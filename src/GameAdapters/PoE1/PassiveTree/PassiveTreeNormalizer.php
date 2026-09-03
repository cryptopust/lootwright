<?php

namespace Lootwright\GameAdapters\PoE1\PassiveTree;

use JsonException;

final class PassiveTreeNormalizer
{
    private const MAX_CLASSES = 32;

    private const MAX_NODES = 10_000;

    private const MAX_STATS_PER_NODE = 128;

    private const MAX_CONNECTIONS_PER_DIRECTION = 256;

    /** @return array<string, mixed> */
    public function normalize(string $json): array
    {
        try {
            $document = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new PassiveTreeSchemaViolation('malformed_json');
        }

        if (! is_array($document)
            || ! is_string($document['tree'] ?? null)
            || trim($document['tree']) === ''
            || ! is_array($document['classes'] ?? null)
            || ! array_is_list($document['classes'])
            || count($document['classes']) > self::MAX_CLASSES
            || ! is_array($document['nodes'] ?? null)
            || array_is_list($document['nodes'])
            || count($document['nodes']) > self::MAX_NODES
        ) {
            throw new PassiveTreeSchemaViolation('unexpected_schema');
        }

        [$classes, $ascendancyOwners, $ascendancyNames] = $this->classes($document['classes']);
        $nodeIds = array_map('strval', array_keys($document['nodes']));
        $knownNodes = array_fill_keys($nodeIds, true);
        $nodes = [];

        foreach ($document['nodes'] as $rawId => $rawNode) {
            $id = (string) $rawId;
            if ($id === '' || ! is_array($rawNode) || array_is_list($rawNode)) {
                throw new PassiveTreeSchemaViolation('unexpected_node_schema');
            }

            $incoming = $this->connections($rawNode['in'] ?? [], $knownNodes);
            $outgoing = $this->connections($rawNode['out'] ?? [], $knownNodes);
            $ascendancyUpstreamId = $this->nullableBoundedString($rawNode, 'ascendancyName', 160);
            $isSecondary = ($rawNode['isBloodline'] ?? false) === true;
            $classIndex = $this->nullableInteger($rawNode, 'classStartIndex');
            $className = $classIndex === null ? null : ($classes[$classIndex]['name'] ?? null);

            if ($classIndex !== null && ! is_string($className)) {
                throw new PassiveTreeSchemaViolation('unknown_class_start');
            }

            if ($ascendancyUpstreamId !== null && ! $isSecondary && ! isset($ascendancyOwners[$ascendancyUpstreamId])) {
                throw new PassiveTreeSchemaViolation('unknown_ascendancy_relation');
            }
            if ($ascendancyUpstreamId !== null && ! $isSecondary) {
                $className = $ascendancyOwners[$ascendancyUpstreamId];
            }
            $ascendancy = $ascendancyUpstreamId === null
                ? null
                : ($isSecondary ? $ascendancyUpstreamId : $ascendancyNames[$ascendancyUpstreamId]);

            $nodes[] = [
                'id' => $id,
                'name' => $this->nullableBoundedString($rawNode, 'name', 255),
                'node_type' => $this->nodeType($id, $rawNode),
                'is_keystone' => ($rawNode['isKeystone'] ?? false) === true,
                'is_notable' => ($rawNode['isNotable'] ?? false) === true,
                'is_mastery' => ($rawNode['isMastery'] ?? false) === true,
                'stats' => $this->stringList($rawNode['stats'] ?? [], self::MAX_STATS_PER_NODE, 2_000),
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'class' => $className,
                'ascendancy' => $ascendancy,
                'ascendancy_upstream_id' => $ascendancyUpstreamId,
                'progression_type' => $ascendancy === null ? null : ($isSecondary ? 'secondary' : 'regular'),
                'icon_path' => $this->iconPath($rawNode),
                'mastery_effects' => $this->masteryEffects($rawNode),
            ];
        }

        usort($nodes, static fn (array $left, array $right): int => strnatcmp($left['id'], $right['id']));

        return [
            'tree' => trim($document['tree']),
            'classes' => array_values($classes),
            'nodes' => $nodes,
        ];
    }

    /**
     * @param  list<mixed>  $rawClasses
     * @return array{array<int, array<string, mixed>>, array<string, string>, array<string, string>}
     */
    private function classes(array $rawClasses): array
    {
        $classes = [];
        $owners = [];
        $names = [];

        foreach ($rawClasses as $index => $rawClass) {
            if (! is_array($rawClass) || array_is_list($rawClass)) {
                throw new PassiveTreeSchemaViolation('unexpected_class_schema');
            }
            $name = $this->requiredBoundedString($rawClass, 'name', 160);
            $rawAscendancies = $rawClass['ascendancies'] ?? null;
            if (! is_array($rawAscendancies) || ! array_is_list($rawAscendancies) || count($rawAscendancies) > 32) {
                throw new PassiveTreeSchemaViolation('unexpected_ascendancy_schema');
            }
            $ascendancies = [];
            foreach ($rawAscendancies as $rawAscendancy) {
                if (! is_array($rawAscendancy) || array_is_list($rawAscendancy)) {
                    throw new PassiveTreeSchemaViolation('unexpected_ascendancy_schema');
                }
                $upstreamId = $this->requiredBoundedString($rawAscendancy, 'id', 160);
                $canonicalName = $this->requiredBoundedString($rawAscendancy, 'name', 160);
                if (isset($owners[$upstreamId]) && $owners[$upstreamId] !== $name) {
                    throw new PassiveTreeSchemaViolation('ambiguous_ascendancy_relation');
                }
                $owners[$upstreamId] = $name;
                $names[$upstreamId] = $canonicalName;
                $ascendancies[] = ['upstream_id' => $upstreamId, 'name' => $canonicalName];
            }
            $classes[$index] = ['index' => $index, 'name' => $name, 'ascendancies' => $ascendancies];
        }

        return [$classes, $owners, $names];
    }

    /** @param array<string, mixed> $node */
    private function nodeType(string $id, array $node): string
    {
        return match (true) {
            $id === 'root' => 'root',
            array_key_exists('classStartIndex', $node) => 'class_start',
            ($node['isAscendancyStart'] ?? false) === true => 'ascendancy_start',
            ($node['isKeystone'] ?? false) === true => 'keystone',
            ($node['isMastery'] ?? false) === true => 'mastery',
            ($node['isNotable'] ?? false) === true => 'notable',
            ($node['isJewelSocket'] ?? false) === true => 'jewel_socket',
            ($node['isProxy'] ?? false) === true => 'proxy',
            default => 'small',
        };
    }

    /**
     * @param  array<string, bool>  $knownNodes
     * @return list<string>
     */
    private function connections(mixed $value, array $knownNodes): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > self::MAX_CONNECTIONS_PER_DIRECTION) {
            throw new PassiveTreeSchemaViolation('unexpected_connection_schema');
        }
        $connections = [];
        foreach ($value as $connection) {
            if (! is_int($connection) && ! is_string($connection)) {
                throw new PassiveTreeSchemaViolation('unexpected_connection_schema');
            }
            $connection = (string) $connection;
            if (! isset($knownNodes[$connection])) {
                throw new PassiveTreeSchemaViolation('unknown_connection_target');
            }
            $connections[$connection] = true;
        }
        $result = array_keys($connections);
        usort($result, 'strnatcmp');

        return $result;
    }

    /** @param array<string, mixed> $node
     * @return list<array{effect_id: string, stats: list<string>}>
     */
    private function masteryEffects(array $node): array
    {
        $rawEffects = $node['masteryEffects'] ?? [];
        if (! is_array($rawEffects) || ! array_is_list($rawEffects) || count($rawEffects) > 128) {
            throw new PassiveTreeSchemaViolation('unexpected_mastery_schema');
        }
        $effects = [];
        foreach ($rawEffects as $effect) {
            if (! is_array($effect) || array_is_list($effect) || (! is_int($effect['effect'] ?? null) && ! is_string($effect['effect'] ?? null))) {
                throw new PassiveTreeSchemaViolation('unexpected_mastery_schema');
            }
            $effects[] = [
                'effect_id' => (string) $effect['effect'],
                'stats' => $this->stringList($effect['stats'] ?? [], self::MAX_STATS_PER_NODE, 2_000),
            ];
        }

        return $effects;
    }

    /** @param array<string, mixed> $node */
    private function iconPath(array $node): ?string
    {
        $icon = $this->nullableBoundedString($node, 'icon', 512);
        if ($icon !== null && (str_contains($icon, '://') || str_starts_with($icon, '/') || str_contains($icon, '..') || str_contains($icon, '\\'))) {
            throw new PassiveTreeSchemaViolation('unsafe_icon_reference');
        }

        return $icon;
    }

    /** @return list<string> */
    private function stringList(mixed $value, int $maximumItems, int $maximumLength): array
    {
        if (! is_array($value) || ! array_is_list($value) || count($value) > $maximumItems) {
            throw new PassiveTreeSchemaViolation('unexpected_string_list');
        }
        $result = [];
        foreach ($value as $item) {
            if (! is_string($item) || strlen($item) > $maximumLength) {
                throw new PassiveTreeSchemaViolation('unexpected_string_list');
            }
            $result[] = $item;
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    private function requiredBoundedString(array $data, string $key, int $maximumLength): string
    {
        $value = $this->nullableBoundedString($data, $key, $maximumLength);
        if ($value === null || trim($value) === '') {
            throw new PassiveTreeSchemaViolation('missing_required_string');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableBoundedString(array $data, string $key, int $maximumLength): ?string
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (! is_string($value) || strlen($value) > $maximumLength) {
            throw new PassiveTreeSchemaViolation('unexpected_string');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableInteger(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (! is_int($value) || $value < 0 || $value >= self::MAX_CLASSES) {
            throw new PassiveTreeSchemaViolation('unexpected_integer');
        }

        return $value;
    }
}

<?php

namespace Lootwright\GameAdapters\Shared\Pob;

use DOMDocument;
use DOMElement;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\ImportProvenance;
use Lootwright\Domain\BuildIntake\Import\ImportWarning;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\BuildIntake\Import\UnsupportedFeature;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

abstract class AbstractPobNormalizer
{
    abstract protected function edition(): GameEdition;

    abstract protected function beta(): bool;

    abstract protected function provenance(): ImportProvenance;

    public function normalize(DOMDocument $document, string $inputChecksum, ImportLimits $limits): DomainResult
    {
        $root = $document->documentElement;

        if (! $root instanceof DOMElement) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The build XML has no root element.');
        }

        $warnings = [];
        $build = $this->directChild($root, 'Build');
        $tree = $this->directChild($root, 'Tree');
        $skillsNode = $this->directChild($root, 'Skills');
        $itemsNode = $this->directChild($root, 'Items');
        $configNode = $this->directChild($root, 'Config');
        $notesNode = $this->directChild($root, 'Notes');

        if ($build === null) {
            return $this->failure(DomainErrorCode::InvalidXml, 'The build XML has no Build section.');
        }

        $level = $this->integerAttribute($build, 'level');

        if ($level !== null && ($level < 1 || $level > 100)) {
            $warnings[] = new ImportWarning('invalid_character_level', 'Character level is outside 1-100 and was left unknown.', '/Build/@level');
            $level = null;
        }

        $spec = $tree === null ? null : $this->activeSpec($tree);
        $classId = $this->canonicalId(
            'class',
            $spec?->getAttribute('classInternalId') ?: $spec?->getAttribute('classId') ?: $build->getAttribute('className'),
        );
        $ascendancyId = $this->canonicalId(
            'ascendancy',
            $spec?->getAttribute('ascendancyInternalId') ?: $spec?->getAttribute('ascendClassId') ?: $build->getAttribute('ascendClassName'),
        );
        $choices = $this->choices($build);
        $passivesResult = $this->passives($spec, $limits, $warnings);

        if ($passivesResult->isFailure()) {
            return $passivesResult;
        }

        $rawPassives = $passivesResult->value();

        if (! is_array($rawPassives) || ! array_is_list($rawPassives)) {
            return $this->failure(DomainErrorCode::InvalidValue, 'The normalized passive-node collection is invalid.');
        }

        $passives = [];

        foreach ($rawPassives as $passive) {
            if (! is_string($passive)) {
                return $this->failure(DomainErrorCode::InvalidValue, 'A normalized passive-node identifier is invalid.');
            }

            $passives[] = $passive;
        }

        $skills = $this->skills($skillsNode, $limits, $warnings);
        $itemsResult = $this->items($itemsNode, $limits, $warnings);

        if ($itemsResult->isFailure()) {
            return $itemsResult;
        }

        $items = $itemsResult->value();

        if (! is_array($items)) {
            return $this->failure(DomainErrorCode::InvalidValue, 'The normalized item collection is invalid.');
        }

        $configuration = $this->namedValues($configNode, $warnings);
        $summary = $this->summary($build, $warnings);
        $notes = $notesNode === null ? '' : $notesNode->textContent;

        if (strlen($notes) > $limits->textBytes) {
            return $this->failure(DomainErrorCode::InputTooLarge, 'Build notes exceed the text limit.');
        }

        $unsupported = $this->unsupported($root);
        $canonical = new CanonicalImportedBuild(
            $this->edition(),
            $this->nullable($build->getAttribute('targetVersion')),
            $level,
            $classId,
            $ascendancyId,
            $choices,
            $passives,
            $skills,
            array_values($items),
            $configuration,
            $summary,
            $notes,
            $this->beta(),
        );

        return DomainResult::success(new PobImportResult(
            $canonical,
            $warnings,
            $unsupported,
            $this->provenance()->parserVersion,
            $inputChecksum,
            $this->provenance(),
        ));
    }

    /** @return array<string, string> */
    private function choices(DOMElement $build): array
    {
        $choices = [];

        foreach (['bandit', 'pantheonMajorGod', 'pantheonMinorGod'] as $name) {
            $value = $this->nullable($build->getAttribute($name));

            if ($value !== null) {
                $choices[$name] = $this->bounded($value, 256);
            }
        }

        ksort($choices, SORT_STRING);

        return $choices;
    }

    /** @param list<ImportWarning> $warnings
     */
    private function passives(?DOMElement $spec, ImportLimits $limits, array &$warnings): DomainResult
    {
        if ($spec === null) {
            return DomainResult::success([]);
        }

        $nodes = [];

        foreach (array_filter(explode(',', $spec->getAttribute('nodes'))) as $raw) {
            $raw = trim($raw);

            if (preg_match('/^[0-9]{1,12}$/D', $raw) !== 1) {
                $warnings[] = new ImportWarning('invalid_passive_node', 'An invalid passive node identifier was ignored.', '/Tree/Spec/@nodes');

                continue;
            }

            $nodes[] = 'pob.node.'.$raw;

            if (count($nodes) > $limits->passiveNodes) {
                return $this->failure(DomainErrorCode::InputTooLarge, 'The build exceeds the passive-node limit.');
            }
        }

        $nodes = array_values(array_unique($nodes));
        sort($nodes, SORT_STRING);

        return DomainResult::success($nodes);
    }

    /** @param list<ImportWarning> $warnings
     * @return list<array<string, mixed>>
     */
    private function skills(?DOMElement $skillsNode, ImportLimits $limits, array &$warnings): array
    {
        if ($skillsNode === null) {
            return [];
        }

        $skills = [];
        $gemCount = 0;

        foreach ($skillsNode->getElementsByTagName('Skill') as $skillNode) {
            if (count($skills) >= $limits->skills) {
                $warnings[] = new ImportWarning('skill_limit', 'Additional skill groups were not normalized.', '/Skills');
                break;
            }

            $group = count($skills) + 1;
            $gems = [];

            foreach ($skillNode->childNodes as $gemNode) {
                if (! $gemNode instanceof DOMElement || $gemNode->tagName !== 'Gem') {
                    continue;
                }

                $gemCount++;

                if ($gemCount > $limits->gems) {
                    $warnings[] = new ImportWarning('gem_limit', 'Additional gems were not normalized.', '/Skills');
                    break 2;
                }

                $externalId = $gemNode->getAttribute('skillId')
                    ?: $gemNode->getAttribute('gemId')
                    ?: $gemNode->getAttribute('nameSpec');
                $gems[] = [
                    'id' => $this->canonicalId('gem', $externalId),
                    'level' => $this->integerAttribute($gemNode, 'level'),
                    'quality' => $this->integerAttribute($gemNode, 'quality'),
                    'enabled' => $this->booleanAttribute($gemNode, 'enabled', true),
                    'socket_index' => count($gems) + 1,
                    'link_group' => $group,
                ];
            }

            $skills[] = [
                'id' => 'pob.skill_group.'.$group,
                'slot' => $this->bounded($skillNode->getAttribute('slot'), 128),
                'enabled' => $this->booleanAttribute($skillNode, 'enabled', true),
                'gems' => $gems,
            ];
        }

        return $skills;
    }

    /** @param list<ImportWarning> $warnings */
    private function items(?DOMElement $itemsNode, ImportLimits $limits, array &$warnings): DomainResult
    {
        if ($itemsNode === null) {
            return DomainResult::success([]);
        }

        $items = [];
        $slots = [];

        foreach ($itemsNode->getElementsByTagName('Slot') as $slotNode) {
            if ($slotNode->hasAttribute('itemId')) {
                $slots[$slotNode->getAttribute('itemId')][] = $this->bounded($slotNode->getAttribute('name'), 128);
            }
        }

        foreach ($itemsNode->childNodes as $itemNode) {
            if (! $itemNode instanceof DOMElement || $itemNode->tagName !== 'Item') {
                continue;
            }

            if (count($items) >= $limits->items) {
                return $this->failure(DomainErrorCode::InputTooLarge, 'The build exceeds the item-count limit.');
            }

            $id = $itemNode->getAttribute('id');

            if ($id === '' || isset($items[$id])) {
                return $this->failure(DomainErrorCode::DuplicateValue, 'Items require unique non-empty identifiers.');
            }

            $text = $this->directText($itemNode);

            if (strlen($text) > $limits->textBytes) {
                return $this->failure(DomainErrorCode::InputTooLarge, 'An item text block exceeds the text limit.');
            }

            $items[$id] = [
                'id' => 'pob.item.'.$this->slug($id),
                'source_id' => $this->bounded($id, 128),
                'slots' => array_values(array_unique($slots[$id] ?? [])),
                'item_text_untrusted' => $text,
            ];
        }

        return DomainResult::success($items);
    }

    /** @param list<ImportWarning> $warnings
     * @return array<string, bool|int|string>
     */
    private function namedValues(?DOMElement $parent, array &$warnings): array
    {
        if ($parent === null) {
            return [];
        }

        $values = [];

        foreach ($parent->getElementsByTagName('Input') as $input) {
            $name = $input->getAttribute('name');

            if ($name === '' || isset($values[$name])) {
                $warnings[] = new ImportWarning('duplicate_or_missing_configuration', 'A duplicate or unnamed configuration value was ignored.', '/Config/Input');

                continue;
            }

            $key = $this->bounded($name, 128);

            if ($input->hasAttribute('boolean')) {
                $values[$key] = $this->booleanAttribute($input, 'boolean', false);
            } elseif ($input->hasAttribute('number') && preg_match('/^-?[0-9]+$/D', $input->getAttribute('number')) === 1) {
                $values[$key] = (int) $input->getAttribute('number');
            } else {
                $values[$key] = $this->bounded($input->getAttribute('string'), 512);
            }
        }

        ksort($values, SORT_STRING);

        return $values;
    }

    /** @param list<ImportWarning> $warnings
     * @return array<string, int|string>
     */
    private function summary(DOMElement $build, array &$warnings): array
    {
        $values = [];

        foreach ($build->getElementsByTagName('PlayerStat') as $stat) {
            $name = $stat->getAttribute('stat');
            $value = $stat->getAttribute('value');

            if ($name === '' || isset($values[$name])) {
                $warnings[] = new ImportWarning('duplicate_or_missing_summary', 'A duplicate or unnamed summary value was ignored.', '/Build/PlayerStat');

                continue;
            }

            $values[$this->bounded($name, 128)] = preg_match('/^-?[0-9]+$/D', $value) === 1
                ? (int) $value
                : $this->bounded($value, 256);
        }

        ksort($values, SORT_STRING);

        return $values;
    }

    /** @return list<UnsupportedFeature> */
    private function unsupported(DOMElement $root): array
    {
        $consumedAttributes = [
            'Build' => ['targetVersion', 'level', 'className', 'ascendClassName', 'bandit', 'pantheonMajorGod', 'pantheonMinorGod'],
            'PlayerStat' => ['stat', 'value'],
            'Config' => [],
            'Input' => ['name', 'boolean', 'number', 'string'],
            'Notes' => [],
            'Tree' => ['activeSpec'],
            'Spec' => ['classInternalId', 'classId', 'ascendancyInternalId', 'ascendClassId', 'nodes'],
            'Items' => [],
            'Item' => ['id'],
            'Slot' => ['itemId', 'name'],
            'Skills' => [],
            'SkillSet' => [],
            'Skill' => ['enabled', 'slot'],
            'Gem' => ['skillId', 'gemId', 'nameSpec', 'level', 'quality', 'enabled'],
        ];
        $unsupported = [];

        foreach ($root->getElementsByTagName('*') as $element) {
            if (! array_key_exists($element->tagName, $consumedAttributes)) {
                $unsupported[] = new UnsupportedFeature(
                    $this->path($element),
                    $element->tagName,
                    $this->attributes($element),
                );

                continue;
            }

            $unknownAttributes = [];

            foreach ($element->attributes as $attribute) {
                if (! in_array($attribute->nodeName, $consumedAttributes[$element->tagName], true)) {
                    $unknownAttributes[$attribute->nodeName] = $this->bounded($attribute->nodeValue ?? '', 256);
                }
            }

            if ($unknownAttributes !== []) {
                ksort($unknownAttributes, SORT_STRING);
                $unsupported[] = new UnsupportedFeature(
                    $this->path($element).'/@*',
                    $element->tagName.'@unsupported_attributes',
                    $unknownAttributes,
                );
            }
        }

        return $unsupported;
    }

    /** @return array<string, string> */
    private function attributes(DOMElement $element): array
    {
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[$attribute->nodeName] = $this->bounded($attribute->nodeValue ?? '', 256);
        }

        ksort($attributes, SORT_STRING);

        return $attributes;
    }

    private function activeSpec(DOMElement $tree): ?DOMElement
    {
        $specs = [];

        foreach ($tree->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'Spec') {
                $specs[] = $child;
            }
        }

        $active = max(1, (int) $tree->getAttribute('activeSpec'));

        return $specs[$active - 1] ?? $specs[0] ?? null;
    }

    private function directChild(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === $name) {
                return $child;
            }
        }

        return null;
    }

    private function directText(DOMElement $element): string
    {
        $text = '';

        foreach ($element->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $text .= $child->nodeValue ?? '';
            }
        }

        return trim($text);
    }

    private function integerAttribute(DOMElement $element, string $name): ?int
    {
        $value = $element->getAttribute($name);

        return preg_match('/^-?[0-9]+$/D', $value) === 1 ? (int) $value : null;
    }

    private function booleanAttribute(DOMElement $element, string $name, bool $default): bool
    {
        if (! $element->hasAttribute($name)) {
            return $default;
        }

        return strtolower($element->getAttribute($name)) === 'true';
    }

    private function canonicalId(string $kind, string $raw): ?string
    {
        $raw = trim($raw);

        return $raw === '' ? null : 'pob.'.$kind.'.'.$this->slug($raw);
    }

    private function slug(string $value): string
    {
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9._-]+/', '-', $value));
        $slug = trim($slug, '-_.');

        return $slug === '' ? substr(hash('sha256', $value), 0, 16) : substr($slug, 0, 96);
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $this->bounded($value, 256);
    }

    private function bounded(string $value, int $limit): string
    {
        return mb_substr($value, 0, $limit, 'UTF-8');
    }

    private function path(DOMElement $element): string
    {
        $parts = [];
        $node = $element;

        while ($node instanceof DOMElement) {
            array_unshift($parts, $node->tagName);
            $node = $node->parentNode;
        }

        return '/'.implode('/', $parts);
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }
}

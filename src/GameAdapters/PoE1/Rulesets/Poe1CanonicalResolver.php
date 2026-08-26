<?php

namespace Lootwright\GameAdapters\PoE1\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\Ascendancy;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;
use Lootwright\Domain\PoeCatalog\Canonical\CharacterClass;

final readonly class Poe1CanonicalResolver
{
    public function __construct(private Poe1Ruleset $ruleset) {}

    public function resolve(CanonicalEntityType $type, string $identifier): ?CanonicalGameEntity
    {
        foreach ($this->candidates($type, $identifier) as $candidate) {
            if (($entity = $this->ruleset->find($type, $candidate)) !== null) {
                return $entity;
            }
        }

        return null;
    }

    public function characterClass(string $identifier): ?CharacterClass
    {
        $entity = $this->resolve(CanonicalEntityType::CharacterClass, $identifier);

        return $entity instanceof CharacterClass ? $entity : null;
    }

    public function ascendancy(string $identifier): ?Ascendancy
    {
        $entity = $this->resolve(CanonicalEntityType::Ascendancy, $identifier);

        return $entity instanceof Ascendancy ? $entity : null;
    }

    /** @return list<string> */
    private function candidates(CanonicalEntityType $type, string $identifier): array
    {
        $prefix = match ($type) {
            CanonicalEntityType::CharacterClass => 'class:',
            CanonicalEntityType::Ascendancy => 'ascendancy:',
            CanonicalEntityType::PassiveNode, CanonicalEntityType::Keystone => 'passive:',
            default => '',
        };
        $raw = preg_replace('/^poe1\.pob\.(?:class|ascendancy|node|gem)\./D', '', trim($identifier));
        $raw = is_string($raw) ? $raw : trim($identifier);
        $candidates = [$identifier, $prefix.$raw];

        if (in_array($type, [CanonicalEntityType::CharacterClass, CanonicalEntityType::Ascendancy], true)) {
            foreach ($this->ruleset->list($type) as $entity) {
                $aliases = is_array($entity->attributes['aliases'] ?? null) ? $entity->attributes['aliases'] : [];
                if (in_array($raw, $aliases, true)) {
                    $candidates[] = $entity->externalId;
                }
            }
        }

        return array_values(array_unique($candidates));
    }
}

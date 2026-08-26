<?php

namespace Lootwright\GameAdapters\PoE1\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use RuntimeException;

final readonly class Poe1Ruleset
{
    /** @var array<string, array<string, CanonicalGameEntity>> */
    private array $entities;

    /**
     * @param  list<CanonicalGameEntity>  $entities
     * @param  array<string, int>  $coverage
     * @param  list<string>  $unknownIdentifiers
     * @param  list<string>  $brokenReferences
     * @param  list<string>  $missingRelationships
     */
    public function __construct(
        public RulesetIdentity $identity,
        array $entities,
        public array $coverage,
        public array $unknownIdentifiers,
        public array $brokenReferences,
        public array $missingRelationships,
        public string $activatedAt,
    ) {
        $indexed = [];
        foreach ($entities as $entity) {
            if ($entity->edition !== $identity->edition || $entity->rulesetVersionId !== $identity->id->value) {
                throw new RuntimeException('PoE1 ruleset entity scope does not match its immutable identity.');
            }
            $type = $entity->type()->value;
            if (isset($indexed[$type][$entity->externalId])) {
                throw new RuntimeException('PoE1 ruleset contains a duplicate canonical external identifier.');
            }
            $indexed[$type][$entity->externalId] = $entity;
        }
        $this->entities = $indexed;
    }

    public function find(CanonicalEntityType $type, string $externalId): ?CanonicalGameEntity
    {
        return $this->entities[$type->value][$externalId] ?? null;
    }

    /** @return list<CanonicalGameEntity> */
    public function list(CanonicalEntityType $type): array
    {
        return array_values($this->entities[$type->value] ?? []);
    }
}

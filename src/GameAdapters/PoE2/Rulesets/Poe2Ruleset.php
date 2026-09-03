<?php

namespace Lootwright\GameAdapters\PoE2\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use RuntimeException;

/** Immutable, edition-scoped PoE2 canonical ruleset view. */
final readonly class Poe2Ruleset
{
    /** @var array<string,array<string,CanonicalGameEntity>> */
    private array $entities;

    /** @param list<CanonicalGameEntity> $entities
     * @param  array<string, int>  $coverage
     */
    public function __construct(public RulesetIdentity $identity, array $entities, public array $coverage = [])
    {
        $indexed = [];
        foreach ($entities as $entity) {
            if ($entity->edition !== $identity->edition || $entity->rulesetVersionId !== $identity->id->value) {
                throw new RuntimeException('PoE2 ruleset entity scope does not match its identity.');
            }
            $type = $entity->type()->value;
            if (isset($indexed[$type][$entity->externalId])) {
                throw new RuntimeException('PoE2 ruleset contains a duplicate canonical identifier.');
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

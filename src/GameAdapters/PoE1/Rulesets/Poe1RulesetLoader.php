<?php

namespace Lootwright\GameAdapters\PoE1\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use RuntimeException;

final readonly class Poe1RulesetLoader
{
    public function __construct(private GameDataRepository $data) {}

    public function load(RulesetIdentity $identity, ?string $activatedAt = null): Poe1Ruleset
    {
        if ($identity->edition->value !== 'poe1') {
            throw new RuntimeException('The PoE1 ruleset loader cannot load a different edition.');
        }

        $entities = $this->data->listForRuleset($identity->edition, $identity->id->value);
        if ($entities === []) {
            throw new RuntimeException('The active PoE1 ruleset contains no canonical data.');
        }

        $coverage = [];
        foreach (CanonicalEntityType::cases() as $type) {
            $coverage[$type->value] = count(array_filter($entities, static fn ($entity): bool => $entity->type() === $type));
        }

        return new Poe1Ruleset($identity, $entities, $coverage, [], [], [], $activatedAt ?? 'unknown');
    }
}

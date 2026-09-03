<?php

namespace Lootwright\GameAdapters\PoE2\Rulesets;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use RuntimeException;

final readonly class Poe2RulesetLoader
{
    public function __construct(private GameDataRepository $data) {}

    public function load(RulesetIdentity $identity): Poe2Ruleset
    {
        if ($identity->edition !== GameEdition::Poe2) {
            throw new RuntimeException('The PoE2 ruleset loader cannot load another edition.');
        }
        $entities = $this->data->listForRuleset(GameEdition::Poe2, $identity->id->value);
        if ($entities === []) {
            throw new RuntimeException('The active PoE2 ruleset contains no canonical data.');
        }
        $coverage = [];
        foreach (CanonicalEntityType::cases() as $type) {
            $coverage[$type->value] = count(array_filter($entities, static fn ($entity): bool => $entity->type() === $type));
        }

        return new Poe2Ruleset($identity, $entities, $coverage);
    }
}

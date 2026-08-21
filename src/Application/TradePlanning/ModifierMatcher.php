<?php

namespace Lootwright\Application\TradePlanning;

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\ModifierDefinition;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\TradePlanning\TradeVocabulary;
use Lootwright\Domain\TradePlanning\TradeVocabularyEntry;

final readonly class ModifierMatcher
{
    public function __construct(private GameDataRepository $gameData) {}

    public function resolve(TradeVocabulary $vocabulary, RulesetIdentity $ruleset, string $canonicalModifierId): ?TradeVocabularyEntry
    {
        $entry = $vocabulary->modifier($canonicalModifierId);
        $canonical = $this->gameData->find(
            $vocabulary->edition(),
            $ruleset->id->value,
            CanonicalEntityType::ModifierDefinition,
            $canonicalModifierId,
        );

        return $entry !== null
            && $canonical instanceof ModifierDefinition
            && $canonical->edition === $entry->edition
            && $canonical->provenance->sourceCode === $entry->provenance->sourceCode
            && $canonical->provenance->sourceVersion === $entry->provenance->sourceVersion
            && $canonical->provenance->checksumSha256 === $entry->provenance->checksumSha256
            ? $entry
            : null;
    }
}

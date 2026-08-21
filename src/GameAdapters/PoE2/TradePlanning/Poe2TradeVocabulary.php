<?php

namespace Lootwright\GameAdapters\PoE2\TradePlanning;

use InvalidArgumentException;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\TradePlanning\TradeVocabulary;
use Lootwright\Domain\TradePlanning\TradeVocabularyEntry;

/** Dormant fail-closed vocabulary contract for the phase-two adapter. */
final readonly class Poe2TradeVocabulary implements TradeVocabulary
{
    public function __construct(private RulesetIdentity $identity)
    {
        if ($identity->edition !== GameEdition::Poe2) {
            throw new InvalidArgumentException('The PoE2 Trade vocabulary requires a PoE2 ruleset.');
        }
    }

    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    public function ruleset(): RulesetReference
    {
        return new RulesetReference(
            GameEdition::Poe2,
            $this->identity->id->value,
            $this->identity->version->value,
            $this->identity->checksumSha256,
        );
    }

    public function enabled(): bool
    {
        return false;
    }

    public function modifier(string $canonicalModifierId): ?TradeVocabularyEntry
    {
        return null;
    }

    public function itemClass(string $slot): ?string
    {
        return null;
    }
}

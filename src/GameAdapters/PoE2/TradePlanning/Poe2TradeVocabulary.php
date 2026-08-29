<?php

namespace Lootwright\GameAdapters\PoE2\TradePlanning;

use InvalidArgumentException;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\TradePlanning\TradeVocabulary;
use Lootwright\Domain\TradePlanning\TradeVocabularyEntry;

/** PoE2 vocabulary remains data-driven and enabled only with explicit entries. */
final readonly class Poe2TradeVocabulary implements TradeVocabulary
{
    /**
     * @param  list<TradeVocabularyEntry>  $modifiers
     * @param  array<string, string>  $itemClasses
     */
    public function __construct(private RulesetIdentity $identity, array $modifiers = [], private array $itemClasses = [], private bool $isEnabled = true)
    {
        if ($identity->edition !== GameEdition::Poe2) {
            throw new InvalidArgumentException('The PoE2 Trade vocabulary requires a PoE2 ruleset.');
        }
        foreach ($modifiers as $modifier) {
            if ($modifier->edition !== GameEdition::Poe2) {
                throw new InvalidArgumentException('PoE2 Trade entries must be edition scoped.');
            }
        }
        $this->modifiers = $modifiers;
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
        return $this->isEnabled;
    }

    public function modifier(string $canonicalModifierId): ?TradeVocabularyEntry
    {
        foreach ($this->modifiers as $modifier) {
            if ($modifier->canonicalModifierId === $canonicalModifierId) {
                return $modifier;
            }
        }

        return null;
    }

    public function itemClass(string $slot): ?string
    {
        return $this->itemClasses[$slot] ?? null;
    }

    /** @var list<TradeVocabularyEntry> */
    private array $modifiers;
}

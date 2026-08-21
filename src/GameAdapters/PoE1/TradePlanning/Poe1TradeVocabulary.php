<?php

namespace Lootwright\GameAdapters\PoE1\TradePlanning;

use InvalidArgumentException;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\TradePlanning\TradeVocabulary;
use Lootwright\Domain\TradePlanning\TradeVocabularyEntry;

final readonly class Poe1TradeVocabulary implements TradeVocabulary
{
    /** @var array<string,TradeVocabularyEntry> */
    private array $modifiers;

    /**
     * @param  list<TradeVocabularyEntry>  $modifiers
     * @param  array<string,string>  $itemClasses
     */
    public function __construct(
        private RulesetIdentity $identity,
        array $modifiers,
        private array $itemClasses = [],
        private bool $isEnabled = true,
    ) {
        if ($identity->edition !== GameEdition::Poe1) {
            throw new InvalidArgumentException('The PoE1 Trade vocabulary requires a PoE1 ruleset.');
        }
        $indexed = [];
        foreach ($modifiers as $modifier) {
            if ($modifier->edition !== GameEdition::Poe1 || isset($indexed[$modifier->canonicalModifierId])) {
                throw new InvalidArgumentException('PoE1 Trade vocabulary entries must be unique PoE1 entries.');
            }
            $indexed[$modifier->canonicalModifierId] = $modifier;
        }
        foreach ($itemClasses as $slot => $label) {
            if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $slot) !== 1
                || trim($label) === ''
                || mb_strlen($label) > 240
                || preg_match('#(?:https?://|/api/|[{}]|[\x00-\x08\x0B\x0C\x0E-\x1F])#i', $label) === 1
            ) {
                throw new InvalidArgumentException('PoE1 item classes require bounded descriptive labels.');
            }
        }
        $this->modifiers = $indexed;
    }

    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    public function ruleset(): RulesetReference
    {
        return new RulesetReference(
            GameEdition::Poe1,
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
        return $this->modifiers[$canonicalModifierId] ?? null;
    }

    public function itemClass(string $slot): ?string
    {
        return $this->itemClasses[$slot] ?? null;
    }
}

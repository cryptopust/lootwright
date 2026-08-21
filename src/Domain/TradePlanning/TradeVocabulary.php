<?php

namespace Lootwright\Domain\TradePlanning;

use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;

interface TradeVocabulary
{
    public function edition(): GameEdition;

    public function ruleset(): RulesetReference;

    public function enabled(): bool;

    public function modifier(string $canonicalModifierId): ?TradeVocabularyEntry;

    public function itemClass(string $slot): ?string;
}

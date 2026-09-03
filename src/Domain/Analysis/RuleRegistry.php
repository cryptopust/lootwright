<?php

namespace Lootwright\Domain\Analysis;

use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\RulesetVersion;

interface RuleRegistry
{
    public function edition(): GameEdition;

    public function version(): RulesetVersion;

    /** @return list<AnalysisRule> */
    public function rules(): array;
}

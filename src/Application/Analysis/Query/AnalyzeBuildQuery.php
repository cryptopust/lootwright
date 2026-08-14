<?php

namespace Lootwright\Application\Analysis\Query;

use Lootwright\Domain\Shared\Identity\BuildId;
use Lootwright\Domain\Shared\Identity\RulesetId;

final readonly class AnalyzeBuildQuery
{
    public function __construct(
        public BuildId $buildId,
        public RulesetId $rulesetId,
    ) {}
}

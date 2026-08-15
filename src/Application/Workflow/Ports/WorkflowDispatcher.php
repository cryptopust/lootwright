<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Domain\Shared\Game\GameEdition;

interface WorkflowDispatcher
{
    public function parse(string $artifactId, GameEdition $edition): void;

    public function analyze(string $analysisId, GameEdition $edition, ?string $rulesetChecksumSha256 = null): void;
}

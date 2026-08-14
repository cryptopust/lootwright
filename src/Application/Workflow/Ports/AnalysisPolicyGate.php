<?php

namespace Lootwright\Application\Workflow\Ports;

use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;

interface AnalysisPolicyGate
{
    /** Gate deterministic derivation and any manual external-action recipe. */
    public function authorize(ResolvedAnalysisContext $context): void;
}

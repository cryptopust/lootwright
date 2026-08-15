<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class AnalysisProvenanceStatus
{
    /** @param list<array<string, mixed>> $sources
     * @param list<array<string, mixed>> $policyDecisions
     */
    public function __construct(
        public string $analysisId,
        public array $sources,
        public array $policyDecisions,
    ) {}
}

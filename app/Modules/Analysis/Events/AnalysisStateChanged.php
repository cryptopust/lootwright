<?php

namespace App\Modules\Analysis\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class AnalysisStateChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public string $analysisId,
        public string $state,
    ) {}
}

<?php

namespace App\Modules\Analysis\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class BuildArtifactSubmitted implements ShouldDispatchAfterCommit
{
    public function __construct(
        public string $artifactId,
        public string $analysisId,
        public string $gameEdition,
    ) {}
}

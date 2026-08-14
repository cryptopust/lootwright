<?php

namespace App\Modules\Analysis\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

final readonly class BuildArtifactParsed implements ShouldDispatchAfterCommit
{
    public function __construct(
        public string $artifactId,
        public string $gameEdition,
        public string $adapterKey,
        public string $parserVersion,
    ) {}
}

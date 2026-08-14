<?php

namespace App\Modules\Analysis\Infrastructure;

use App\Modules\Analysis\Jobs\ParseBuildArtifactJob;
use App\Modules\Analysis\Jobs\RunDeterministicAnalysisJob;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;

final class LaravelWorkflowDispatcher implements WorkflowDispatcher
{
    public function parse(string $artifactId): void
    {
        ParseBuildArtifactJob::dispatch($artifactId)->onQueue('build-parsing')->afterCommit();
    }

    public function analyze(string $analysisId): void
    {
        RunDeterministicAnalysisJob::dispatch($analysisId)->onQueue('deterministic-analysis')->afterCommit();
    }
}

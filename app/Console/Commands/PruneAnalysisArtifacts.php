<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lootwright\Application\Workflow\UseCases\PruneExpiredArtifacts;

final class PruneAnalysisArtifacts extends Command
{
    protected $signature = 'analysis:prune-artifacts';

    protected $description = 'Delete expired encrypted raw analysis artifacts';

    public function handle(PruneExpiredArtifacts $useCase): int
    {
        $count = $useCase->handle();
        $this->info("Pruned {$count} expired raw analysis artifact(s).");

        return self::SUCCESS;
    }
}

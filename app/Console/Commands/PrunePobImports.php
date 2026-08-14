<?php

namespace App\Console\Commands;

use App\Modules\BuildIntake\PobImportStore;
use Illuminate\Console\Command;

class PrunePobImports extends Command
{
    protected $signature = 'pob:prune-imports';

    protected $description = 'Delete expired encrypted PoB import records';

    public function handle(PobImportStore $store): int
    {
        $count = $store->pruneExpired();
        $this->info("Pruned {$count} expired PoB import record(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Modules\Release\MvpReleaseDashboard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CheckMvpReleaseGate extends Command
{
    protected $signature = 'release:check-mvp {--json : Print the complete machine-readable report} {--write : Write storage/app/release/mvp-latest.json}';

    protected $description = 'Evaluate independent PoE1 and PoE2 player-facing MVP release gates';

    public function handle(MvpReleaseDashboard $dashboard): int
    {
        $report = $dashboard->report();
        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;

        if ($this->option('write')) {
            $path = storage_path('app/release/mvp-latest.json');
            File::ensureDirectoryExists(dirname($path), 0700);
            File::put($path, $encoded);
            $this->line('Report: '.$path);
        }

        if ($this->option('json')) {
            $this->line($encoded);
        } else {
            $this->components->twoColumnDetail('Overall', (string) $report['overall_status']);
            foreach ($report['editions'] as $edition) {
                $this->components->twoColumnDetail(strtoupper((string) $edition['edition']), (string) $edition['status']);
                foreach ($edition['blockers'] as $blocker) {
                    $this->line('  - '.$blocker);
                }
            }
        }

        return in_array($report['overall_status'], ['PASS', 'PASS_WITH_LIMITATIONS'], true)
            ? self::SUCCESS
            : self::FAILURE;
    }
}

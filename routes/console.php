<?php

use App\Modules\ExternalSources\PoeNinja\PoeNinjaSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pob:prune-imports')->hourly()->withoutOverlapping();
Schedule::command('analysis:prune-artifacts')->hourly()->withoutOverlapping();
Schedule::command('security:prune-retained-data')->dailyAt('03:15')->withoutOverlapping();
Schedule::command('workflow:dispatch-outbox')->everyMinute()->withoutOverlapping();
Schedule::command('lootwright:sources:sync-poe-ninja')->everyThirtyMinutes()->withoutOverlapping();

Artisan::command('lootwright:sources:sync-poe-ninja {--league=}', function (PoeNinjaSyncService $sync): int {
    $league = $this->option('league');
    $result = $sync->sync(is_string($league) ? $league : null);
    $this->line($result->success ? "Synchronized {$result->quoteCount} normalized quotes." : "Sync failed: {$result->failureCode}");

    return $result->success ? 0 : 1;
})->purpose('Synchronize policy-gated poe.ninja economy snapshots.');

Artisan::command('lootwright:sources:status', function (): int {
    DB::table('external_source_sync_runs')->select('source_key', DB::raw('max(completed_at) as last_success'), DB::raw('max(started_at) as last_attempt'))->groupBy('source_key')->orderBy('source_key')->get()->each(fn (object $row) => $this->line("{$row->source_key}: success={$row->last_success} attempt={$row->last_attempt}"));

    return 0;
})->purpose('Show non-sensitive external-source status.');

Artisan::command('lootwright:sources:prune', function (): int {
    $deleted = DB::table('external_source_sync_runs')->where('completed_at', '<', now()->subDays(30))->whereNotExists(fn ($query) => $query->selectRaw('1')->from('economy_quotes')->whereColumn('economy_quotes.source_sync_run_id', 'external_source_sync_runs.id'))->delete();
    $payloadsCleared = DB::table('source_import_staging_records')
        ->whereNotNull('normalized_payload')
        ->whereIn('import_report_id', DB::table('source_import_reports')->select('id')->where('completed_at', '<', now()->subDays(7)))
        ->update(['normalized_payload' => null, 'updated_at' => now()]);
    $this->line("Pruned {$deleted} historical sync runs and cleared {$payloadsCleared} bounded staging payloads.");

    return 0;
})->purpose('Prune bounded external-source operational history.');

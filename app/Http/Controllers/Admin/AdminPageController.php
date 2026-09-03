<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;
use Lootwright\Application\ExternalSources\Ports\SourceRegistry;
use Lootwright\Application\GameData\Ports\DataCoverageReporter;
use Lootwright\Domain\PoeCatalog\Character\Poe1CharacterCatalog;
use Lootwright\Domain\PoeCatalog\Character\Poe2CharacterCatalog;
use Lootwright\Domain\Shared\Game\GameEdition;

final class AdminPageController extends Controller
{
    public function audit(): Response
    {
        return Inertia::render('Admin/AuditLog', ['entries' => DB::table('admin_audit_logs')->join('users as actors', 'actors.id', '=', 'admin_audit_logs.actor_user_id')->latest('admin_audit_logs.created_at')->paginate(50, ['admin_audit_logs.id', 'admin_audit_logs.action', 'admin_audit_logs.reason', 'admin_audit_logs.metadata', 'admin_audit_logs.created_at', 'actors.email as actor_email'])]);
    }

    public function catalog(DataCoverageReporter $coverage): Response
    {
        $rulesets = DB::table('ruleset_versions as rulesets')
            ->leftJoin('ruleset_dataset_approvals as approvals', 'approvals.ruleset_version_id', '=', 'rulesets.id')
            ->leftJoin('ruleset_activations as activations', 'activations.ruleset_version_id', '=', 'rulesets.id')
            ->orderByDesc('rulesets.published_at')
            ->get([
                'rulesets.id', 'rulesets.game_edition', 'rulesets.version', 'rulesets.patch', 'rulesets.checksum_sha256',
                'rulesets.published_at', 'approvals.dataset_classification', 'approvals.provenance_status',
                'approvals.compatibility_status', 'activations.id as activation_id',
            ]);
        $sourceMetadata = DB::table('ruleset_source_snapshots as links')
            ->join('source_snapshots as snapshots', 'snapshots.id', '=', 'links.source_snapshot_id')
            ->leftJoin('external_source_sync_runs as runs', 'runs.source_snapshot_id', '=', 'snapshots.id')
            ->get(['links.ruleset_version_id', 'snapshots.source_code', 'snapshots.checksum_sha256', 'runs.status as run_status', 'runs.failure_code'])
            ->groupBy('ruleset_version_id');
        $entityCounts = DB::table('canonical_game_data')
            ->select(['ruleset_version_id', 'entity_type', DB::raw('count(*) AS aggregate')])
            ->groupBy(['ruleset_version_id', 'entity_type'])
            ->get()->groupBy('ruleset_version_id');

        return Inertia::render('Admin/Catalog', [
            'catalogs' => [Poe1CharacterCatalog::current(), Poe2CharacterCatalog::current()],
            'coverage' => [
                'poe1' => array_map(static fn ($entry): array => $entry->jsonSerialize(), $coverage->forEdition(GameEdition::Poe1)),
                'poe2' => array_map(static fn ($entry): array => $entry->jsonSerialize(), $coverage->forEdition(GameEdition::Poe2)),
            ],
            'importFailures' => DB::table('external_source_sync_runs')
                ->whereIn('status', ['failed', 'quarantined'])
                ->latest('started_at')->limit(50)
                ->get(['source_key', 'game_edition', 'status', 'failure_code', 'started_at']),
            'rulesets' => $rulesets->map(static function (object $ruleset) use ($entityCounts, $sourceMetadata): array {
                $counts = $entityCounts->get($ruleset->id, collect())->mapWithKeys(
                    static fn (object $row): array => [(string) $row->entity_type => (int) $row->aggregate],
                );
                $sources = $sourceMetadata->get($ruleset->id, collect());
                $data = get_object_vars($ruleset);
                unset($data['activation_id']);
                $data['dataset_classification'] ??= 'unavailable';
                $data['provenance_status'] ??= 'pending';
                $data['compatibility_status'] ??= 'unavailable';

                return [
                    ...$data,
                    'active' => $ruleset->activation_id !== null,
                    'sources' => $sources->pluck('source_code')->unique()->sort()->implode(', '),
                    'source_checksums' => $sources->pluck('checksum_sha256')->unique()->sort()->implode(', '),
                    'import_failures' => $sources->filter(static fn (object $row): bool => in_array($row->run_status, ['failed', 'quarantined'], true))
                        ->pluck('failure_code')->filter()->unique()->sort()->implode(', '),
                    'entity_counts' => $counts,
                ];
            }),
        ]);
    }

    public function system(Request $request, SourceRegistry $registry, ExternalSourceAdapterCatalog $adapters): Response
    {
        $adapterStatuses = collect($adapters->all())->mapWithKeys(static function ($adapter): array {
            $status = $adapter->status();

            return [$status->sourceCode => $status->jsonSerialize()];
        });
        $sources = collect($registry->all())->map(function ($record) use ($adapterStatuses): array {
            $lastAttempt = DB::table('external_source_sync_runs')->where('source_key', $record->code)->latest('started_at')->first();
            $lastSuccess = DB::table('external_source_sync_runs')->where('source_key', $record->code)->whereIn('status', ['success', 'succeeded'])->latest('completed_at')->first();
            $lastReport = DB::table('source_import_reports')->where('source_code', $record->code)->latest('created_at')->first();
            $lastUpdate = DB::table('source_update_observations')->where('source_code', $record->code)->latest('checked_at')->first();
            $attempt = $lastAttempt === null ? [] : get_object_vars($lastAttempt);
            $success = $lastSuccess === null ? [] : get_object_vars($lastSuccess);
            $report = $lastReport === null ? [] : get_object_vars($lastReport);
            $attemptStatus = is_string($attempt['status'] ?? null) ? $attempt['status'] : null;

            return [
                ...$record->jsonSerialize(),
                'adapter' => $adapterStatuses->get($record->code),
                'last_attempt_at' => $attempt['started_at'] ?? null,
                'last_success_at' => $success['completed_at'] ?? null,
                'last_error' => in_array($attemptStatus, ['failed', 'quarantined'], true) ? ($attempt['failure_code'] ?? null) : null,
                'dataset_edition' => $report['game_edition'] ?? null,
                'ruleset_target' => $report['ruleset_target'] ?? null,
                'checksum' => $report['normalized_checksum_sha256'] ?? null,
                'records_imported' => $report['records_imported'] ?? 0,
                'records_rejected' => $report['records_rejected'] ?? 0,
                'import_status' => $report['status'] ?? null,
                'policy_status' => $report['policy_status'] ?? $record->governanceStatus,
                'update_status' => $lastUpdate?->status,
                'update_checked_at' => $lastUpdate?->checked_at,
            ];
        });

        return Inertia::render('Admin/System', [
            'failedJobs' => DB::table('failed_jobs')->count(),
            'killSwitches' => DB::table('policy_kill_switches')->where('active', true)->get(['scope', 'source_id', 'capability', 'reason']),
            'sourceRuns' => DB::table('external_source_sync_runs')->latest('started_at')->limit(20)->get(['source_key', 'status', 'league', 'category', 'started_at', 'completed_at', 'failure_code']),
            'sources' => $sources,
            'canTriggerImports' => $request->user()?->isSuperAdmin() === true,
            'release' => config('deployment.release_sha'),
        ]);
    }
}

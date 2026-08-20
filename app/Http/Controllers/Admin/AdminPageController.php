<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Lootwright\Domain\PoeCatalog\Character\Poe1CharacterCatalog;
use Lootwright\Domain\PoeCatalog\Character\Poe2CharacterCatalog;

final class AdminPageController extends Controller
{
    public function audit(): Response
    {
        return Inertia::render('Admin/AuditLog', ['entries' => DB::table('admin_audit_logs')->join('users as actors', 'actors.id', '=', 'admin_audit_logs.actor_user_id')->latest('admin_audit_logs.created_at')->paginate(50, ['admin_audit_logs.id', 'admin_audit_logs.action', 'admin_audit_logs.reason', 'admin_audit_logs.metadata', 'admin_audit_logs.created_at', 'actors.email as actor_email'])]);
    }

    public function catalog(): Response
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

    public function system(): Response
    {
        return Inertia::render('Admin/System', ['failedJobs' => DB::table('failed_jobs')->count(), 'killSwitches' => DB::table('policy_kill_switches')->where('active', true)->get(['scope', 'source_id', 'capability', 'reason']), 'sourceRuns' => DB::table('external_source_sync_runs')->latest('started_at')->limit(20)->get(['source_key', 'status', 'league', 'category', 'started_at', 'completed_at', 'failure_code']), 'release' => config('deployment.release_sha')]);
    }
}

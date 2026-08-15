<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;

final class PruneRetainedUserData extends Command
{
    protected $signature = 'security:prune-retained-data {--limit=500}';

    protected $description = 'Delete expired user-linked analyses, sessions, caches, and AI metadata';

    public function handle(ArtifactStorage $storage): int
    {
        $limit = max(1, min(5_000, (int) $this->option('limit')));
        $artifactRows = DB::table('builds')
            ->join('build_artifacts', 'build_artifacts.id', '=', 'builds.artifact_id')
            ->whereNotNull('builds.retention_until')
            ->where('builds.retention_until', '<=', now())
            ->orderBy('builds.retention_until')
            ->limit($limit)
            ->get(['build_artifacts.id', 'build_artifacts.blob_key']);

        foreach ($artifactRows as $row) {
            if (is_string($row->blob_key) && $row->blob_key !== '') {
                $storage->delete($row->blob_key);
            }

            DB::table('build_artifacts')->where('id', (string) $row->id)->delete();
        }

        $auditCutoff = now()->subDays(max(1, (int) config('security.retention.ai_audit_days', 30)));
        $audits = DB::table('ai_request_audits')->where('created_at', '<', $auditCutoff)->delete();
        $cacheKeys = DB::table('ai_response_cache_keys')->where('expires_at', '<=', now())->delete();
        $budgetCounters = DB::table('ai_budget_counters')
            ->whereIn('scope_type', ['user_daily', 'ip_daily'])
            ->where('period_end', '<', $auditCutoff)
            ->delete();

        DB::table('privacy_sessions')
            ->where('status', 'active')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'updated_at' => now()]);
        $sessionCutoff = now()->subDays(max(1, (int) config('security.retention.deleted_session_tombstone_days', 7)));
        $sessions = DB::table('privacy_sessions')
            ->whereIn('status', ['deleted', 'expired'])
            ->where('updated_at', '<', $sessionCutoff)
            ->delete();

        $this->info(sprintf(
            'Pruned %d artifact(s), %d AI audit(s), %d cache key(s), %d budget counter(s), and %d session tombstone(s).',
            $artifactRows->count(),
            $audits,
            $cacheKeys,
            $budgetCounters,
            $sessions,
        ));

        return self::SUCCESS;
    }
}

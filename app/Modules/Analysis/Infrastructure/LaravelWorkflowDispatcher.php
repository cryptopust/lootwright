<?php

namespace App\Modules\Analysis\Infrastructure;

use App\Modules\Analysis\Jobs\ParseBuildArtifactJob;
use App\Modules\Analysis\Jobs\RunDeterministicAnalysisJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\WorkflowDispatcher;
use Lootwright\Domain\Shared\Game\GameEdition;
use Throwable;

final class LaravelWorkflowDispatcher implements WorkflowDispatcher
{
    public function parse(string $artifactId, GameEdition $edition): void
    {
        $this->recordAndDispatch('build.parse', $artifactId, $edition, null);
    }

    public function analyze(string $analysisId, GameEdition $edition, ?string $rulesetChecksumSha256 = null): void
    {
        $this->recordAndDispatch('analysis.run', $analysisId, $edition, $rulesetChecksumSha256);
    }

    public function flushPending(int $limit = 100): int
    {
        $count = 0;
        $rows = DB::table('workflow_outbox')
            ->where('status', 'pending')
            ->where('attempts', '<', 5)
            ->where('available_at', '<=', now())
            ->orderBy('created_at')
            ->limit(max(1, min($limit, 500)))
            ->get();

        foreach ($rows as $row) {
            if ($this->dispatchRecorded((string) $row->id)) {
                $count++;
            }
        }

        return $count;
    }

    private function recordAndDispatch(
        string $topic,
        string $aggregateId,
        GameEdition $edition,
        ?string $rulesetChecksumSha256,
    ): void {
        DB::table('workflow_outbox')->insertOrIgnore([
            'id' => (string) Str::uuid7(),
            'topic' => $topic,
            'aggregate_id' => $aggregateId,
            'game_edition' => $edition->value,
            'ruleset_checksum_sha256' => $rulesetChecksumSha256,
            'status' => 'pending',
            'available_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('workflow_outbox')
            ->where('topic', $topic)
            ->where('aggregate_id', $aggregateId)
            ->first();

        if ($row !== null && $row->status === 'pending') {
            $id = (string) $row->id;

            if (DB::transactionLevel() > 0) {
                DB::afterCommit(function () use ($id): void {
                    try {
                        $this->dispatchRecorded($id);
                    } catch (TransientWorkflowFailure $exception) {
                        Log::warning('workflow_outbox_dispatch_deferred', [
                            'outbox_id' => $id,
                            'exception_type' => $exception::class,
                        ]);
                    }
                });
            } else {
                $this->dispatchRecorded($id);
            }
        }
    }

    private function dispatchRecorded(string $id): bool
    {
        $failure = null;
        $dispatched = DB::transaction(function () use ($id, &$failure): bool {
            $row = DB::table('workflow_outbox')->where('id', $id)->lockForUpdate()->first();

            if ($row === null || $row->status !== 'pending' || (int) $row->attempts >= 5) {
                return false;
            }

            try {
                $edition = GameEdition::from((string) $row->game_edition);
                $checksum = is_string($row->ruleset_checksum_sha256) ? $row->ruleset_checksum_sha256 : null;

                match ((string) $row->topic) {
                    'build.parse' => ParseBuildArtifactJob::dispatch((string) $row->aggregate_id, $edition)
                        ->onQueue('build-parsing'),
                    'analysis.run' => RunDeterministicAnalysisJob::dispatch((string) $row->aggregate_id, $edition, $checksum)
                        ->onQueue('deterministic-analysis'),
                    default => throw new \UnexpectedValueException('Unknown workflow outbox topic.'),
                };

                DB::table('workflow_outbox')->where('id', $id)->update([
                    'status' => 'dispatched',
                    'attempts' => DB::raw('attempts + 1'),
                    'dispatched_at' => now(),
                    'last_error_code' => null,
                    'updated_at' => now(),
                ]);

                return true;
            } catch (Throwable $exception) {
                $attempts = ((int) $row->attempts) + 1;
                DB::table('workflow_outbox')->where('id', $id)->update([
                    'status' => $exception instanceof \UnexpectedValueException || $attempts >= 5 ? 'failed' : 'pending',
                    'attempts' => $attempts,
                    'available_at' => now()->addSeconds(min(300, 30 * (2 ** ($attempts - 1)))),
                    'last_error_code' => $exception::class,
                    'updated_at' => now(),
                ]);
                $failure = $exception;

                return false;
            }
        }, 3);

        if ($failure instanceof Throwable) {
            throw new TransientWorkflowFailure('The durable workflow dispatch is temporarily unavailable.');
        }

        return $dispatched;
    }
}

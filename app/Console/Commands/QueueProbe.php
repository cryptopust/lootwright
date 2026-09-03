<?php

namespace App\Console\Commands;

use App\Modules\Operations\Jobs\QueueProbeJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class QueueProbe extends Command
{
    /** @var string */
    protected $signature = 'lootwright:queue:probe
        {action : dispatch or status}
        {probe? : Opaque probe UUID returned by dispatch}
        {--queue=deterministic-analysis : build-parsing or deterministic-analysis}';

    /** @var string */
    protected $description = 'Dispatch or inspect a privacy-safe operator queue probe';

    public function handle(): int
    {
        $action = (string) $this->argument('action');

        return match ($action) {
            'dispatch' => $this->dispatchProbe(),
            'status' => $this->showStatus(),
            default => $this->invalid('Action must be dispatch or status.'),
        };
    }

    private function dispatchProbe(): int
    {
        $queue = (string) $this->option('queue');
        if (! in_array($queue, ['build-parsing', 'deterministic-analysis'], true)) {
            return $this->invalid('Queue must be build-parsing or deterministic-analysis.');
        }

        $probeId = (string) Str::uuid7();
        Cache::put(QueueProbeJob::cacheKey($probeId), [
            'probe_id' => $probeId,
            'queue' => $queue,
            'state' => 'queued',
            'queued_at' => now()->toIso8601String(),
        ], now()->addHour());

        QueueProbeJob::dispatch($probeId)->onQueue($queue);
        $this->line(json_encode(['probe_id' => $probeId, 'queue' => $queue, 'state' => 'queued'], JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    private function showStatus(): int
    {
        $probeId = (string) $this->argument('probe');
        if (! Str::isUuid($probeId)) {
            return $this->invalid('A valid probe UUID is required.');
        }

        $state = Cache::get(QueueProbeJob::cacheKey($probeId));
        if (! is_array($state)) {
            return $this->invalid('Probe was not found or has expired.');
        }

        $this->line(json_encode($state, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }

    private function invalid(string $message): int
    {
        $this->error($message);

        return self::FAILURE;
    }
}

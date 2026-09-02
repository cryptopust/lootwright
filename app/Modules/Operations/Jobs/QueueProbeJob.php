<?php

namespace App\Modules\Operations\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

final class QueueProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 15;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $probeId) {}

    public function handle(): void
    {
        $key = self::cacheKey($this->probeId);
        $state = Cache::get($key);

        if (! is_array($state)) {
            return;
        }

        $state['state'] = 'processing';
        $state['started_at'] = now()->toIso8601String();
        Cache::put($key, $state, now()->addHour());

        $state['state'] = 'completed';
        $state['completed_at'] = now()->toIso8601String();
        Cache::put($key, $state, now()->addHour());
    }

    public static function cacheKey(string $probeId): string
    {
        return 'operator:queue-probe:'.$probeId;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ReadinessController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->databaseIsReady(),
        ];

        if ($this->redisIsRequired()) {
            $checks['redis'] = $this->redisIsReady();
        }

        $ready = ! in_array(false, $checks, true);

        $payload = [
            'status' => $ready ? 'ready' : 'unavailable',
            'checks' => array_map(
                static fn (bool $check): string => $check ? 'ok' : 'failed',
                $checks,
            ),
        ];

        if ($request->boolean('detail')) {
            $components = $this->detailedComponents($checks);
            $payload['components'] = $components;
            $payload['status'] = in_array('FAILED', $components, true)
                ? 'unavailable'
                : 'ready';
        }

        return response()->json($payload, $payload['status'] === 'ready' ? 200 : 503);
    }

    /**
     * @param  array<string, bool>  $checks
     * @return array<string, string>
     */
    private function detailedComponents(array $checks): array
    {
        $database = $checks['database'] ?? false;
        $redis = $checks['redis'] ?? null;

        return [
            'APP' => $this->applicationStatus(),
            'DATABASE' => $database ? 'HEALTHY' : 'FAILED',
            'CACHE' => $this->cacheStatus($database, $redis),
            'QUEUE' => $this->queueStatus($database, $redis),
            'STORAGE' => $this->storageStatus(),
            'ACTIVE_POE1_RULESET' => $this->activeRulesetStatus('poe1'),
            'ACTIVE_POE2_RULESET' => $this->activeRulesetStatus('poe2'),
            'MARKET_PROVIDER' => (bool) config('external-sources.poe_ninja.enabled') ? 'DEGRADED' : 'DISABLED',
            'AI_PROVIDER' => (bool) config('ai.enabled') ? 'DEGRADED' : 'DISABLED',
        ];
    }

    private function applicationStatus(): string
    {
        return config('app.env') === 'production' && ! config('app.debug')
            ? 'HEALTHY'
            : 'DEGRADED';
    }

    private function cacheStatus(bool $database, ?bool $redis): string
    {
        if ($redis === false) {
            return 'FAILED';
        }

        $driver = (string) config('cache.stores.'.config('cache.default').'.driver', config('cache.default'));

        if ($driver === 'database' && ! $database) {
            return 'FAILED';
        }

        return $driver === 'null' ? 'DISABLED' : 'HEALTHY';
    }

    private function queueStatus(bool $database, ?bool $redis): string
    {
        if ($redis === false) {
            return 'FAILED';
        }

        $driver = (string) config('queue.connections.'.config('queue.default').'.driver', config('queue.default'));

        if ($driver === 'database' && ! $database) {
            return 'FAILED';
        }

        return match ($driver) {
            'null' => 'DISABLED',
            'sync' => 'HEALTHY',
            default => 'DEGRADED',
        };
    }

    private function storageStatus(): string
    {
        $disk = (string) config('filesystems.default', 'local');
        $artifactDriver = (string) config('filesystems.disks.analysis-artifacts.driver', 'local');

        // Local storage is intentionally non-durable on Laravel Cloud. It is
        // healthy for framework scratch data but degraded for retained artifacts.
        return $disk === 'local' || $artifactDriver === 'local' ? 'DEGRADED' : 'HEALTHY';
    }

    private function activeRulesetStatus(string $edition): string
    {
        try {
            if (! Schema::hasTable('ruleset_activations')) {
                return 'DISABLED';
            }

            // Activation rows are foreign-keyed to immutable, published
            // rulesets by the catalog schema; readiness only needs existence.
            $active = DB::table('ruleset_activations')
                ->where('game_edition', $edition)
                ->exists();

            return $active ? 'HEALTHY' : 'DISABLED';
        } catch (Throwable) {
            return 'FAILED';
        }
    }

    private function databaseIsReady(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function redisIsReady(): bool
    {
        try {
            Redis::command('ping');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function redisIsRequired(): bool
    {
        $cacheStore = config('cache.default');
        $queueConnection = config('queue.default');

        return (is_string($cacheStore)
                && config("cache.stores.{$cacheStore}.driver") === 'redis')
            || config('session.driver') === 'redis'
            || (is_string($queueConnection)
                && config("queue.connections.{$queueConnection}.driver") === 'redis');
    }
}

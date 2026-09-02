<?php

namespace Tests\Feature;

use App\Modules\Operations\Jobs\QueueProbeJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class QueueProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_probe_dispatches_only_scalar_identity_to_selected_queue(): void
    {
        Queue::fake();

        self::assertSame(0, Artisan::call('lootwright:queue:probe', [
            'action' => 'dispatch',
            '--queue' => 'deterministic-analysis',
        ]));

        Queue::assertPushedOn('deterministic-analysis', QueueProbeJob::class, function (QueueProbeJob $job): bool {
            self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $job->probeId);
            self::assertStringNotContainsString('GameRuleset', serialize($job));

            return true;
        });
    }

    public function test_probe_records_processing_and_completion_without_user_data(): void
    {
        $probeId = '01990abc-1234-7000-8000-000000000001';
        Cache::put(QueueProbeJob::cacheKey($probeId), [
            'probe_id' => $probeId,
            'queue' => 'build-parsing',
            'state' => 'queued',
            'queued_at' => now()->toIso8601String(),
        ]);

        (new QueueProbeJob($probeId))->handle();

        $state = Cache::get(QueueProbeJob::cacheKey($probeId));
        self::assertIsArray($state);
        self::assertSame('completed', $state['state']);
        self::assertArrayHasKey('started_at', $state);
        self::assertArrayHasKey('completed_at', $state);
        self::assertSame(['probe_id', 'queue', 'state', 'queued_at', 'started_at', 'completed_at'], array_keys($state));
    }

    public function test_probe_rejects_unknown_queue(): void
    {
        Queue::fake();

        self::assertSame(1, Artisan::call('lootwright:queue:probe', [
            'action' => 'dispatch',
            '--queue' => 'default',
        ]));
        Queue::assertNothingPushed();
    }
}

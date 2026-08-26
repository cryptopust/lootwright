<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ReadinessTest extends TestCase
{
    private const TOKEN = 'test-readiness-token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.readiness.token' => self::TOKEN,
            'cache.default' => 'database',
            'cache.limiter' => 'array',
            'session.driver' => 'database',
            'queue.default' => 'sync',
        ]);
    }

    #[DataProvider('nonRedisQueueDrivers')]
    public function test_database_only_configuration_succeeds_without_contacting_redis(string $queue): void
    {
        config(['queue.default' => $queue]);

        Redis::shouldReceive('command')->never();

        $this->withHeader('X-Lootwright-Readiness-Token', self::TOKEN)
            ->getJson(route('readiness'))
            ->assertOk()
            ->assertExactJson([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                ],
            ]);
    }

    /** @return array<string, array{string}> */
    public static function nonRedisQueueDrivers(): array
    {
        return [
            'initial Cloud sync queue' => ['sync'],
            'database queue' => ['database'],
        ];
    }

    public function test_database_failure_returns_503_without_exception_details(): void
    {
        config([
            'database.default' => 'missing-sensitive-database-connection',
            'cache.default' => 'array',
            'session.driver' => 'array',
        ]);
        Redis::shouldReceive('command')->never();

        $this->withHeader('X-Lootwright-Readiness-Token', self::TOKEN)
            ->getJson(route('readiness'))
            ->assertServiceUnavailable()
            ->assertExactJson([
                'status' => 'unavailable',
                'checks' => [
                    'database' => 'failed',
                ],
            ])
            ->assertDontSee('missing-sensitive-database-connection');
    }

    /** @param array<string, mixed> $configuration */
    #[DataProvider('redisRuntimeComponents')]
    public function test_redis_configuration_causes_redis_to_be_checked(array $configuration): void
    {
        config($configuration);

        Redis::shouldReceive('command')->once()->with('ping')->andReturn('PONG');

        $this->withHeader('X-Lootwright-Readiness-Token', self::TOKEN)
            ->getJson(route('readiness'))
            ->assertOk()
            ->assertExactJson([
                'status' => 'ready',
                'checks' => [
                    'database' => 'ok',
                    'redis' => 'ok',
                ],
            ]);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function redisRuntimeComponents(): array
    {
        return [
            'cache store driver' => [[
                'cache.default' => 'cloud-cache',
                'cache.stores.cloud-cache.driver' => 'redis',
            ]],
            'session driver' => [[
                'session.driver' => 'redis',
            ]],
            'queue connection driver' => [[
                'queue.default' => 'cloud-queue',
                'queue.connections.cloud-queue.driver' => 'redis',
            ]],
        ];
    }

    public function test_redis_failure_returns_503_when_redis_is_required(): void
    {
        config(['queue.default' => 'redis']);

        Redis::shouldReceive('command')->once()->with('ping')->andThrow(new RuntimeException('sensitive redis detail'));

        $this->withHeader('X-Lootwright-Readiness-Token', self::TOKEN)
            ->getJson(route('readiness'))
            ->assertServiceUnavailable()
            ->assertExactJson([
                'status' => 'unavailable',
                'checks' => [
                    'database' => 'ok',
                    'redis' => 'failed',
                ],
            ])
            ->assertDontSee('sensitive redis detail');
    }

    public function test_authenticated_detailed_readiness_exposes_only_bounded_component_statuses(): void
    {
        config([
            'app.env' => 'production',
            'app.debug' => false,
            'filesystems.default' => 'local',
            'external-sources.poe_ninja.enabled' => false,
            'ai.enabled' => false,
        ]);

        $response = $this->withHeader('X-Lootwright-Readiness-Token', self::TOKEN)
            ->getJson(route('readiness', ['detail' => 1]));
        $response
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('components.APP', 'HEALTHY')
            ->assertJsonPath('components.DATABASE', 'HEALTHY')
            ->assertJsonPath('components.CACHE', 'HEALTHY')
            ->assertJsonPath('components.QUEUE', 'HEALTHY')
            ->assertJsonPath('components.STORAGE', 'DEGRADED')
            ->assertJsonPath('components.ACTIVE_POE1_RULESET', 'DISABLED')
            ->assertJsonPath('components.ACTIVE_POE2_RULESET', 'DISABLED')
            ->assertJsonPath('components.MARKET_PROVIDER', 'DISABLED')
            ->assertJsonPath('components.AI_PROVIDER', 'DISABLED');
        self::assertEmpty(array_diff(
            array_values(array_unique($response->json('components'))),
            ['HEALTHY', 'DEGRADED', 'DISABLED', 'FAILED'],
        ));

        $response->assertDontSee('password')->assertDontSee('connection');
    }

    public function test_missing_or_invalid_readiness_token_remains_inaccessible(): void
    {
        Redis::shouldReceive('command')->never();

        $this->getJson(route('readiness'))->assertNotFound();
        $this->withHeader('X-Lootwright-Readiness-Token', 'invalid-token')
            ->getJson(route('readiness'))
            ->assertNotFound();
    }
}

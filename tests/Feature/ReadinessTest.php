<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class ReadinessTest extends TestCase
{
    private const TOKEN = 'test-readiness-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.readiness.token' => self::TOKEN]);
    }

    public function test_authorized_readiness_reports_healthy_dependencies(): void
    {
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([(object) ['value' => 1]]);
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

    public function test_authorized_readiness_fails_closed_without_exception_details(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('sensitive database detail'));
        Redis::shouldReceive('command')->once()->with('ping')->andReturn('PONG');

        $this->withHeader('X-Lootwright-Readiness-Token', self::TOKEN)
            ->getJson(route('readiness'))
            ->assertServiceUnavailable()
            ->assertExactJson([
                'status' => 'unavailable',
                'checks' => [
                    'database' => 'failed',
                    'redis' => 'ok',
                ],
            ])
            ->assertDontSee('sensitive database detail');
    }
}

<?php

namespace Tests\Feature;

use App\Modules\Release\RuntimeMarker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class LiveAcceptanceGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_fixture_runtime_is_never_accepted_as_live_canonical(): void
    {
        self::assertSame(RuntimeMarker::TEST_FIXTURE, RuntimeMarker::current());
        self::assertSame(1, Artisan::call('acceptance:gate'));
    }

    public function test_canonical_marker_still_requires_non_local_environment(): void
    {
        Config::set('analysis-workflow.runtime_mode', RuntimeMarker::PRODUCTION_CANONICAL);

        self::assertSame(1, Artisan::call('acceptance:gate', ['--edition' => 'poe1']));
    }
}

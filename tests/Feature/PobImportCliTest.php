<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PobImportCliTest extends TestCase
{
    public function test_local_fixture_command_prints_safe_json_without_database_queries(): void
    {
        $queryCount = 0;
        DB::listen(function (QueryExecuted $event) use (&$queryCount): void {
            $queryCount++;
        });

        $exitCode = Artisan::call('pob:import-fixture', [
            'path' => dirname(__DIR__).'/Fixtures/Pob/poe1-minimal.xml',
        ]);
        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('"edition":"poe1"', $output);
        self::assertStringNotContainsString('<script>', $output);
        self::assertSame(0, $queryCount);
    }

    public function test_local_fixture_command_fails_closed_when_format_evidence_expires(): void
    {
        CarbonImmutable::setTestNow('2026-11-12T00:00:00Z');

        try {
            $exitCode = Artisan::call('pob:import-fixture', [
                'path' => dirname(__DIR__).'/Fixtures/Pob/poe1-minimal.xml',
            ]);
            $output = Artisan::output();

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('"status":"policy_denied"', $output);
            self::assertStringContainsString('"reason":"expired_evidence"', $output);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_local_fixture_command_fails_closed_before_format_evidence_is_effective(): void
    {
        CarbonImmutable::setTestNow('2026-08-14T13:15:59Z');

        try {
            $exitCode = Artisan::call('pob:import-fixture', [
                'path' => dirname(__DIR__).'/Fixtures/Pob/poe1-minimal.xml',
            ]);
            $output = Artisan::output();

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('"status":"policy_denied"', $output);
            self::assertStringContainsString('"reason":"missing_evidence"', $output);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_local_fixture_command_rejects_stream_wrappers_and_network_shares_before_file_access(): void
    {
        foreach (['https://example.invalid/build', 'phar://fixture.phar/build', '\\\\server\\share\\build.txt'] as $path) {
            $exitCode = Artisan::call('pob:import-fixture', ['path' => $path]);

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('not a URL, stream wrapper, or network share', Artisan::output());
        }
    }

    public function test_local_fixture_command_honors_the_environment_global_kill_switch(): void
    {
        Config::set('policy.global_kill_switch', true);

        try {
            $exitCode = Artisan::call('pob:import-fixture', [
                'path' => dirname(__DIR__).'/Fixtures/Pob/poe1-minimal.xml',
            ]);
            $output = Artisan::output();

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('"status":"policy_denied"', $output);
            self::assertStringContainsString('"reason":"global_kill_switch"', $output);
        } finally {
            Config::set('policy.global_kill_switch', false);
        }
    }

    public function test_local_fixture_command_never_reads_past_the_input_limit(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lootwright-pob-');

        if (! is_string($path)) {
            self::fail('Unable to create a temporary fixture path.');
        }

        try {
            file_put_contents($path, str_repeat('A', 1_048_577));
            $exitCode = Artisan::call('pob:import-fixture', ['path' => $path]);

            self::assertSame(1, $exitCode);
            self::assertStringContainsString('exceeds the 1 MiB input limit', Artisan::output());
        } finally {
            unlink($path);
        }
    }
}

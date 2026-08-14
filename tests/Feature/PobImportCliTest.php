<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Artisan;
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
}

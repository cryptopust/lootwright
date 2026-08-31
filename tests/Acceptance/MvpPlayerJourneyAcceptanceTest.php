<?php

namespace Tests\Acceptance;

use App\Models\User;
use App\Modules\Release\MvpReleaseDashboard;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;
use Lootwright\Application\Workflow\UseCases\ParseAndNormalizeBuild;
use Lootwright\Application\Workflow\UseCases\RunDeterministicAnalysis;
use Tests\TestCase;

/**
 * Automated release-ledger acceptance. Representative parser fixtures prove
 * safety and determinism elsewhere; only a real staging player run may satisfy
 * the dashboard's real-build and staging gates.
 */
final class MvpPlayerJourneyAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private const REVISION = '8bd138b32ea2631455cac5935bfab089f826094f';

    private const PLAYER_QUESTIONS = [
        'I have 20 div. What should I upgrade first?',
        'My clear is good but boss damage is bad.',
        'I want more defence without replacing my main weapon.',
        'I want to push deeper Delve.',
        'Which ring should I replace?',
        'What changes give the best value for my budget?',
        'What support gem is currently hurting the build?',
        'Why am I dying?',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
    }

    public function test_release_gate_fails_closed_without_observed_player_products(): void
    {
        $report = $this->app->make(MvpReleaseDashboard::class)->report();

        self::assertSame('FAIL', $report['overall_status']);
        self::assertSame('poe1', $report['active_release_edition']);
        self::assertSame('FAIL', $report['editions']['poe1']['status']);
        self::assertSame('FAIL', $report['editions']['poe2']['status']);
        self::assertContains(
            'Production upgrade planı: Production analiz çıktısında recommendation yok.',
            $report['editions']['poe1']['blockers'],
        );
        self::assertContains(
            'Production manual Trade recipe: Production analiz çıktısında manual Trade recipe yok.',
            $report['editions']['poe1']['blockers'],
        );
    }

    public function test_representative_poe1_artifact_reaches_traceable_findings_and_ranked_recommendations(): void
    {
        Queue::fake();
        $fixture = base_path('tests/Fixtures/ggg/passive-tree-8bd138b-reduced.json');
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $fixture));
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $fixture, '--activate' => true]), Artisan::output());

        $user = User::factory()->create(['email_verified_at' => now()]);
        $xml = file_get_contents(base_path('tests/Fixtures/Pob/poe1-minimal.xml'));
        self::assertIsString($xml);
        $xml = str_replace(
            ['targetVersion="3_0"', 'classId="1" ascendClassId="2"', 'className="Fixture Class" ascendClassName="Fixture Ascendancy"'],
            ['targetVersion="3.29.1"', 'classId="2" ascendClassId="Deadeye"', 'className="Ranger" ascendClassName="Deadeye"'],
            $xml,
        );
        $ruleset = DB::table('ruleset_versions')->sole();
        $response = $this->actingAs($user)->postJson('/api/analyses', [
            'game' => 'poe1',
            'locale' => 'en-US',
            'artifact_type' => 'pob',
            'artifact' => $xml,
            'storage_consent' => true,
            'goals' => self::PLAYER_QUESTIONS,
            'budget_amount' => '20',
            'budget_currency' => 'DIVINE',
            'ruleset_id' => $ruleset->id,
            'ruleset_version' => $ruleset->version,
            'ruleset_checksum_sha256' => $ruleset->checksum_sha256,
        ], ['Idempotency-Key' => str_repeat('p', 32)])->assertAccepted();
        $artifactId = $response->json('artifact_id');
        $analysisId = $response->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);
        $parameters = DB::table('analyses')->where('id', $analysisId)->value('parameters_snapshot_encrypted');
        self::assertIsString($parameters);
        $parameters = json_decode(Crypt::decryptString($parameters), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(self::PLAYER_QUESTIONS, $parameters['goals']);

        $importStarted = hrtime(true);
        $this->app->make(ParseAndNormalizeBuild::class)->handle($artifactId);
        $importMs = (int) ceil((hrtime(true) - $importStarted) / 1_000_000);
        $analysisStarted = hrtime(true);
        $this->app->make(RunDeterministicAnalysis::class)->handle($analysisId);
        $analysisMs = (int) ceil((hrtime(true) - $analysisStarted) / 1_000_000);

        self::assertLessThan(10_000, $importMs);
        self::assertLessThan(10_000, $analysisMs);
        $this->assertDatabaseHas('analyses', ['id' => $analysisId, 'state' => 'completed']);
        self::assertGreaterThan(0, DB::table('analysis_findings')->where('analysis_id', $analysisId)->count());
        self::assertGreaterThan(0, DB::table('analysis_recommendations')->where('analysis_id', $analysisId)->count(), 'recommendations missing');
        self::assertGreaterThan(0, DB::table('manual_trade_recipes')->where('analysis_id', $analysisId)->count(), 'recipes missing');

        $finding = DB::table('analysis_findings')->where('analysis_id', $analysisId)->orderBy('sequence')->first();
        self::assertNotNull($finding);
        $payload = json_decode(Crypt::decryptString($finding->payload_encrypted), true, flags: JSON_THROW_ON_ERROR);
        foreach (['finding_id', 'rule_id', 'ruleset_version', 'evidence', 'source_provenance', 'explanation_trace'] as $field) {
            self::assertArrayHasKey($field, $payload);
        }
        $recommendation = DB::table('analysis_recommendations')->where('analysis_id', $analysisId)->orderBy('sequence')->first();
        self::assertNotNull($recommendation);
        $recommendationPayload = json_decode(Crypt::decryptString($recommendation->payload_encrypted), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(
            ['constraints', 'evidence', 'finding', 'market_evidence', 'recommendation', 'rule', 'upgrade_candidate', 'user_goal'],
            array_keys($recommendationPayload['decision_trace']),
        );
        self::assertArrayHasKey('latencies_ms', json_decode(Crypt::decryptString(DB::table('analyses')->where('id', $analysisId)->value('output_snapshot_encrypted')), true, flags: JSON_THROW_ON_ERROR));

        $report = $this->app->make(MvpReleaseDashboard::class)->report();
        $gates = [];
        foreach ($report['editions']['poe1']['gates'] as $gate) {
            $gates[$gate['key']] = $gate;
        }
        self::assertSame('BLOCKED', $gates['real_build_import']['status']);
        self::assertSame('PASS', $gates['deterministic_findings']['status']);
        self::assertSame('PASS', $gates['upgrade_planner']['status']);
        self::assertSame('PASS', $gates['recommendation_trace']['status']);
        self::assertSame('FAIL', $gates['trade_recipes']['status']);
    }

    public function test_release_command_is_machine_readable_and_uses_nonzero_exit_for_fail(): void
    {
        self::assertSame(1, Artisan::call('release:check-mvp', ['--json' => true]));
        $output = Artisan::output();

        self::assertStringContainsString('"overall_status": "FAIL"', $output);
        self::assertStringContainsString('"poe1"', $output);
        self::assertStringContainsString('"poe2"', $output);
        self::assertStringNotContainsString('POESESSID', $output);
    }

    public function test_release_dashboard_is_admin_only_and_keeps_editions_independent(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);
        $admin = User::factory()->admin()->create([
            'email_verified_at' => now(),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($member)->get('/admin/release')->assertForbidden();
        $this->actingAs($admin)->get('/admin/release')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Release')
                ->where('releaseGate.editions.poe1.edition', 'poe1')
                ->where('releaseGate.editions.poe2.edition', 'poe2')
                ->where('releaseGate.editions.poe1.public', true)
                ->where('releaseGate.editions.poe2.public', false)
                ->where('releaseGate.editions.poe2.status', 'FAIL'));
    }

    public function test_poe2_status_does_not_override_the_active_poe1_verdict(): void
    {
        $report = $this->app->make(MvpReleaseDashboard::class)->report();

        self::assertSame($report['editions']['poe1']['status'], $report['overall_status']);
        self::assertContains($report['editions']['poe2']['status'], ['FAIL', 'PASS', 'PASS_WITH_LIMITATIONS']);
    }

    public function test_public_submission_rejects_inactive_poe2_before_workflow_creation(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->postJson('/api/analyses', [
            'game' => 'poe2',
            'locale' => 'en-US',
            'artifact_type' => 'pob',
            'artifact' => '<PathOfBuilding/>',
            'storage_consent' => true,
        ], ['Idempotency-Key' => str_repeat('e', 32)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('game');
    }
}

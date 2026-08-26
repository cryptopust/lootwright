<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Application\Workflow\UseCases\ParseAndNormalizeBuild;
use Lootwright\Application\Workflow\UseCases\RunDeterministicAnalysis;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Tests\TestCase;

final class ProductionPoe1AnalysisTest extends TestCase
{
    use RefreshDatabase;

    private const REVISION = '8bd138b32ea2631455cac5935bfab089f826094f';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
        Queue::fake();
        $fixture = base_path('tests/Fixtures/ggg/passive-tree-8bd138b-reduced.json');
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $fixture));
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $fixture, '--activate' => true]), Artisan::output());
    }

    public function test_real_pob_fixture_produces_byte_stable_persisted_findings_without_raw_input_logging(): void
    {
        $logged = [];
        foreach (['debug', 'info', 'warning', 'error'] as $level) {
            Log::shouldReceive($level)->zeroOrMoreTimes()->andReturnUsing(static function (mixed ...$arguments) use (&$logged, $level): void {
                $logged[] = [$level, $arguments];
            });
        }
        $user = User::factory()->create();
        $xml = file_get_contents(base_path('tests/Fixtures/Pob/poe1-minimal.xml'));
        self::assertIsString($xml);
        $xml = $this->canonicalPoe1Xml(str_replace('targetVersion="3_0"', 'targetVersion="3.29.1"', $xml));
        $ruleset = DB::table('ruleset_versions')->sole();
        $response = $this->actingAs($user)->postJson('/api/analyses', [
            'game' => 'poe1',
            'locale' => 'en-US',
            'artifact_type' => 'pob',
            'artifact' => $xml,
            'storage_consent' => true,
            'goals' => ['Inspect deterministic fixture evidence.'],
            'ruleset_id' => $ruleset->id,
            'ruleset_version' => $ruleset->version,
            'ruleset_checksum_sha256' => $ruleset->checksum_sha256,
        ], ['Idempotency-Key' => str_repeat('r', 32)])->assertAccepted();
        $artifactId = $response->json('artifact_id');
        $analysisId = $response->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);

        $this->app->make(ParseAndNormalizeBuild::class)->handle($artifactId);
        $this->app->make(RunDeterministicAnalysis::class)->handle($analysisId);

        $this->assertDatabaseHas('analyses', ['id' => $analysisId, 'state' => 'completed']);
        $this->assertDatabaseHas('analysis_findings', ['analysis_id' => $analysisId, 'code' => 'skills.gem.disabled']);
        $this->assertDatabaseHas('analysis_findings', ['analysis_id' => $analysisId, 'code' => 'passive_tree.node.unknown']);
        $first = DB::table('analyses')->where('id', $analysisId)->sole();
        $output = Crypt::decryptString($first->output_snapshot_encrypted);
        self::assertSame($first->output_hash_sha256, hash('sha256', $output));
        self::assertStringNotContainsString($xml, $output);
        self::assertStringNotContainsString('Fixture Item', Crypt::decryptString($first->input_snapshot_encrypted));
        self::assertStringContainsString('GGG-POE1-SKILLTREE-001', $output);
        $encodedLogs = json_encode($logged, JSON_PARTIAL_OUTPUT_ON_ERROR);
        self::assertIsString($encodedLogs);
        self::assertStringNotContainsString($xml, $encodedLogs);
        self::assertStringNotContainsString('Fixture Item', $encodedLogs);
    }

    public function test_production_engine_returns_identical_snapshots_for_same_normalized_input_and_ruleset(): void
    {
        $engine = $this->app->make(DeterministicAnalysisEngine::class);
        $artifact = $this->artifactRecord();
        $analysis = $this->analysisRecord();
        $context = $engine->resolve($analysis, $artifact);

        $first = $engine->run($analysis, $artifact, $context);
        $second = $engine->run($analysis, $artifact, $context);

        self::assertSame($first->inputSnapshot, $second->inputSnapshot);
        self::assertSame($first->outputSnapshot, $second->outputSnapshot);
        self::assertSame($first->outputHashSha256, $second->outputHashSha256);
    }

    private function artifactRecord(): ArtifactRecord
    {
        $xml = file_get_contents(base_path('tests/Fixtures/Pob/poe1-minimal.xml'));
        self::assertIsString($xml);
        $xml = $this->canonicalPoe1Xml(str_replace('targetVersion="3_0"', 'targetVersion="3.29.1"', $xml));
        $result = app(PolicyGatedPobImporter::class)->handle($xml, false)->result;
        $normalized = CanonicalJson::encode($result);

        return new ArtifactRecord(
            '01890f47-0f7d-7a2b-8c3d-1234567890ac', 'owner', '01890f47-0f7d-7a2b-9c3d-1234567890ab', GameEdition::Poe1, 'pob', 'deleted', hash('sha256', $xml), AnalysisState::Completed, 'pob1', '1.0.0', $normalized, hash('sha256', $normalized), '3.29.1', null,
        );
    }

    private function analysisRecord(): AnalysisRecord
    {
        return new AnalysisRecord(
            '01890f47-0f7d-7a2b-9c3d-1234567890ab', '01890f47-0f7d-7a2b-8c3d-1234567890ac', 'owner', GameEdition::Poe1, 1, AnalysisState::Processing, '{}', hash('sha256', '{}'),
        );
    }

    private function canonicalPoe1Xml(string $xml): string
    {
        return str_replace(
            ['classId="1" ascendClassId="2"', 'className="Fixture Class" ascendClassName="Fixture Ascendancy"'],
            ['classId="2" ascendClassId="Deadeye"', 'className="Ranger" ascendClassName="Deadeye"'],
            $xml,
        );
    }
}

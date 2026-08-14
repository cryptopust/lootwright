<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Analysis\Jobs\ParseBuildArtifactJob;
use App\Modules\Analysis\Jobs\RunDeterministicAnalysisJob;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Lootwright\Application\AIGateway\DTO\AnalysisExplanationRequest;
use Lootwright\Application\AIGateway\DTO\IntentExtractionRequest;
use Lootwright\Application\AIGateway\Ports\IntentExtractor;
use Lootwright\Application\AIGateway\Ports\ResultExplainer;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\AnalysisRecord;
use Lootwright\Application\Workflow\DTO\ArtifactRecord;
use Lootwright\Application\Workflow\DTO\DeterministicAnalysisSnapshot;
use Lootwright\Application\Workflow\DTO\ParsedArtifact;
use Lootwright\Application\Workflow\DTO\ResolvedAnalysisContext;
use Lootwright\Application\Workflow\DTO\SubmitBuildArtifactCommand;
use Lootwright\Application\Workflow\Exception\PolicyBlocked;
use Lootwright\Application\Workflow\Exception\TerminalWorkflowFailure;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\AnalysisPolicyGate;
use Lootwright\Application\Workflow\Ports\ArtifactParser;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Application\Workflow\UseCases\ExplainPolicyDecision;
use Lootwright\Application\Workflow\UseCases\ParseAndNormalizeBuild;
use Lootwright\Application\Workflow\UseCases\RunDeterministicAnalysis;
use Lootwright\Application\Workflow\UseCases\SubmitBuildArtifact;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\PolicyVersion;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Locale;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Throwable;

class AnalysisWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private FakeIntentExtractor $intentExtractor;

    private FakeResultExplainer $resultExplainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
        Storage::fake('analysis-artifacts');
        Queue::fake();
        $this->intentExtractor = new FakeIntentExtractor;
        $this->resultExplainer = new FakeResultExplainer;
        $this->app->instance(IntentExtractor::class, $this->intentExtractor);
        $this->app->instance(ResultExplainer::class, $this->resultExplainer);
    }

    public function test_full_workflow_moves_through_queued_processing_and_completed_with_immutable_hashes(): void
    {
        $user = User::factory()->create();
        $submission = $this->submit($user, str_repeat('a', 32));
        $artifactId = $submission->json('artifact_id');
        $analysisId = $submission->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);

        $submission->assertAccepted()->assertJsonPath('status', 'queued');
        Queue::assertPushed(ParseBuildArtifactJob::class, 1);
        $this->assertDatabaseHas('build_artifacts', ['id' => $artifactId, 'state' => 'queued']);
        $this->assertDatabaseHas('analyses', ['id' => $analysisId, 'state' => 'queued']);

        $this->app->instance(ArtifactParser::class, new FakeArtifactParser($this->parsed()));
        $this->app->make(ParseAndNormalizeBuild::class)->handle($artifactId);
        $this->assertDatabaseHas('build_artifacts', [
            'id' => $artifactId,
            'state' => 'completed',
            'adapter_key' => 'pob1-fixture',
            'parser_version' => '1.0.0',
        ]);
        $this->assertDatabaseHas('analyses', ['id' => $analysisId, 'state' => 'queued']);
        Queue::assertPushed(RunDeterministicAnalysisJob::class, 1);

        $engine = new FakeDeterministicAnalysisEngine;
        $policy = new AllowAnalysisPolicyGate;
        $this->app->instance(DeterministicAnalysisEngine::class, $engine);
        $this->app->instance(AnalysisPolicyGate::class, $policy);
        $this->app->make(RunDeterministicAnalysis::class)->handle($analysisId);

        $row = DB::table('analyses')->where('id', $analysisId)->first();
        self::assertNotNull($row);
        self::assertSame('completed', $row->state);
        self::assertSame(hash('sha256', $engine->lastInput), $row->input_hash_sha256);
        self::assertSame(hash('sha256', $engine->lastOutput), $row->output_hash_sha256);
        self::assertSame(str_repeat('b', 64), $row->ruleset_checksum_sha256);
        self::assertSame(1, $policy->authorizations);
        self::assertSame(0, $this->intentExtractor->calls);
        self::assertSame(0, $this->resultExplainer->calls);

        $this->actingAs($user)->getJson('/api/analyses/'.$analysisId)
            ->assertOk()
            ->assertJsonPath('analysis.state', 'completed')
            ->assertJsonPath('analysis.ruleset.version', '1.0.0')
            ->assertJsonPath('analysis.output.analysis_id', $analysisId);
    }

    public function test_duplicate_submissions_replay_one_database_record_one_blob_and_one_job(): void
    {
        $user = User::factory()->create();
        $key = str_repeat('d', 32);
        $first = $this->submit($user, $key)->assertAccepted();
        $second = $this->submit($user, $key)->assertOk();

        $second->assertJsonPath('idempotent_replay', true)
            ->assertJsonPath('artifact_id', $first->json('artifact_id'))
            ->assertJsonPath('analysis_id', $first->json('analysis_id'));
        $this->assertDatabaseCount('build_artifacts', 1);
        $this->assertDatabaseCount('analyses', 1);
        Queue::assertPushed(ParseBuildArtifactJob::class, 1);
        self::assertCount(1, Storage::disk('analysis-artifacts')->allFiles());

        $this->actingAs($user)->postJson('/api/analyses', $this->payload('different artifact'), [
            'Idempotency-Key' => $key,
        ])->assertConflict()->assertJsonPath('status', 'idempotency_conflict');
    }

    public function test_concurrent_winner_between_precheck_and_insert_is_replayed_without_a_second_record(): void
    {
        self::assertSame('sqlite', DB::connection()->getDriverName());
        $user = User::factory()->create();
        $ownerId = (string) $user->getAuthIdentifier();
        $idempotencyKey = str_repeat('w', 32);
        $applicationKey = config('app.key');
        self::assertIsString($applicationKey);
        $ownerHash = hash_hmac('sha256', "analysis-owner\0".$ownerId, $applicationKey);
        $idempotencyHash = hash_hmac(
            'sha256',
            "analysis-idempotency\0".$ownerHash."\0".$idempotencyKey,
            $applicationKey,
        );
        $parameters = new AnalysisParameters(['Improve the deterministic fixture.'], null, null);
        $parametersSnapshot = $parameters->canonicalJson();
        $winnerArtifactId = '01890f47-0f7d-7a2b-bc3d-1234567890ab';
        $winnerAnalysisId = '01890f47-0f7d-7a2b-cc3d-1234567890ab';
        $idempotencySql = $this->sqlLiteral($idempotencyHash);
        $artifactIdSql = $this->sqlLiteral($winnerArtifactId);
        $analysisIdSql = $this->sqlLiteral($winnerAnalysisId);
        $blobKeySql = $this->sqlLiteral('build-artifacts/'.$winnerArtifactId.'.payload');
        $parametersSql = $this->sqlLiteral(Crypt::encryptString($parametersSnapshot));
        $parametersHashSql = $this->sqlLiteral(hash('sha256', $parametersSnapshot));

        DB::connection()->getPdo()->exec(<<<SQL
            CREATE TRIGGER simulate_concurrent_submission
            BEFORE INSERT ON build_artifacts
            WHEN NEW.idempotency_key_hash = {$idempotencySql}
            BEGIN
                INSERT INTO build_artifacts (
                    id, owner_id_hash, idempotency_key_hash, game_edition, locale,
                    artifact_type, blob_key, artifact_hash_sha256, artifact_bytes,
                    state, processing_attempts, raw_expires_at, created_at, updated_at
                ) VALUES (
                    {$artifactIdSql}, NEW.owner_id_hash, NEW.idempotency_key_hash,
                    NEW.game_edition, NEW.locale, NEW.artifact_type, {$blobKeySql},
                    NEW.artifact_hash_sha256, NEW.artifact_bytes, 'queued', 0,
                    NEW.raw_expires_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                );
                INSERT INTO analyses (
                    id, artifact_id, owner_id_hash, game_edition, version, state,
                    parameters_snapshot_encrypted, parameters_hash_sha256,
                    processing_attempts, created_at, updated_at
                ) VALUES (
                    {$analysisIdSql}, {$artifactIdSql}, NEW.owner_id_hash,
                    NEW.game_edition, 1, 'queued', {$parametersSql},
                    {$parametersHashSql}, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                );
            END
            SQL);

        try {
            $locale = Locale::from('en-US')->value();
            self::assertInstanceOf(Locale::class, $locale);
            $receipt = $this->app->make(SubmitBuildArtifact::class)->handle(new SubmitBuildArtifactCommand(
                $ownerId,
                $idempotencyKey,
                GameEdition::Poe1,
                $locale,
                'pob',
                'fixture artifact',
                $parameters,
            ));
        } finally {
            DB::connection()->getPdo()->exec('DROP TRIGGER IF EXISTS simulate_concurrent_submission');
        }

        self::assertTrue($receipt->replayed);
        self::assertSame($winnerArtifactId, $receipt->artifactId);
        self::assertSame($winnerAnalysisId, $receipt->analysisId);
        $this->assertDatabaseCount('build_artifacts', 1);
        $this->assertDatabaseCount('analyses', 1);
        Queue::assertNothingPushed();
        self::assertSame([], Storage::disk('analysis-artifacts')->allFiles());
    }

    public function test_atomic_claims_surface_processing_and_duplicate_job_claims_are_noops(): void
    {
        $user = User::factory()->create();
        $submission = $this->submit($user, str_repeat('j', 32));
        $artifactId = $submission->json('artifact_id');
        $analysisId = $submission->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);
        $repository = $this->app->make(WorkflowRepository::class);

        $claimedArtifact = $repository->claimArtifact($artifactId);
        self::assertNotNull($claimedArtifact);
        self::assertSame(AnalysisState::Processing, $claimedArtifact->state);
        self::assertNull($repository->claimArtifact($artifactId));
        $this->assertDatabaseHas('build_artifacts', ['id' => $artifactId, 'state' => 'processing']);
        $repository->requeueArtifact($artifactId);

        $this->app->instance(ArtifactParser::class, new FakeArtifactParser($this->parsed()));
        $this->app->make(ParseAndNormalizeBuild::class)->handle($artifactId);
        $claimedAnalysis = $repository->claimAnalysis($analysisId);
        self::assertNotNull($claimedAnalysis);
        self::assertSame(AnalysisState::Processing, $claimedAnalysis->state);
        self::assertNull($repository->claimAnalysis($analysisId));
        $this->assertDatabaseHas('analyses', ['id' => $analysisId, 'state' => 'processing']);
        $repository->requeueAnalysis($analysisId);
    }

    public function test_storage_failure_rolls_back_submission_transaction(): void
    {
        $user = User::factory()->create();
        $this->app->instance(ArtifactStorage::class, new FailingArtifactStorage);

        $this->submit($user, str_repeat('r', 32))
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'temporarily_unavailable');

        $this->assertDatabaseCount('build_artifacts', 0);
        $this->assertDatabaseCount('analyses', 0);
        Queue::assertNothingPushed();
    }

    public function test_only_transient_failures_are_requeued_while_invalid_and_policy_denials_are_terminal(): void
    {
        $user = User::factory()->create();

        $transient = $this->submit($user, str_repeat('t', 32));
        $transientId = $transient->json('artifact_id');
        self::assertIsString($transientId);
        $this->app->instance(ArtifactParser::class, new FakeArtifactParser(
            failure: new TransientWorkflowFailure('temporary fixture failure'),
        ));

        try {
            $this->app->make(ParseAndNormalizeBuild::class)->handle($transientId);
            self::fail('Expected the transient failure to be rethrown for the queue worker.');
        } catch (TransientWorkflowFailure) {
            $this->assertDatabaseHas('build_artifacts', ['id' => $transientId, 'state' => 'queued']);
        }

        $invalid = $this->submit($user, str_repeat('i', 32), 'invalid fixture');
        $invalidId = $invalid->json('artifact_id');
        self::assertIsString($invalidId);
        $this->app->instance(ArtifactParser::class, new FakeArtifactParser(
            failure: new TerminalWorkflowFailure('invalid_fixture', 'invalid'),
        ));
        $this->app->make(ParseAndNormalizeBuild::class)->handle($invalidId);
        $this->assertDatabaseHas('build_artifacts', [
            'id' => $invalidId,
            'state' => 'failed',
            'failure_code' => 'invalid_fixture',
        ]);

        $blocked = $this->submit($user, str_repeat('p', 32), 'policy fixture');
        $blockedId = $blocked->json('artifact_id');
        self::assertIsString($blockedId);
        $decision = new CapabilityDecision(
            Capability::Import,
            'USER-PASTED-POB',
            PolicyDecision::Deny,
            PolicyDecisionReason::ExplicitDenial,
            PolicyVersion::baseline(),
            'Denied by fixture policy.',
        );
        $this->app->instance(ArtifactParser::class, new FakeArtifactParser(
            failure: new PolicyBlocked($decision),
        ));
        $this->app->make(ParseAndNormalizeBuild::class)->handle($blockedId);
        $this->assertDatabaseHas('build_artifacts', [
            'id' => $blockedId,
            'state' => 'policy_blocked',
            'failure_code' => 'explicit_denial',
        ]);

        $job = new ParseBuildArtifactJob($transientId);
        self::assertSame(3, $job->tries);
        self::assertSame([10, 30, 90], $job->backoff());
    }

    public function test_clarification_is_a_persisted_state_and_does_not_queue_analysis(): void
    {
        $user = User::factory()->create();
        $submission = $this->submit($user, str_repeat('c', 32));
        $artifactId = $submission->json('artifact_id');
        $analysisId = $submission->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);
        Queue::fake();

        $parsed = $this->parsed([
            ['code' => 'exact_patch_required', 'question' => 'Which exact patch?'],
        ]);
        $this->app->instance(ArtifactParser::class, new FakeArtifactParser($parsed));
        $this->app->make(ParseAndNormalizeBuild::class)->handle($artifactId);

        $this->assertDatabaseHas('analyses', ['id' => $analysisId, 'state' => 'clarification_required']);
        Queue::assertNotPushed(RunDeterministicAnalysisJob::class);
        $this->actingAs($user)->getJson('/api/analyses/'.$analysisId)
            ->assertOk()
            ->assertJsonPath('analysis.state', 'clarification_required')
            ->assertJsonPath('analysis.clarification.clarifications.0.code', 'exact_patch_required');
    }

    public function test_authorization_is_owner_scoped_for_submission_and_retrieval(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->postJson('/api/analyses', $this->payload(), [
            'Idempotency-Key' => str_repeat('u', 32),
        ])->assertUnauthorized();

        $analysisId = $this->submit($owner, str_repeat('o', 32))->json('analysis_id');
        self::assertIsString($analysisId);
        $this->actingAs($other)->getJson('/api/analyses/'.$analysisId)->assertNotFound();
        $this->actingAs($owner)->getJson('/api/analyses/'.$analysisId)->assertOk();
    }

    public function test_reanalysis_creates_an_immutable_version_and_comparison_without_mutating_parent(): void
    {
        $user = User::factory()->create();
        [$artifactId, $firstAnalysisId] = $this->complete($user, str_repeat('v', 32));
        $firstHash = DB::table('analyses')->where('id', $firstAnalysisId)->value('output_hash_sha256');

        $response = $this->actingAs($user)->postJson('/api/analyses/'.$firstAnalysisId.'/reanalyze', [
            'goals' => ['A different deterministic fixture goal.'],
            'budget_amount' => '10',
            'budget_currency' => 'CHAOS',
        ])->assertAccepted()->assertJsonPath('analysis.version', 2);
        $secondAnalysisId = $response->json('analysis.id');
        self::assertIsString($secondAnalysisId);

        $this->app->make(RunDeterministicAnalysis::class)->handle($secondAnalysisId);
        $this->assertDatabaseHas('analyses', [
            'id' => $firstAnalysisId,
            'artifact_id' => $artifactId,
            'version' => 1,
            'output_hash_sha256' => $firstHash,
        ]);
        $this->assertDatabaseHas('analyses', [
            'id' => $secondAnalysisId,
            'artifact_id' => $artifactId,
            'version' => 2,
            'state' => 'completed',
        ]);

        $this->actingAs($user)->getJson("/api/analyses/{$firstAnalysisId}/compare/{$secondAnalysisId}")
            ->assertOk()
            ->assertJsonPath('comparison.inputChanged', true)
            ->assertJsonPath('comparison.outputChanged', true)
            ->assertJsonPath('comparison.rulesetChanged', false);
    }

    public function test_deletion_removes_only_the_owners_artifacts_and_analyses_and_keeps_an_anonymous_count(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownerSubmission = $this->submit($owner, str_repeat('x', 32));
        $otherSubmission = $this->submit($other, str_repeat('y', 32), 'other artifact');
        $ownerArtifact = $ownerSubmission->json('artifact_id');
        $otherArtifact = $otherSubmission->json('artifact_id');
        self::assertIsString($ownerArtifact);
        self::assertIsString($otherArtifact);

        $ownerBlob = DB::table('build_artifacts')->where('id', $ownerArtifact)->value('blob_key');
        self::assertIsString($ownerBlob);
        Storage::disk('analysis-artifacts')->assertExists($ownerBlob);

        $this->actingAs($owner)->deleteJson('/api/user-data')
            ->assertOk()
            ->assertJsonPath('artifacts_deleted', 1)
            ->assertJsonPath('analyses_deleted', 1);

        $this->assertDatabaseMissing('build_artifacts', ['id' => $ownerArtifact]);
        $this->assertDatabaseHas('build_artifacts', ['id' => $otherArtifact]);
        Storage::disk('analysis-artifacts')->assertMissing($ownerBlob);
        $this->assertDatabaseHas('user_data_deletion_records', [
            'artifacts_deleted' => 1,
            'analyses_deleted' => 1,
            'reason' => 'user_requested',
        ]);
        self::assertNotContains('owner_id_hash', DB::getSchemaBuilder()->getColumnListing('user_data_deletion_records'));
    }

    public function test_expired_unclaimed_raw_artifacts_are_deleted_and_terminally_failed(): void
    {
        $user = User::factory()->create();
        $submission = $this->submit($user, str_repeat('e', 32));
        $artifactId = $submission->json('artifact_id');
        $analysisId = $submission->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);
        $blobKey = DB::table('build_artifacts')->where('id', $artifactId)->value('blob_key');
        self::assertIsString($blobKey);
        DB::table('build_artifacts')->where('id', $artifactId)->update(['raw_expires_at' => now()->subMinute()]);

        self::assertSame(0, Artisan::call('analysis:prune-artifacts'));

        Storage::disk('analysis-artifacts')->assertMissing($blobKey);
        $this->assertDatabaseHas('build_artifacts', [
            'id' => $artifactId,
            'state' => 'failed',
            'failure_code' => 'raw_artifact_expired',
        ]);
        $this->assertDatabaseHas('analyses', [
            'id' => $analysisId,
            'state' => 'failed',
            'failure_code' => 'raw_artifact_expired',
        ]);
    }

    public function test_policy_explanation_use_case_returns_the_audited_exact_decision(): void
    {
        $timestamp = RetrievedAt::from('2026-08-14T13:20:00Z')->value();
        self::assertInstanceOf(RetrievedAt::class, $timestamp);
        $request = CapabilityRequest::create(
            Capability::Import,
            'user_input.pob_code.import',
            'USER-PASTED-POB',
            '1.0.0',
            $timestamp,
            ['explicit_user_submission'],
        )->value();
        self::assertInstanceOf(CapabilityRequest::class, $request);

        $decision = $this->app->make(ExplainPolicyDecision::class)->handle($request);
        self::assertSame(PolicyDecision::Allow, $decision->decision);
        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'USER-PASTED-POB',
            'operation' => 'user_input.pob_code.import',
            'decision' => 'allow',
        ]);
    }

    /** @return array{string, string} */
    private function complete(User $user, string $idempotencyKey): array
    {
        $submission = $this->submit($user, $idempotencyKey);
        $artifactId = $submission->json('artifact_id');
        $analysisId = $submission->json('analysis_id');
        self::assertIsString($artifactId);
        self::assertIsString($analysisId);
        $this->app->instance(ArtifactParser::class, new FakeArtifactParser($this->parsed()));
        $this->app->instance(DeterministicAnalysisEngine::class, new FakeDeterministicAnalysisEngine);
        $this->app->instance(AnalysisPolicyGate::class, new AllowAnalysisPolicyGate);
        $this->app->make(ParseAndNormalizeBuild::class)->handle($artifactId);
        $this->app->make(RunDeterministicAnalysis::class)->handle($analysisId);

        return [$artifactId, $analysisId];
    }

    /** @return TestResponse<Response> */
    private function submit(User $user, string $idempotencyKey, string $artifact = 'fixture artifact'): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/analyses', $this->payload($artifact), [
            'Idempotency-Key' => $idempotencyKey,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(string $artifact = 'fixture artifact'): array
    {
        return [
            'game' => 'poe1',
            'locale' => 'en-US',
            'artifact_type' => 'pob',
            'artifact' => $artifact,
            'storage_consent' => true,
            'goals' => ['Improve the deterministic fixture.'],
        ];
    }

    /** @param list<array{code: string, question: string}> $clarifications */
    private function parsed(array $clarifications = []): ParsedArtifact
    {
        $snapshot = CanonicalJson::encode([
            'canonical_build' => ['edition' => 'poe1', 'fixture' => true],
            'parser_version' => '1.0.0',
        ]);

        return new ParsedArtifact(
            GameEdition::Poe1,
            'pob1-fixture',
            '1.0.0',
            $snapshot,
            hash('sha256', $snapshot),
            '1.2.3',
            null,
            $clarifications,
        );
    }

    private function sqlLiteral(string $value): string
    {
        $quoted = DB::connection()->getPdo()->quote($value);

        if (! is_string($quoted)) {
            throw new RuntimeException('The fixture SQL value could not be quoted.');
        }

        return $quoted;
    }
}

final class FakeArtifactParser implements ArtifactParser
{
    public function __construct(
        private readonly ?ParsedArtifact $result = null,
        private readonly ?Throwable $failure = null,
    ) {}

    public function parse(string $artifactType, string $contents, GameEdition $expectedEdition): ParsedArtifact
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->result ?? throw new RuntimeException('No fake parse result was configured.');
    }
}

final class FakeDeterministicAnalysisEngine implements DeterministicAnalysisEngine
{
    public string $lastInput = '';

    public string $lastOutput = '';

    public function resolve(AnalysisRecord $analysis, ArtifactRecord $artifact): ResolvedAnalysisContext
    {
        return new ResolvedAnalysisContext(
            'pob1-fixture',
            '1.0.0',
            '01890f47-0f7d-7a2b-ac3d-1234567890ab',
            '1.0.0',
            str_repeat('b', 64),
            'LOOTWRIGHT-001',
            'fixture-1',
        );
    }

    public function run(
        AnalysisRecord $analysis,
        ArtifactRecord $artifact,
        ResolvedAnalysisContext $context,
    ): DeterministicAnalysisSnapshot {
        $this->lastInput = CanonicalJson::encode([
            'artifact_hash' => $artifact->normalizedHashSha256,
            'parameters_hash' => $analysis->parametersHashSha256,
            'ruleset_checksum' => $context->rulesetChecksumSha256,
        ]);
        $this->lastOutput = CanonicalJson::encode([
            'analysis_id' => $analysis->id,
            'parameters_hash' => $analysis->parametersHashSha256,
            'result' => 'fixture-only',
        ]);

        return new DeterministicAnalysisSnapshot(
            $context->adapterKey,
            $context->parserVersion,
            $context->rulesetId,
            $context->rulesetVersion,
            $context->rulesetChecksumSha256,
            $this->lastInput,
            hash('sha256', $this->lastInput),
            $this->lastOutput,
            hash('sha256', $this->lastOutput),
        );
    }
}

final class AllowAnalysisPolicyGate implements AnalysisPolicyGate
{
    public int $authorizations = 0;

    public function authorize(ResolvedAnalysisContext $context): void
    {
        $this->authorizations++;
    }
}

final class FailingArtifactStorage implements ArtifactStorage
{
    public function put(string $key, string $contents): void
    {
        throw new TransientWorkflowFailure('Fixture storage failure.');
    }

    public function get(string $key): string
    {
        throw new RuntimeException('Unexpected fixture read.');
    }

    public function delete(string $key): void {}
}

final class FakeIntentExtractor implements IntentExtractor
{
    public int $calls = 0;

    public function extract(IntentExtractionRequest $request): DomainResult
    {
        $this->calls++;

        return DomainResult::success(null);
    }
}

final class FakeResultExplainer implements ResultExplainer
{
    public int $calls = 0;

    public function explain(AnalysisExplanationRequest $request): DomainResult
    {
        $this->calls++;

        return DomainResult::success(null);
    }
}

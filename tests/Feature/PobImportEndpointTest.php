<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Tests\Support\RecordingLogger;
use Tests\TestCase;

class PobImportEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_poe2_expected_game_is_rejected_before_parsing(): void
    {
        $this->postJson('/api/build-imports/pob', [
            'input' => $this->fixture('poe1-minimal.xml'),
            'persist' => false,
            'expected_game' => 'poe2',
        ])->assertConflict();
    }

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-08-14T14:00:00Z');
        $this->seed(PolicyDefaultsSeeder::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_pasted_poe1_input_is_policy_gated_and_processed_transiently(): void
    {
        $this->postJson('/api/build-imports/pob', [
            'input' => $this->fixture('poe1-minimal.xml'),
            'persist' => false,
        ])->assertOk()
            ->assertJsonPath('status', 'normalized')
            ->assertJsonPath('import.canonical_build.edition', 'poe1')
            ->assertJsonPath('retention.persisted', false);

        $this->assertDatabaseCount('pob_imports', 0);
        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'POB-COMMUNITY',
            'operation' => 'pob.community.format_interpret',
            'decision' => 'allow',
        ]);
    }

    public function test_canonical_pobbin_url_uses_the_local_user_input_pipeline(): void
    {
        $code = $this->code($this->fixture('poe1-minimal.xml'));

        $this->postJson('/api/build-imports/pob', [
            'input' => 'https://pobb.in/'.$code,
            'persist' => false,
        ])->assertOk()
            ->assertJsonPath('status', 'normalized')
            ->assertJsonPath('import.canonical_build.edition', 'poe1')
            ->assertJsonPath('retention.persisted', false);

        $this->assertDatabaseMissing('policy_decision_audits', [
            'source_id' => 'POBBIN-REMOTE',
        ]);
    }

    public function test_mutating_routes_keep_the_web_csrf_session_boundary_and_safe_session_defaults(): void
    {
        $storeRoute = Route::getRoutes()->getByName('build-imports.pob.store');
        $deleteRoute = Route::getRoutes()->getByName('build-imports.pob.delete');

        self::assertNotNull($storeRoute);
        self::assertNotNull($deleteRoute);
        self::assertContains('web', $storeRoute->middleware());
        self::assertContains('web', $deleteRoute->middleware());
        self::assertTrue(filter_var(config('session.encrypt'), FILTER_VALIDATE_BOOL));
        self::assertTrue(filter_var(config('session.http_only'), FILTER_VALIDATE_BOOL));
        self::assertSame('lax', config('session.same_site'));
    }

    public function test_uploaded_plain_text_share_code_uses_the_same_bounded_pipeline(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'fixture.txt',
            $this->code($this->fixture('poe1-minimal.xml')),
        );

        $this->post('/api/build-imports/pob', [
            'build_file' => $file,
            'persist' => false,
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('import.canonical_build.edition', 'poe1');
    }

    public function test_upload_type_size_and_storage_consent_are_validated(): void
    {
        $wrongType = UploadedFile::fake()->create('fixture.bin', 1, 'application/octet-stream');
        $tooLarge = UploadedFile::fake()->create('fixture.txt', 1_025, 'text/plain');

        $this->post('/api/build-imports/pob', ['build_file' => $wrongType], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('build_file');
        $this->post('/api/build-imports/pob', ['build_file' => $tooLarge], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('build_file');
        $this->postJson('/api/build-imports/pob', [
            'input' => $this->fixture('poe1-minimal.xml'),
            'persist' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('storage_consent');

        $this->postJson('/api/build-imports/pob', [
            'input' => $this->fixture('poe1-minimal.xml'),
            'persist' => true,
            'storage_consent' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('Idempotency-Key');
    }

    public function test_policy_denial_happens_before_untrusted_input_is_decoded(): void
    {
        DB::table('policy_kill_switches')->insert([
            'scope' => 'source_capability',
            'source_id' => 'USER-POB-001',
            'capability' => 'import',
            'active' => true,
            'reason' => 'Test import shutdown.',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/build-imports/pob', [
            'input' => 'not valid Base64!',
        ])->assertForbidden()
            ->assertJsonPath('status', 'policy_denied')
            ->assertJsonPath('policy.reason', 'source_capability_kill_switch');

        $this->assertDatabaseCount('policy_decision_audits', 1);
    }

    public function test_format_and_persistence_kill_switches_cannot_be_bypassed(): void
    {
        DB::table('policy_kill_switches')->insert([
            'scope' => 'source_capability',
            'source_id' => 'POB-COMMUNITY',
            'capability' => 'derivative_analysis',
            'active' => true,
            'reason' => 'Test format shutdown.',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/build-imports/pob', [
            'input' => $this->fixture('poe1-minimal.xml'),
        ])->assertForbidden()
            ->assertJsonPath('policy.reason', 'source_capability_kill_switch');

        DB::table('policy_kill_switches')->where('source_id', 'POB-COMMUNITY')->delete();
        DB::table('policy_kill_switches')->insert([
            'scope' => 'source_capability',
            'source_id' => 'USER-POB-001',
            'capability' => 'persistent_store',
            'active' => true,
            'reason' => 'Test persistence shutdown.',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::factory()->create());
        $this->withHeader('Idempotency-Key', 'policy-persistence-test-000000000001')
            ->postJson('/api/build-imports/pob', [
                'input' => $this->fixture('poe1-minimal.xml'),
                'persist' => true,
                'storage_consent' => true,
            ])->assertForbidden()
            ->assertJsonPath('policy.reason', 'source_capability_kill_switch');
        $this->assertDatabaseCount('pob_imports', 0);
    }

    public function test_anonymous_callers_cannot_create_persistent_storage(): void
    {
        $this->withHeader('Idempotency-Key', 'anonymous-persistence-test-00000001')
            ->postJson('/api/build-imports/pob', [
                'input' => $this->fixture('poe1-minimal.xml'),
                'persist' => true,
                'storage_consent' => true,
            ])->assertForbidden()
            ->assertJsonPath('policy.reason', 'unmet_conditions');

        $this->assertDatabaseCount('pob_imports', 0);
    }

    public function test_detected_poe2_input_is_rejected_while_the_adapter_remains_dormant(): void
    {
        $this->postJson('/api/build-imports/pob', [
            'input' => $this->fixture('poe2-minimal.xml'),
        ])->assertOk()
            ->assertJsonPath('status', 'normalized');

        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'POB2-COMMUNITY',
            'operation' => 'pob2.community.format_interpret',
            'decision' => 'allow',
        ]);
        $this->assertDatabaseMissing('policy_decision_audits', [
            'source_id' => 'POB-COMMUNITY',
            'operation' => 'pob.community.format_interpret',
        ]);
    }

    public function test_consented_result_is_encrypted_short_lived_and_user_deletable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $raw = str_replace(
            'Untrusted ',
            'private-build-secret ',
            $this->fixture('poe1-minimal.xml'),
        );
        self::assertStringContainsString('private-build-secret', $raw);
        $idempotencyKey = 'private-import-test-000000000001';
        $requestHash = hash('sha256', trim($raw));
        $logger = new RecordingLogger;
        $this->app->instance(LoggerInterface::class, $logger);

        $response = $this->withHeader('Idempotency-Key', $idempotencyKey)
            ->postJson('/api/build-imports/pob', [
                'input' => $raw,
                'persist' => true,
                'storage_consent' => true,
                'retention_hours' => 2,
            ])->assertCreated()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('retention.persisted', true);

        $id = $response->json('retention.id');
        $token = $response->json('retention.deletion_token');

        if (! is_string($id) || ! is_string($token)) {
            throw new RuntimeException('Expected import deletion credentials.');
        }

        $row = DB::table('pob_imports')->where('id', $id)->first();

        self::assertNotNull($row);
        self::assertSame($requestHash, $row->request_hash_sha256);
        self::assertNotSame((string) $user->getKey(), $row->owner_id_hash);
        self::assertNotSame($idempotencyKey, $row->idempotency_key_hash);
        self::assertSame(hash('sha256', $token), $row->deletion_token_hash_sha256);
        self::assertStringNotContainsString('private-build-secret', $row->normalized_payload_encrypted);
        self::assertStringNotContainsString($raw, $row->normalized_payload_encrypted);
        self::assertSame('2026-08-14T16:00:00Z', $response->json('retention.expires_at'));

        $completionLogs = array_values(array_filter(
            $logger->records,
            static fn (array $record): bool => $record['message'] === 'pob_import_completed',
        ));
        self::assertCount(1, $completionLogs);
        self::assertSame($requestHash, $completionLogs[0]['context']['request_hash_sha256']);
        self::assertStringNotContainsString('private-build-secret', serialize($completionLogs[0]['context']));
        self::assertStringNotContainsString($idempotencyKey, serialize($completionLogs[0]['context']));

        $this->deleteJson("/api/build-imports/pob/{$id}", [
            'deletion_token' => str_repeat('0', 64),
        ])->assertNotFound();
        $this->deleteJson("/api/build-imports/pob/{$id}", [
            'deletion_token' => $token,
        ])->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('status', 'deleted');
        $this->assertDatabaseMissing('pob_imports', ['id' => $id]);
    }

    public function test_persistent_import_retries_are_idempotent_and_key_reuse_conflicts(): void
    {
        $this->actingAs(User::factory()->create());
        $headers = ['Idempotency-Key' => 'idempotent-import-test-000000000001'];
        $payload = [
            'input' => $this->fixture('poe1-minimal.xml'),
            'persist' => true,
            'storage_consent' => true,
        ];
        $first = $this->withHeaders($headers)->postJson('/api/build-imports/pob', $payload)
            ->assertCreated()
            ->assertJsonPath('retention.idempotent_replay', false);
        $second = $this->withHeaders($headers)->postJson('/api/build-imports/pob', $payload)
            ->assertOk()
            ->assertJsonPath('retention.idempotent_replay', true);

        self::assertSame($first->json('retention.id'), $second->json('retention.id'));
        self::assertSame($first->json('retention.deletion_token'), $second->json('retention.deletion_token'));
        $this->assertDatabaseCount('pob_imports', 1);

        $this->withHeaders($headers)->postJson('/api/build-imports/pob', [
            ...$payload,
            'input' => str_replace('Untrusted ', 'Different ', $this->fixture('poe1-minimal.xml')),
        ])->assertConflict()->assertJsonPath('status', 'idempotency_conflict');
        $this->assertDatabaseCount('pob_imports', 1);

        $this->actingAs(User::factory()->create());
        $otherOwner = $this->withHeaders($headers)->postJson('/api/build-imports/pob', $payload)
            ->assertCreated();
        self::assertNotSame($first->json('retention.id'), $otherOwner->json('retention.id'));
        self::assertNotSame($first->json('retention.deletion_token'), $otherOwner->json('retention.deletion_token'));
        $this->assertDatabaseCount('pob_imports', 2);
    }

    public function test_expired_imports_are_pruned(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->withHeader('Idempotency-Key', 'expiry-import-test-0000000000001')
            ->postJson('/api/build-imports/pob', [
                'input' => $this->fixture('poe1-minimal.xml'),
                'persist' => true,
                'storage_consent' => true,
                'retention_hours' => 1,
            ])->assertCreated();
        $id = $response->json('retention.id');

        DB::table('pob_imports')->where('id', $id)->update(['expires_at' => now()->subMinute()]);

        self::assertSame(0, Artisan::call('pob:prune-imports'));
        self::assertStringContainsString('Pruned 1 expired PoB import record(s).', Artisan::output());
        $this->assertDatabaseCount('pob_imports', 0);
    }

    private function fixture(string $name): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/Fixtures/Pob/'.$name);

        if (! is_string($contents)) {
            throw new RuntimeException('Fixture could not be read.');
        }

        return $contents;
    }

    private function code(string $xml): string
    {
        $compressed = gzcompress($xml, 9);

        if (! is_string($compressed)) {
            throw new RuntimeException('Fixture compression failed.');
        }

        return rtrim(strtr(base64_encode($compressed), '+/', '-_'), '=');
    }
}

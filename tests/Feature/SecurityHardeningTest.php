<?php

namespace Tests\Feature;

use App\Logging\RedactSensitiveData;
use App\Models\Analysis;
use App\Models\User;
use App\Modules\Administration\AdminAuditLogger;
use App\Modules\Analysis\Jobs\ParseBuildArtifactJob;
use App\Modules\Analysis\Jobs\RunDeterministicAnalysisJob;
use App\Modules\BuildIntake\PobImportDisabled;
use App\Modules\BuildIntake\PolicyGatedPobImporter;
use App\Security\OutboundRequestDenied;
use App\Security\OutboundRequestGuard;
use Carbon\CarbonImmutable;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Application\Workflow\UseCases\ParseAndNormalizeBuild;
use Lootwright\Application\Workflow\UseCases\RunDeterministicAnalysis;
use Lootwright\Domain\Shared\Game\GameEdition;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

final class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
    }

    public function test_production_responses_emit_a_strict_browser_security_baseline(): void
    {
        $this->app->instance('env', 'production');

        $response = $this->get('https://lootwright.test/');
        $csp = (string) $response->headers->get('Content-Security-Policy');

        $response->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-site')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        self::assertStringContainsString("default-src 'self'", $csp);
        self::assertStringContainsString("frame-ancestors 'none'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringNotContainsString("'unsafe-inline'", $csp);
        self::assertStringNotContainsString("'unsafe-eval'", $csp);
        self::assertStringContainsString('camera=()', (string) $response->headers->get('Permissions-Policy'));
    }

    public function test_correlation_ids_are_bounded_returned_and_shared_with_audit_records(): void
    {
        $correlationId = '018f0000-0000-7000-8000-000000000123';
        $this->withHeader('X-Correlation-ID', $correlationId)
            ->get('/')
            ->assertOk()
            ->assertHeader('X-Correlation-ID', $correlationId);

        $generated = (string) $this->withHeader('X-Correlation-ID', 'forged-log-entry')
            ->get('/')
            ->headers->get('X-Correlation-ID');
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $generated);

        $actor = User::factory()->create();
        Context::scope(function () use ($actor): void {
            app(AdminAuditLogger::class)->record($actor, 'security.correlation_test', 'Bounded test reason.');
        }, ['correlation_id' => $correlationId]);
        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_user_id' => $actor->id,
            'correlation_id' => $correlationId,
        ]);
    }

    public function test_persistence_models_are_not_open_to_mass_assignment(): void
    {
        self::assertTrue((new Analysis)->totallyGuarded());

        $user = new User;
        $user->fill([
            'name' => 'Fixture',
            'email' => 'fixture@example.test',
            'password' => 'not-persisted',
            'role' => 'super_admin',
            'status' => 'suspended',
        ]);
        self::assertArrayNotHasKey('role', $user->getAttributes());
        self::assertArrayNotHasKey('status', $user->getAttributes());
    }

    public function test_admin_audit_rejects_sensitive_metadata_and_log_injection(): void
    {
        $actor = User::factory()->create();
        $audit = app(AdminAuditLogger::class);

        try {
            $audit->record($actor, 'security.invalid_metadata', 'Bounded reason.', metadata: [
                'access_token_hash' => 'must-not-persist',
            ]);
            self::fail('Sensitive audit metadata must be rejected.');
        } catch (InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $this->expectException(InvalidArgumentException::class);
        $audit->record($actor, 'security.invalid_reason', "forged\nlog line");
    }

    public function test_hostile_pob_text_stays_json_data_and_cannot_be_reflected_as_markup(): void
    {
        $xml = $this->fixture('poe1-minimal.xml');
        $response = $this->postJson('/api/build-imports/pob?redirect=https://evil.example', [
            'input' => $xml,
            'persist' => false,
        ]);

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderMissing('Location')
            ->assertDontSee('<script>fixture</script>', false)
            ->assertJsonPath('import.canonical_build.notes_untrusted_text', 'Untrusted <script>fixture</script> text');
        self::assertStringNotContainsString('evil.example', (string) $response->getContent());
    }

    public function test_untrusted_redirect_parameters_never_change_navigation_targets(): void
    {
        $this->get('/?redirect=https://evil.example')
            ->assertOk()
            ->assertHeaderMissing('Location')
            ->assertDontSee('https://evil.example', false);

        $this->getJson('/api/analyses/01890f47-0f7d-7a2b-ac3d-1234567890ab?return_to=https://evil.example')
            ->assertUnauthorized()
            ->assertHeaderMissing('Location');
    }

    public function test_optional_email_verification_blocks_only_unverified_accounts(): void
    {
        Config::set('security.require_verified_email', true);
        $analysisId = '01890f47-0f7d-7a2b-ac3d-1234567890ab';

        $this->actingAs(User::factory()->unverified()->create())
            ->getJson('/api/analyses/'.$analysisId)
            ->assertForbidden()
            ->assertJsonPath('status', 'email_verification_required');

        $this->actingAs(User::factory()->create())
            ->getJson('/api/analyses/'.$analysisId)
            ->assertNotFound();
    }

    public function test_admin_authority_is_disabled_by_default_and_not_inherited_from_a_user_session(): void
    {
        $token = 'security-admin-token-0000000000000000';
        Config::set('policy.admin_token', $token);

        $this->actingAs(User::factory()->create())
            ->withHeader('X-Lootwright-Policy-Admin-Token', $token)
            ->getJson('/admin/policy/evidence')
            ->assertNotFound();

        Config::set('security.policy_admin.enabled', true);
        $this->withoutHeader('X-Lootwright-Policy-Admin-Token')
            ->getJson('/admin/policy/evidence')->assertNotFound();
        $this->withHeaders([
            'X-Lootwright-Policy-Admin-Token' => $token,
            'X-Lootwright-Privacy-Session' => 'not-an-admin-credential',
        ])->getJson('/admin/policy/evidence')->assertNotFound();
    }

    public function test_admin_cannot_store_an_unapproved_or_credential_bearing_evidence_url(): void
    {
        Config::set('security.policy_admin.enabled', true);
        Config::set('policy.admin_token', 'security-admin-token-0000000000000000');

        $this->withHeader('X-Lootwright-Policy-Admin-Token', 'security-admin-token-0000000000000000')
            ->postJson('/admin/policy/evidence', [
                'id' => 'UNSAFE-REDIRECT-EVIDENCE',
                'source_id' => 'POBBIN-REMOTE',
                'source_version' => 'unreviewed-2026-08-14',
                'evidence_url' => 'https://user:secret@evil.example/redirect',
                'retrieved_at' => '2026-08-15T00:00:00Z',
                'effective_from' => '2026-08-15T00:00:00Z',
                'permission_status' => 'unknown',
                'attribution_required' => false,
                'summary' => 'Must never be stored.',
            ])->assertUnprocessable()->assertJsonValidationErrors('evidence_url');

        $this->assertDatabaseMissing('policy_permission_evidence', ['id' => 'UNSAFE-REDIRECT-EVIDENCE']);
    }

    public function test_anonymous_session_creation_has_bounded_non_fingerprinting_rate_limits(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/privacy-sessions')->assertCreated();
        }

        $this->postJson('/api/privacy-sessions')
            ->assertTooManyRequests()
            ->assertHeader('Retry-After');
        $this->assertDatabaseCount('privacy_sessions', 3);
    }

    public function test_import_emergency_switch_cannot_be_bypassed_through_http_or_direct_orchestration(): void
    {
        Config::set('security.emergency.imports', false);

        $this->postJson('/api/build-imports/pob', [
            'input' => 'not valid base64 and must never reach the parser',
        ])->assertServiceUnavailable()->assertJsonPath('capability', 'imports');
        $this->assertDatabaseCount('policy_decision_audits', 0);

        $this->expectException(PobImportDisabled::class);
        $this->app->make(PolicyGatedPobImporter::class)->handle('still not parsed', false);
    }

    public function test_egress_is_deny_by_default_exact_and_rejects_private_dns_answers(): void
    {
        $target = [
            'openai.responses' => [
                'scheme' => 'https',
                'host' => 'api.openai.com',
                'port' => 443,
                'path' => '/v1/responses',
            ],
        ];

        $disabled = new OutboundRequestGuard(false, $target, static fn (): array => ['203.0.113.10']);
        $this->assertOutboundDenied($disabled, 'https://api.openai.com/v1/responses');

        $private = new OutboundRequestGuard(true, $target, static fn (): array => ['127.0.0.1']);
        $this->assertOutboundDenied($private, 'https://api.openai.com/v1/responses');

        $public = new OutboundRequestGuard(true, $target, static fn (): array => ['8.8.8.8']);
        $this->assertOutboundDenied($public, 'https://api.openai.com/v1/responses?url=http://169.254.169.254');
        $public->assertAllowed('openai.responses', 'https://api.openai.com/v1/responses');
    }

    public function test_log_processor_removes_secrets_raw_artifacts_and_bearer_credentials(): void
    {
        $processor = new RedactSensitiveData;
        $record = new LogRecord(
            CarbonImmutable::now(),
            'security',
            Level::Warning,
            "Failed with Bearer super-secret and sk-project-secret123\nforged-log-entry",
            [
                'OPENAI_API_KEY' => 'sk-project-secret123',
                'artifact' => '<PathOfBuilding>private</PathOfBuilding>',
                'nested' => ['privacy_session_token' => str_repeat('a', 64)],
                'unlabelled_user_input' => str_repeat('private build text ', 100),
                'safe_code' => 'policy_blocked',
            ],
        );

        $redacted = $processor($record);
        self::assertStringNotContainsString('super-secret', $redacted->message);
        self::assertStringNotContainsString('sk-project-secret123', $redacted->message);
        self::assertStringNotContainsString("\n", $redacted->message);
        self::assertSame('[REDACTED]', $redacted->context['OPENAI_API_KEY']);
        self::assertSame('[REDACTED]', $redacted->context['artifact']);
        self::assertSame('[REDACTED]', $redacted->context['nested']['privacy_session_token']);
        self::assertSame('[REDACTED:OVERSIZED]', $redacted->context['unlabelled_user_input']);
        self::assertSame('policy_blocked', $redacted->context['safe_code']);
    }

    public function test_retention_pruner_removes_expired_linkable_metadata_and_keeps_current_records(): void
    {
        $old = now()->subDays(60);
        DB::table('privacy_sessions')->insert([
            [
                'id' => '01890f47-0f7d-7a2b-ac3d-1234567890ab',
                'access_token_hash_sha256' => str_repeat('a', 64),
                'status' => 'deleted',
                'expires_at' => $old,
                'last_seen_at' => $old,
                'deletion_requested_at' => $old,
                'created_at' => $old,
                'updated_at' => $old,
            ],
            [
                'id' => '01890f47-0f7d-7a2b-ac3d-1234567890ac',
                'access_token_hash_sha256' => str_repeat('b', 64),
                'status' => 'active',
                'expires_at' => now()->addDay(),
                'last_seen_at' => now(),
                'deletion_requested_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('ai_request_audits')->insert([
            'id' => '01890f47-0f7d-7a2b-ac3d-1234567890ad',
            'request_hash' => str_repeat('c', 64),
            'user_hash' => str_repeat('d', 64),
            'prompt_template_version' => 'fixture',
            'provider' => 'fake',
            'model' => 'fake',
            'task' => 'intent',
            'input_tokens' => 0,
            'cached_input_tokens' => 0,
            'output_tokens' => 0,
            'latency_ms' => 0,
            'cache_status' => 'miss',
            'validation_outcome' => 'disabled',
            'repair_attempts' => 0,
            'cost_micro_usd' => 0,
            'created_at' => $old,
        ]);

        self::assertSame(0, Artisan::call('security:prune-retained-data'));
        $this->assertDatabaseMissing('privacy_sessions', ['id' => '01890f47-0f7d-7a2b-ac3d-1234567890ab']);
        $this->assertDatabaseHas('privacy_sessions', ['id' => '01890f47-0f7d-7a2b-ac3d-1234567890ac']);
        $this->assertDatabaseCount('ai_request_audits', 0);
    }

    public function test_sensitive_routes_keep_csrf_and_named_security_middleware(): void
    {
        $submit = Route::getRoutes()->getByName('analyses.submit');
        $delete = Route::getRoutes()->getByName('user-data.delete');
        $export = Route::getRoutes()->getByName('analyses.export');

        self::assertNotNull($submit);
        self::assertNotNull($delete);
        self::assertNotNull($export);
        self::assertContains('web', $submit->middleware());
        self::assertContains('emergency:imports', $submit->middleware());
        self::assertContains('throttle:analysis-submit', $submit->middleware());
        self::assertContains('throttle:deletion', $delete->middleware());
        self::assertContains('throttle:export', $export->middleware());
        self::assertIsCallable(RateLimiter::limiter('authentication'));
        self::assertIsCallable(RateLimiter::limiter('imports'));
        self::assertIsCallable(RateLimiter::limiter('ai'));
    }

    public function test_malformed_queue_payloads_cannot_claim_or_execute_work(): void
    {
        $repository = $this->app->make(WorkflowRepository::class);

        (new ParseBuildArtifactJob('not-a-uuid', GameEdition::Poe1))->handle(
            $this->app->make(ParseAndNormalizeBuild::class),
            $repository,
        );
        (new RunDeterministicAnalysisJob(
            'not-a-uuid',
            GameEdition::Poe1,
            str_repeat('A', 64),
        ))->handle(
            $this->app->make(RunDeterministicAnalysis::class),
            $repository,
        );

        $this->assertDatabaseCount('build_artifacts', 0);
        $this->assertDatabaseCount('analyses', 0);
    }

    public function test_external_link_switch_is_exposed_as_a_server_authoritative_page_prop(): void
    {
        Config::set('security.emergency.external_links', false);

        $this->get('/analyses/demo/trade')->assertOk()->assertInertia(
            fn (Assert $page): Assert => $page
                ->component('Analysis/Workspace')
                ->where('externalLinksEnabled', false),
        );
    }

    private function assertOutboundDenied(OutboundRequestGuard $guard, string $url): void
    {
        $denial = null;

        try {
            $guard->assertAllowed('openai.responses', $url);
        } catch (OutboundRequestDenied $exception) {
            $denial = $exception;
        }

        self::assertInstanceOf(OutboundRequestDenied::class, $denial);
    }

    private function fixture(string $name): string
    {
        $contents = file_get_contents(base_path('tests/Fixtures/Pob/'.$name));

        self::assertIsString($contents);

        return $contents;
    }
}

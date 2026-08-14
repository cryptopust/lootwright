<?php

namespace Tests\Feature;

use App\Modules\PolicyProvenance\PolicyDefaults;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\PolicyProvenance\DecideCapability;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PolicyProvenanceGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function defaultRules(): iterable
    {
        foreach (PolicyDefaults::rules() as $rule) {
            $name = $rule['source_id'].':'.$rule['capability'].':'.$rule['operation'];
            yield $name => [$rule];
        }
    }

    /** @param array<string, mixed> $rule */
    #[DataProvider('defaultRules')]
    public function test_every_seeded_default_is_enforced_through_the_gate(array $rule): void
    {
        $request = $this->request(
            Capability::from($rule['capability']),
            $rule['operation'],
            $rule['source_id'],
            $rule['source_version'],
            $rule['required_conditions'],
        );
        $decision = $this->decision($request);

        self::assertSame(PolicyDecision::from($rule['decision']), $decision->decision);
        self::assertSame($decision->decision === PolicyDecision::Allow, $decision->permitsExecution());
        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => $rule['source_id'],
            'capability' => $rule['capability'],
            'operation' => $rule['operation'],
            'decision' => $rule['decision'],
            'actor_type' => 'application',
        ]);
    }

    public function test_unknown_operation_is_denied_and_audited(): void
    {
        $decision = $this->decision($this->request(
            Capability::LiveFetch,
            'unknown.external.operation',
            'GGG-DOCUMENTED-API',
            '2026-08-14',
        ));

        self::assertSame(PolicyDecision::Deny, $decision->decision);
        self::assertSame('missing_rule', $decision->reason->value);
        $this->assertDatabaseCount('policy_decision_audits', 1);
    }

    public function test_environment_and_database_kill_switches_fail_closed(): void
    {
        Config::set('policy.global_kill_switch', true);
        $global = $this->decision($this->request(
            Capability::Import,
            'user_input.pob_code.import',
            'USER-PASTED-POB',
            '1.0.0',
            ['explicit_user_submission'],
        ));
        self::assertSame('global_kill_switch', $global->reason->value);

        Config::set('policy.global_kill_switch', false);
        DB::table('policy_kill_switches')->insert([
            'scope' => 'source_capability',
            'source_id' => 'USER-PASTED-POB',
            'capability' => 'import',
            'active' => true,
            'reason' => 'Test emergency switch.',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $scoped = $this->decision($this->request(
            Capability::Import,
            'user_input.pob_code.import',
            'USER-PASTED-POB',
            '1.0.0',
            ['explicit_user_submission'],
        ));

        self::assertSame(PolicyDecision::Deny, $scoped->decision);
        self::assertSame('source_capability_kill_switch', $scoped->reason->value);
    }

    public function test_user_persistence_requires_consent(): void
    {
        $withoutConsent = $this->decision($this->request(
            Capability::PersistentStore,
            'user_input.item_text.store',
            'USER-PASTED-ITEM',
            '1.0.0',
            ['explicit_user_submission'],
        ));
        $withConsent = $this->decision($this->request(
            Capability::PersistentStore,
            'user_input.item_text.store',
            'USER-PASTED-ITEM',
            '1.0.0',
            ['explicit_user_submission', 'user_storage_consent'],
        ));

        self::assertSame(PolicyDecision::RequireReview, $withoutConsent->decision);
        self::assertSame(PolicyDecision::Allow, $withConsent->decision);
    }

    public function test_public_explanations_are_read_only_and_admin_evidence_is_token_protected(): void
    {
        $this->getJson('/policy/sources/GGG-UNDOCUMENTED-TRADE')
            ->assertOk()
            ->assertJsonPath('source.id', 'GGG-UNDOCUMENTED-TRADE')
            ->assertJsonPath('notice', 'A require_review result does not permit execution.')
            ->assertJsonMissing(['reviewer_role']);

        $this->getJson('/admin/policy/evidence')->assertNotFound();

        Config::set('policy.admin_token', 'test-policy-admin-token');
        $this->withHeader('X-Lootwright-Policy-Admin-Token', 'test-policy-admin-token')
            ->getJson('/admin/policy/evidence')
            ->assertOk()
            ->assertJsonStructure(['evidence']);
    }

    public function test_admin_can_manage_evidence_and_scoped_kill_switches_without_storing_secrets(): void
    {
        Config::set('policy.admin_token', 'test-policy-admin-token');
        $headers = ['X-Lootwright-Policy-Admin-Token' => 'test-policy-admin-token'];

        $this->withHeaders($headers)->postJson('/admin/policy/evidence', [
            'id' => 'POBBIN-REVIEW-TEST',
            'source_id' => 'POBBIN-REMOTE',
            'source_version' => 'unreviewed-2026-08-14',
            'evidence_url' => 'https://pobb.in/',
            'retrieved_at' => '2026-08-14T13:16:00Z',
            'effective_from' => '2026-08-14T00:00:00Z',
            'effective_until' => '2026-11-12T00:00:00Z',
            'permission_status' => 'unknown',
            'attribution_required' => false,
            'summary' => 'Test evidence remains non-enabling.',
        ])->assertCreated();

        $this->withHeaders($headers)->postJson('/admin/policy/kill-switches', [
            'scope' => 'source',
            'source_id' => 'POBBIN-REMOTE',
            'capability' => null,
            'active' => true,
            'reason' => 'Emergency test disablement.',
        ])->assertOk();

        $this->assertDatabaseHas('policy_permission_evidence', [
            'id' => 'POBBIN-REVIEW-TEST',
            'permission_status' => 'unknown',
            'reviewer_role' => 'policy_admin',
        ]);
        $this->assertDatabaseMissing('policy_permission_evidence', [
            'summary' => 'test-policy-admin-token',
        ]);
        $this->assertDatabaseHas('policy_kill_switches', [
            'scope' => 'source',
            'source_id' => 'POBBIN-REMOTE',
            'active' => true,
        ]);
    }

    /** @param list<string> $conditions */
    private function request(
        Capability $capability,
        string $operation,
        string $sourceId,
        string $sourceVersion,
        array $conditions = [],
    ): CapabilityRequest {
        $instant = RetrievedAt::from('2026-08-14T13:20:00Z')->value();

        if (! $instant instanceof RetrievedAt) {
            throw new RuntimeException('Expected a policy timestamp.');
        }

        $request = CapabilityRequest::create(
            $capability,
            $operation,
            $sourceId,
            $sourceVersion,
            $instant,
            $conditions,
        )->value();

        if (! $request instanceof CapabilityRequest) {
            throw new RuntimeException('Expected a capability request.');
        }

        return $request;
    }

    private function decision(CapabilityRequest $request): CapabilityDecision
    {
        $result = $this->app->make(DecideCapability::class)->handle($request);

        $decision = $result->value();

        if (! $decision instanceof CapabilityDecision) {
            throw new RuntimeException('Expected a capability decision.');
        }

        return $decision;
    }
}

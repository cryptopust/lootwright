<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class FundingArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PolicyDefaultsSeeder::class);
        Queue::fake();
    }

    public function test_funding_is_disabled_by_default_and_costs_are_public_projections_only(): void
    {
        $this->get('/funding')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Funding')
                ->where('funding.requested_enabled', false)
                ->where('funding.enabled', false)
                ->where('funding.accepting_funds', false)
                ->where('funding.policy_decision', 'deny')
                ->where('funding.pricing_model', 'gpt-5.4-nano')
                ->where('funding.pricing_reviewed_on', '2026-08-15')
                ->where('funding.cost_projections.0.scenario', 'low')
                ->where('funding.cost_projections.0.hosting_monthly_cents', 5_500)
                ->where('funding.cost_projections.0.ai_monthly_cents', 10)
                ->where('funding.cost_projections.1.total_monthly_cents', 5_695)
                ->where('funding.cost_projections.2.total_monthly_cents', 9_268));
    }

    public function test_environment_and_operator_metadata_cannot_bypass_the_funding_policy_gate(): void
    {
        config()->set([
            'funding.requested_enabled' => true,
            'funding.activation.policy_decision_id' => 'FUNDING-DECISION-20260815',
            'funding.activation.policy_decision_date' => '2026-08-15',
            'funding.activation.evidence_record_id' => 'FUNDING-EVIDENCE-20260815',
            'funding.activation.operator_acknowledged' => true,
            'funding.activation.disclosure_version' => '2026-08-15.1',
        ]);

        $this->get('/funding')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('funding.requested_enabled', true)
                ->where('funding.enabled', false)
                ->where('funding.accepting_funds', false)
                ->where('funding.policy_decision', 'deny')
                ->where('funding.activation_requirements.dated_policy_decision', true)
                ->where('funding.activation_requirements.permission_evidence_recorded', true)
                ->where('funding.activation_requirements.operator_activation', true)
                ->where('funding.activation_requirements.public_disclosure_versioned', true));

        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'LOOTWRIGHT-FUNDING',
            'source_version' => '2026-08-15',
            'capability' => 'monetized_hosting',
            'operation' => 'lootwright.funding.activate',
            'decision' => 'deny',
        ]);
    }

    public function test_donor_or_sponsor_state_is_rejected_before_product_output_is_created(): void
    {
        $user = User::factory()->create();
        $payload = [
            'game' => 'poe1',
            'platform_realm' => 'pc',
            'locale' => 'en-US',
            'artifact_type' => 'pob',
            'artifact' => 'fixture build artifact',
            'storage_consent' => true,
            'goals' => ['Improve the deterministic fixture.'],
            'donor_status' => 'founder',
            'donor_badge' => 'gold',
            'funding_tier' => 'priority',
            'sponsor_rank' => 1,
        ];

        $this->actingAs($user)
            ->postJson('/api/analyses', $payload, ['Idempotency-Key' => str_repeat('f', 32)])
            ->assertUnprocessable()
            ->assertJson(fn (AssertableJson $json): AssertableJson => $json
                ->hasAll([
                    'errors.donor_status',
                    'errors.donor_badge',
                    'errors.funding_tier',
                    'errors.sponsor_rank',
                ])
                ->etc());

        $this->assertDatabaseCount('build_artifacts', 0);
        $this->assertDatabaseCount('analyses', 0);
        Queue::assertNothingPushed();
    }
}

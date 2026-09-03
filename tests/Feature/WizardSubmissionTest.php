<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WizardSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_poe2_payloads_are_rejected_at_the_public_boundary(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $headers = ['Idempotency-Key' => str_repeat('p', 32)];
        $payload = [...$this->payload(), 'game' => 'poe2', 'character_class' => 'witch', 'ascendancy' => 'lich', 'alternate_ascendancy' => 'abyssal-lich'];
        $this->actingAs($user)->postJson('/api/analyses/wizard', $payload, $headers)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('game');
    }

    public function test_guest_and_unverified_user_cannot_submit_persistent_analysis(): void
    {
        $payload = $this->payload();
        $this->postJson('/api/analyses/wizard', $payload, ['Idempotency-Key' => str_repeat('a', 32)])->assertUnauthorized();
        $this->actingAs(User::factory()->unverified()->create())->postJson('/api/analyses/wizard', $payload, ['Idempotency-Key' => str_repeat('b', 32)])->assertForbidden();
    }

    public function test_invalid_pair_level_and_negative_budget_are_rejected_by_backend(): void
    {
        $user = User::factory()->create();
        $payload = [...$this->payload(), 'character_class' => 'witch', 'ascendancy' => 'warden', 'character_level' => 101, 'budget_amount' => '-1'];
        $this->actingAs($user)->postJson('/api/analyses/wizard', $payload, ['Idempotency-Key' => str_repeat('c', 32)])->assertUnprocessable()->assertJsonValidationErrors(['ascendancy', 'character_level', 'budget_amount']);
    }

    public function test_flow_specific_fields_are_required(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/api/analyses/wizard', [...$this->payload(), 'flow' => 'analyse', 'pob' => ''], ['Idempotency-Key' => str_repeat('d', 32)])->assertJsonValidationErrors('pob');
        $this->actingAs($user)->postJson('/api/analyses/wizard', [...$this->payload(), 'flow' => 'upgrade', 'item_text' => '', 'equipment_slot' => ''], ['Idempotency-Key' => str_repeat('e', 32)])->assertJsonValidationErrors('item_text');
    }

    public function test_duplicate_submission_replays_one_analysis_and_does_not_log_raw_artifact(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $payload = $this->payload();
        $key = str_repeat('f', 32);
        $first = $this->actingAs($user)->postJson('/api/analyses/wizard', $payload, ['Idempotency-Key' => $key])->assertAccepted();
        $second = $this->actingAs($user)->postJson('/api/analyses/wizard', $payload, ['Idempotency-Key' => $key])->assertOk()->assertJsonPath('idempotent_replay', true);
        self::assertSame($first->json('analysis_id'), $second->json('analysis_id'));
        $this->assertDatabaseCount('analyses', 1);
        self::assertStringNotContainsString('raw-secret-pob', json_encode(DB::table('analyses')->get()->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_submission_without_a_budget_normalizes_the_default_currency_to_null(): void
    {
        Bus::fake();
        $user = User::factory()->create();
        $payload = [...$this->payload(), 'budget_amount' => '', 'budget_currency' => 'DIVINE'];

        $this->actingAs($user)
            ->postJson('/api/analyses/wizard', $payload, ['Idempotency-Key' => str_repeat('n', 32)])
            ->assertAccepted();

        $parameters = json_decode(Crypt::decryptString((string) DB::table('analyses')->value('parameters_snapshot_encrypted')), true, flags: JSON_THROW_ON_ERROR);
        self::assertNull($parameters['budget']);
        self::assertNull($parameters['selection']['league']);
    }

    public function test_draft_rejects_raw_artifact_fields_and_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->putJson('/api/analysis-draft', ['flow' => 'plan', 'current_step' => 3, 'safe_fields' => ['character_class' => 'ranger', 'pob' => 'raw-secret-pob']])->assertJsonValidationErrors('safe_fields.pob');
        $this->actingAs($owner)->putJson('/api/analysis-draft', ['flow' => 'plan', 'current_step' => 3, 'safe_fields' => ['character_class' => 'ranger']])->assertAccepted();
        $this->actingAs($other)->getJson('/api/analysis-draft')->assertJsonPath('draft', null);
    }

    public function test_draft_rejects_an_inactive_game_edition(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->putJson('/api/analysis-draft', ['game' => 'poe2', 'flow' => 'plan', 'current_step' => 2, 'safe_fields' => ['game' => 'poe2', 'character_class' => 'witch']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('game');
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['flow' => 'plan', 'game' => 'poe1', 'character_class' => 'ranger', 'ascendancy' => 'warden', 'character_level' => 90, 'league' => 'standard', 'mode' => 'trade', 'difficulty' => 'softcore', 'build_name' => 'Test', 'main_skill' => 'Lightning Arrow', 'secondary_skills' => [], 'archetype' => 'bow', 'pob' => '', 'item_text' => '', 'equipment_slot' => '', 'goals' => ['mapping'], 'play_style' => 'balanced', 'priority' => 'budget_efficiency', 'problem' => '', 'description' => '', 'budget_amount' => '10', 'budget_currency' => 'DIVINE', 'preserved_items' => [], 'replaceable_slots' => [], 'avoid' => '', 'must_keep' => '', 'notes' => '', 'storage_consent' => true, 'ai_explanation_opt_in' => false];
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WizardSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_poe2_relationships_planned_classes_and_cross_game_payloads_are_validated(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $headers = ['Idempotency-Key' => str_repeat('p', 32)];
        $valid = [...$this->payload(), 'game' => 'poe2', 'character_class' => 'witch', 'ascendancy' => 'lich', 'alternate_ascendancy' => 'abyssal-lich'];
        $response = $this->actingAs($user)->postJson('/api/analyses/wizard', $valid, $headers)->assertAccepted();
        $this->assertDatabaseHas('analyses', ['id' => $response->json('analysis_id'), 'game_edition' => 'poe2', 'user_id' => $user->id]);

        foreach ([
            [...$valid, 'character_class' => 'marauder', 'ascendancy' => null, 'alternate_ascendancy' => null],
            [...$valid, 'ascendancy' => 'infernalist'],
            [...$valid, 'character_class' => 'ranger', 'ascendancy' => 'warden', 'alternate_ascendancy' => null],
        ] as $index => $invalid) {
            $this->actingAs($user)->postJson('/api/analyses/wizard', $invalid, ['Idempotency-Key' => str_repeat((string) ($index + 1), 32)])->assertUnprocessable()->assertJsonValidationErrors('ascendancy');
        }
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

    public function test_draft_rejects_raw_artifact_fields_and_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($owner)->putJson('/api/analysis-draft', ['flow' => 'plan', 'current_step' => 3, 'safe_fields' => ['character_class' => 'ranger', 'pob' => 'raw-secret-pob']])->assertJsonValidationErrors('safe_fields.pob');
        $this->actingAs($owner)->putJson('/api/analysis-draft', ['flow' => 'plan', 'current_step' => 3, 'safe_fields' => ['character_class' => 'ranger']])->assertAccepted();
        $this->actingAs($other)->getJson('/api/analysis-draft')->assertJsonPath('draft', null);
    }

    public function test_draft_persists_game_identity_without_raw_artifacts(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->putJson('/api/analysis-draft', ['game' => 'poe2', 'flow' => 'plan', 'current_step' => 2, 'safe_fields' => ['game' => 'poe2', 'character_class' => 'witch']])->assertAccepted();
        $this->assertDatabaseHas('analysis_drafts', ['user_id' => $owner->id, 'game_edition' => 'poe2']);
        self::assertStringNotContainsString('pob', (string) DB::table('analysis_drafts')->where('user_id', $owner->id)->value('safe_fields'));
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['flow' => 'plan', 'game' => 'poe1', 'character_class' => 'ranger', 'ascendancy' => 'warden', 'character_level' => 90, 'league' => 'standard', 'mode' => 'trade', 'difficulty' => 'softcore', 'build_name' => 'Test', 'main_skill' => 'Lightning Arrow', 'secondary_skills' => [], 'archetype' => 'bow', 'pob' => '', 'item_text' => '', 'equipment_slot' => '', 'goals' => ['mapping'], 'play_style' => 'balanced', 'priority' => 'budget_efficiency', 'problem' => '', 'description' => '', 'budget_amount' => '10', 'budget_currency' => 'DIVINE', 'preserved_items' => [], 'replaceable_slots' => [], 'avoid' => '', 'must_keep' => '', 'notes' => '', 'storage_consent' => true, 'ai_explanation_opt_in' => false];
    }
}

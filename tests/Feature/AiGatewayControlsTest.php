<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AI\DatabaseAiBudget;
use App\Modules\AI\DatabaseAiCircuitBreaker;
use App\Modules\AI\DatabaseAiExecutionPolicy;
use App\Modules\AI\DatabaseAiRuntimePolicy;
use App\Modules\AI\DatabaseAiUserDataEraser;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\AIGateway\DTO\AiRequestContext;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Tests\TestCase;

final class AiGatewayControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_policy_gate_denies_openai_until_reviewed_allow_exists(): void
    {
        config()->set('source-governance.openai_intent_enabled', true);
        $this->seed(PolicyDefaultsSeeder::class);
        $policy = new DatabaseAiExecutionPolicy($this->app->make(CapabilityPolicy::class));

        self::assertFalse($policy->permits('intent'));
        self::assertFalse($policy->permits('explanation'));
        $this->assertDatabaseHas('policy_decision_audits', [
            'source_id' => 'OPENAI-API',
            'operation' => 'openai.responses.intent',
            'decision' => 'require_review',
        ]);
    }

    public function test_runtime_task_switches_and_circuit_breaker_fail_closed(): void
    {
        config()->set('ai.enabled', true);
        config()->set('ai.intent_enabled', true);
        config()->set('ai.explanation_enabled', true);
        $policy = new DatabaseAiRuntimePolicy;
        self::assertFalse($policy->permits('intent'));

        DB::table('ai_runtime_controls')->where('scope', 'global')->update([
            'global_enabled' => true, 'intent_enabled' => true, 'explanation_enabled' => false,
        ]);
        self::assertTrue($policy->permits('intent'));
        self::assertFalse($policy->permits('explanation'));

        $breaker = new DatabaseAiCircuitBreaker(2, 300);
        self::assertTrue($breaker->permitsRequest());
        $breaker->recordFailure();
        self::assertTrue($breaker->permitsRequest());
        $breaker->recordFailure();
        self::assertFalse($breaker->permitsRequest());
        $breaker->recordSuccess();
        self::assertTrue($breaker->permitsRequest());
    }

    public function test_circuit_cooldown_allows_only_one_half_open_probe(): void
    {
        Carbon::setTestNow('2026-08-21 12:00:00');
        $breaker = new DatabaseAiCircuitBreaker(1, 300);
        $breaker->recordFailure();
        self::assertFalse($breaker->permitsRequest());

        Carbon::setTestNow('2026-08-21 12:05:01');
        self::assertTrue($breaker->permitsRequest());
        self::assertFalse($breaker->permitsRequest());

        $breaker->recordSuccess();
        self::assertTrue($breaker->permitsRequest());
        Carbon::setTestNow();
    }

    public function test_super_admin_can_change_runtime_and_user_quota_with_audit_but_admin_cannot(): void
    {
        $admin = User::factory()->admin()->create(['two_factor_confirmed_at' => now()]);
        $super = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);
        $member = User::factory()->create();
        $payload = [
            'global_enabled' => true,
            'intent_enabled' => true,
            'explanation_enabled' => false,
            'global_daily_budget_micro_usd' => 1000,
            'global_monthly_budget_micro_usd' => 10_000,
            'reason' => 'Approved controlled rollout',
        ];
        $session = ['auth.password_confirmed_at' => time()];

        $this->actingAs($super)->put('/admin/ai/settings', $payload)->assertRedirect(route('password.confirm'));
        $this->actingAs($admin)->withSession($session)->put('/admin/ai/settings', $payload)->assertForbidden();
        $this->actingAs($super)->withSession($session)->put('/admin/ai/settings', $payload)->assertRedirect();
        $this->actingAs($super)->withSession($session)->put("/admin/users/{$member->id}/ai-quota", [
            'daily_budget_micro_usd' => 500,
            'reason' => 'Temporary evaluated quota',
        ])->assertRedirect();

        $this->assertDatabaseHas('ai_runtime_controls', ['scope' => 'global', 'global_enabled' => true, 'intent_enabled' => true]);
        $this->assertDatabaseHas('ai_user_quota_overrides', ['user_id' => $member->id, 'daily_budget_micro_usd' => 500]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'ai.runtime.updated', 'actor_user_id' => $super->id]);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'ai.user_quota.updated', 'target_user_id' => $member->id]);
    }

    public function test_budget_reservations_fail_closed_at_user_and_monthly_caps(): void
    {
        $budget = new DatabaseAiBudget(10, 100, 100, 10);
        $context = new AiRequestContext(str_repeat('a', 64), str_repeat('b', 64), true, false);
        $first = $budget->reserve($context, 6);

        self::assertNotNull($first);
        $budget->settle($first, 6);
        self::assertNull($budget->reserve($context, 5));
        $this->assertDatabaseMissing('ai_budget_reservations', ['id' => $first->id]);
        $this->assertDatabaseHas('ai_budget_counters', [
            'scope_type' => 'global_monthly',
            'scope_key' => 'global',
            'spent_micro_usd' => 6,
        ]);
    }

    public function test_database_budget_honors_user_and_global_runtime_overrides(): void
    {
        $user = User::factory()->create();
        $userHash = hash_hmac('sha256', 'user:'.$user->id, (string) config('app.key'));
        $context = new AiRequestContext($userHash, str_repeat('b', 64), true, false);
        $budget = new DatabaseAiBudget(100, 100, 100, 100);

        DB::table('ai_user_quota_overrides')->insert([
            'user_id' => $user->id,
            'user_hash' => $userHash,
            'daily_budget_micro_usd' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        self::assertNull($budget->reserve($context, 6));

        DB::table('ai_user_quota_overrides')->where('user_id', $user->id)->update(['daily_budget_micro_usd' => 100]);
        DB::table('ai_runtime_controls')->where('scope', 'global')->update([
            'global_daily_budget_micro_usd' => 5,
            'global_monthly_budget_micro_usd' => 100,
        ]);
        self::assertNull($budget->reserve($context, 6));
    }

    public function test_user_deletion_removes_linkable_ai_audits_and_budget_counters(): void
    {
        $key = str_repeat('k', 32);
        $owner = 'fixture-owner';
        $hash = hash_hmac('sha256', 'user:'.$owner, $key);
        $now = now();
        $counterId = DB::table('ai_budget_counters')->insertGetId([
            'scope_type' => 'user_daily', 'scope_key' => $hash, 'period_start' => $now->startOfDay(),
            'period_end' => $now->endOfDay(), 'spent_micro_usd' => 5, 'reserved_micro_usd' => 0,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('ai_request_audits')->insert([
            'id' => '01890f47-0f7d-7a2b-8c3d-1234567890ab', 'request_hash' => str_repeat('c', 64),
            'user_hash' => $hash, 'prompt_template_version' => 'test', 'provider' => 'fake', 'model' => 'fake',
            'task' => 'intent', 'input_tokens' => 1, 'cached_input_tokens' => 0, 'output_tokens' => 1,
            'latency_ms' => 1, 'cache_status' => 'miss', 'validation_outcome' => 'valid',
            'repair_attempts' => 0, 'cost_micro_usd' => 1, 'created_at' => $now,
        ]);
        $user = User::factory()->create();
        DB::table('ai_user_quota_overrides')->insert([
            'user_id' => $user->id,
            'user_hash' => $hash,
            'daily_budget_micro_usd' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        (new DatabaseAiUserDataEraser($key))->erase($owner);

        $this->assertDatabaseMissing('ai_request_audits', ['user_hash' => $hash]);
        $this->assertDatabaseMissing('ai_budget_counters', ['id' => $counterId]);
        $this->assertDatabaseMissing('ai_user_quota_overrides', ['user_hash' => $hash]);
    }

    public function test_smoke_command_is_manual_and_refuses_without_confirmation(): void
    {
        $exitCode = Artisan::call('ai:smoke-openai', ['--max-cost-micro-usd' => 1000]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Refused', Artisan::output());
    }
}

<?php

namespace Tests\Feature;

use App\Modules\AI\DatabaseAiBudget;
use App\Modules\AI\DatabaseAiExecutionPolicy;
use App\Modules\AI\DatabaseAiUserDataEraser;
use Database\Seeders\PolicyDefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        (new DatabaseAiUserDataEraser($key))->erase($owner);

        $this->assertDatabaseMissing('ai_request_audits', ['user_hash' => $hash]);
        $this->assertDatabaseMissing('ai_budget_counters', ['id' => $counterId]);
    }

    public function test_smoke_command_is_manual_and_refuses_without_confirmation(): void
    {
        $exitCode = Artisan::call('ai:smoke-openai', ['--max-cost-micro-usd' => 1000]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Refused', Artisan::output());
    }
}

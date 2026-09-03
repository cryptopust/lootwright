<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_runtime_controls', function (Blueprint $table): void {
            $table->string('scope', 32)->primary();
            $table->boolean('global_enabled')->default(false);
            $table->boolean('intent_enabled')->default(false);
            $table->boolean('explanation_enabled')->default(false);
            $table->unsignedBigInteger('global_daily_budget_micro_usd')->nullable();
            $table->unsignedBigInteger('global_monthly_budget_micro_usd')->nullable();
            $table->unsignedSmallInteger('consecutive_provider_failures')->default(0);
            $table->timestampTz('circuit_open_until')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('ai_user_quota_overrides', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->char('user_hash', 64)->unique();
            $table->unsignedBigInteger('daily_budget_micro_usd');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        if (! DB::connection()->pretending()) {
            DB::table('ai_runtime_controls')->insert([
                'scope' => 'global',
                'global_enabled' => false,
                'intent_enabled' => false,
                'explanation_enabled' => false,
                'consecutive_provider_failures' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_user_quota_overrides');
        Schema::dropIfExists('ai_runtime_controls');
    }
};

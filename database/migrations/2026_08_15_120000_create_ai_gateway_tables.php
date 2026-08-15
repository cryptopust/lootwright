<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_budget_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_type', 32);
            $table->string('scope_key', 64);
            $table->timestampTz('period_start');
            $table->timestampTz('period_end');
            $table->unsignedBigInteger('spent_micro_usd')->default(0);
            $table->unsignedBigInteger('reserved_micro_usd')->default(0);
            $table->timestampsTz();
            $table->unique(['scope_type', 'scope_key', 'period_start']);
        });

        Schema::create('ai_budget_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('reserved_micro_usd');
            $table->unsignedBigInteger('actual_micro_usd')->nullable();
            $table->json('scopes');
            $table->string('status', 16);
            $table->timestampsTz();
        });

        Schema::create('ai_request_audits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('request_hash', 64);
            $table->char('user_hash', 64);
            $table->string('prompt_template_version', 64);
            $table->string('provider', 32);
            $table->string('model', 96);
            $table->string('task', 32);
            $table->unsignedInteger('input_tokens');
            $table->unsignedInteger('cached_input_tokens');
            $table->unsignedInteger('output_tokens');
            $table->unsignedInteger('latency_ms');
            $table->string('cache_status', 32);
            $table->string('validation_outcome', 32);
            $table->unsignedTinyInteger('repair_attempts');
            $table->unsignedBigInteger('cost_micro_usd');
            $table->timestampTz('created_at');
            $table->index(['task', 'created_at']);
            $table->index(['user_hash', 'created_at']);
        });

        Schema::create('ai_response_cache_keys', function (Blueprint $table): void {
            $table->id();
            $table->char('user_hash', 64);
            $table->string('cache_key', 160);
            $table->timestampTz('expires_at');
            $table->timestampsTz();
            $table->unique(['user_hash', 'cache_key']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_response_cache_keys');
        Schema::dropIfExists('ai_request_audits');
        Schema::dropIfExists('ai_budget_reservations');
        Schema::dropIfExists('ai_budget_counters');
    }
};

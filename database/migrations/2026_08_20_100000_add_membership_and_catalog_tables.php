<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 24)->default('member')->index();
            $table->string('status', 24)->default('active')->index();
            $table->timestampTz('last_login_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->string('suspension_reason', 500)->nullable();
            $table->timestampTz('deletion_requested_at')->nullable();
        });

        Schema::table('analyses', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('owner_id_hash')->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'state', 'created_at']);
        });

        Schema::create('analysis_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('flow', 32);
            $table->jsonb('safe_fields');
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->timestampTz('expires_at');
            $table->timestampsTz();
            $table->unique('user_id');
            $table->index('expires_at');
        });

        Schema::create('user_privacy_preferences', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->boolean('ai_explanation_opt_in')->default(false);
            $table->boolean('store_normalized_analysis')->default(true);
            $table->timestampTz('updated_at');
        });

        Schema::create('admin_audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('actor_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 96);
            $table->jsonb('metadata');
            $table->string('reason', 500);
            $table->uuid('correlation_id');
            $table->timestampTz('created_at');
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['target_user_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('user_privacy_preferences');
        Schema::dropIfExists('analysis_drafts');
        Schema::table('analyses', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'state', 'created_at']);
            $table->dropColumn('user_id');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn(['role', 'status', 'last_login_at', 'suspended_at', 'suspension_reason', 'deletion_requested_at']);
        });
    }
};

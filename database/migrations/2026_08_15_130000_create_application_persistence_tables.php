<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('privacy_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('access_token_hash_sha256', 64)->unique();
            $table->string('status', 24)->default('active');
            $table->timestampTz('expires_at');
            $table->timestampTz('last_seen_at');
            $table->timestampTz('deletion_requested_at')->nullable();
            $table->timestampsTz();
            $table->index(['status', 'expires_at']);
        });

        Schema::create('build_import_attempts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('artifact_id')->unique();
            $table->char('owner_id_hash', 64);
            $table->string('game_edition', 8);
            $table->string('artifact_type', 32);
            $table->char('input_hash_sha256', 64);
            $table->unsignedBigInteger('input_bytes');
            $table->string('state', 32);
            $table->string('failure_code', 128)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->timestampsTz();
            $table->foreign('artifact_id')->references('id')->on('build_artifacts')->cascadeOnDelete();
            $table->index(['owner_id_hash', 'created_at']);
            $table->index(['state', 'updated_at']);
        });

        Schema::create('normalized_build_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('artifact_id')->unique();
            $table->string('game_edition', 8);
            $table->string('adapter_key', 64);
            $table->string('parser_version', 64);
            $table->longText('payload_encrypted');
            $table->char('payload_hash_sha256', 64);
            $table->string('patch_version', 64)->nullable();
            $table->string('league', 128)->nullable();
            $table->timestampTz('retention_until')->nullable();
            $table->timestampsTz();
            $table->foreign('artifact_id')->references('id')->on('build_artifacts')->cascadeOnDelete();
            $table->index(['game_edition', 'patch_version', 'league']);
            $table->index('retention_until');
        });

        Schema::create('builds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('artifact_id')->unique();
            $table->uuid('normalized_snapshot_id')->unique();
            $table->char('owner_id_hash', 64);
            $table->string('game_edition', 8);
            $table->string('platform_realm', 16)->nullable();
            $table->string('league', 128)->nullable();
            $table->string('content_goal', 128)->nullable();
            $table->string('selected_ruleset_id', 36)->nullable();
            $table->string('selected_ruleset_version', 64)->nullable();
            $table->char('selected_ruleset_checksum_sha256', 64)->nullable();
            $table->string('deletion_status', 24)->default('active');
            $table->timestampTz('retention_until')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestampsTz();
            $table->foreign('artifact_id')->references('id')->on('build_artifacts')->cascadeOnDelete();
            $table->foreign('normalized_snapshot_id')->references('id')->on('normalized_build_snapshots')->cascadeOnDelete();
            $table->index(['owner_id_hash', 'created_at']);
            $table->index(['game_edition', 'league', 'content_goal']);
            $table->index(['deletion_status', 'retention_until']);
        });

        Schema::table('analyses', function (Blueprint $table): void {
            $table->uuid('build_id')->nullable()->after('artifact_id');
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreign('build_id')->references('id')->on('builds')->cascadeOnDelete();
            $table->index(['build_id', 'version']);
        });

        Schema::create('analysis_findings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('analysis_id');
            $table->unsignedSmallInteger('sequence');
            $table->string('code', 128);
            $table->unsignedSmallInteger('severity');
            $table->longText('payload_encrypted');
            $table->char('payload_hash_sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->unique(['analysis_id', 'sequence']);
            $table->unique(['analysis_id', 'code']);
        });

        Schema::create('analysis_recommendations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('analysis_id');
            $table->unsignedSmallInteger('sequence');
            $table->string('code', 128);
            $table->unsignedSmallInteger('priority');
            $table->longText('payload_encrypted');
            $table->char('payload_hash_sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->unique(['analysis_id', 'sequence']);
            $table->unique(['analysis_id', 'code']);
        });

        Schema::create('manual_trade_recipes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('analysis_id');
            $table->unsignedSmallInteger('sequence');
            $table->string('recipe_key', 128);
            $table->longText('payload_encrypted');
            $table->char('payload_hash_sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->unique(['analysis_id', 'sequence']);
            $table->unique(['analysis_id', 'recipe_key']);
        });

        Schema::create('analysis_provenance_references', function (Blueprint $table): void {
            $table->id();
            $table->uuid('analysis_id');
            $table->string('source_id', 64);
            $table->string('source_version', 128);
            $table->string('ruleset_id', 36);
            $table->string('ruleset_version', 64);
            $table->char('ruleset_checksum_sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->unique(['analysis_id', 'source_id', 'source_version']);
        });

        Schema::create('analysis_policy_decisions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('analysis_id');
            $table->string('source_id', 64);
            $table->string('source_version', 128);
            $table->string('capability', 40);
            $table->string('operation', 192);
            $table->string('decision', 32);
            $table->string('reason', 64);
            $table->string('policy_version', 32);
            $table->json('evidence_ids');
            $table->timestampTz('evaluated_at');
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->unique(['analysis_id', 'capability', 'operation']);
        });

        Schema::create('analysis_explanations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('analysis_id')->unique();
            $table->string('status', 32);
            $table->longText('payload_encrypted');
            $table->char('payload_hash_sha256', 64);
            $table->timestampTz('created_at');
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
        });

        Schema::create('workflow_outbox', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('topic', 64);
            $table->uuid('aggregate_id');
            $table->string('game_edition', 8);
            $table->char('ruleset_checksum_sha256', 64)->nullable();
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('available_at');
            $table->timestampTz('dispatched_at')->nullable();
            $table->text('last_error_code')->nullable();
            $table->timestampsTz();
            $table->unique(['topic', 'aggregate_id']);
            $table->index(['status', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_outbox');
        Schema::dropIfExists('analysis_explanations');
        Schema::dropIfExists('analysis_policy_decisions');
        Schema::dropIfExists('analysis_provenance_references');
        Schema::dropIfExists('manual_trade_recipes');
        Schema::dropIfExists('analysis_recommendations');
        Schema::dropIfExists('analysis_findings');
        Schema::table('analyses', function (Blueprint $table): void {
            $table->dropForeign(['build_id']);
            $table->dropIndex(['build_id', 'version']);
            $table->dropColumn(['build_id', 'lock_version']);
        });
        Schema::dropIfExists('builds');
        Schema::dropIfExists('normalized_build_snapshots');
        Schema::dropIfExists('build_import_attempts');
        Schema::dropIfExists('privacy_sessions');
    }
};

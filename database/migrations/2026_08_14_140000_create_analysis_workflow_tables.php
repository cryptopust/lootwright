<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('build_artifacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('owner_id_hash', 64);
            $table->char('idempotency_key_hash', 64)->unique();
            $table->string('game_edition', 8);
            $table->string('locale', 32);
            $table->string('artifact_type', 32);
            $table->string('blob_key', 255)->unique();
            $table->char('artifact_hash_sha256', 64);
            $table->unsignedBigInteger('artifact_bytes');
            $table->string('state', 32);
            $table->string('adapter_key', 64)->nullable();
            $table->string('parser_version', 64)->nullable();
            $table->longText('normalized_snapshot_encrypted')->nullable();
            $table->char('normalized_hash_sha256', 64)->nullable();
            $table->string('patch_version', 64)->nullable();
            $table->string('league', 128)->nullable();
            $table->string('failure_code', 128)->nullable();
            $table->unsignedSmallInteger('processing_attempts')->default(0);
            $table->timestampTz('raw_expires_at');
            $table->timestampTz('raw_deleted_at')->nullable();
            $table->timestampsTz();

            $table->index(['owner_id_hash', 'created_at']);
            $table->index(['state', 'updated_at']);
            $table->index(['raw_deleted_at', 'raw_expires_at']);
        });

        Schema::create('analyses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('artifact_id');
            $table->char('owner_id_hash', 64);
            $table->uuid('parent_analysis_id')->nullable();
            $table->string('game_edition', 8);
            $table->unsignedInteger('version');
            $table->string('state', 32);
            $table->longText('parameters_snapshot_encrypted');
            $table->char('parameters_hash_sha256', 64);
            $table->string('adapter_key', 64)->nullable();
            $table->string('parser_version', 64)->nullable();
            $table->uuid('ruleset_id')->nullable();
            $table->string('ruleset_version', 64)->nullable();
            $table->char('ruleset_checksum_sha256', 64)->nullable();
            $table->longText('input_snapshot_encrypted')->nullable();
            $table->char('input_hash_sha256', 64)->nullable();
            $table->longText('output_snapshot_encrypted')->nullable();
            $table->char('output_hash_sha256', 64)->nullable();
            $table->text('clarification_snapshot_encrypted')->nullable();
            $table->string('failure_code', 128)->nullable();
            $table->unsignedSmallInteger('processing_attempts')->default(0);
            $table->timestampsTz();

            $table->foreign('artifact_id')->references('id')->on('build_artifacts')->cascadeOnDelete();
            $table->unique(['artifact_id', 'version']);
            $table->index(['owner_id_hash', 'created_at']);
            $table->index(['state', 'updated_at']);
        });

        Schema::table('analyses', function (Blueprint $table): void {
            $table->foreign('parent_analysis_id')->references('id')->on('analyses')->nullOnDelete();
        });

        Schema::create('user_data_deletion_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('artifacts_deleted');
            $table->unsignedInteger('analyses_deleted');
            $table->string('reason', 32)->default('user_requested');
            $table->timestampTz('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_data_deletion_records');
        Schema::dropIfExists('analyses');
        Schema::dropIfExists('build_artifacts');
    }
};

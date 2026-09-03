<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_source_sync_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_key', 64);
            $table->string('source_version', 128);
            $table->string('operation', 192);
            $table->string('game_edition', 8);
            $table->string('league', 128)->nullable();
            $table->string('category', 64)->nullable();
            $table->string('status', 32);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('etag', 512)->nullable();
            $table->string('last_modified', 128)->nullable();
            $table->char('response_checksum_sha256', 64)->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('fetched_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->string('failure_code', 128)->nullable();
            $table->timestampsTz();

            $table->index(['source_key', 'game_edition', 'league', 'category', 'started_at'], 'external_source_sync_lookup');
            $table->index(['status', 'completed_at']);
        });

        Schema::create('economy_quotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('source_sync_run_id');
            $table->string('source_key', 64);
            $table->string('source_version', 128);
            $table->string('game_edition', 8);
            $table->string('league', 128);
            $table->string('category', 64);
            $table->string('external_id', 255);
            $table->string('normalized_name', 255);
            $table->string('primary_currency', 64);
            $table->string('secondary_currency', 64)->nullable();
            $table->decimal('normalized_value', 20, 6);
            $table->jsonb('confidence_metadata');
            $table->timestampTz('fetched_at');
            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->foreign('source_sync_run_id')->references('id')->on('external_source_sync_runs')->restrictOnDelete();
            $table->unique(['source_key', 'source_version', 'game_edition', 'league', 'category', 'external_id'], 'economy_quote_current_identity');
            $table->index(['game_edition', 'league', 'category', 'normalized_name'], 'economy_quote_lookup');
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economy_quotes');
        Schema::dropIfExists('external_source_sync_runs');
    }
};

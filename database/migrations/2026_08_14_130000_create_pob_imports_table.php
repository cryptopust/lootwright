<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pob_imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('owner_id_hash', 64);
            $table->char('idempotency_key_hash', 64)->unique();
            $table->char('request_hash_sha256', 64);
            $table->char('input_checksum_sha256', 64);
            $table->string('outcome', 32);
            $table->string('game_edition', 8);
            $table->string('parser_version', 64);
            $table->text('normalized_payload_encrypted');
            $table->char('deletion_token_hash_sha256', 64);
            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->index(['expires_at', 'outcome']);
            $table->index('owner_id_hash');
            $table->index('request_hash_sha256');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pob_imports');
    }
};

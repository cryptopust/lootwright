<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_drafts', function (Blueprint $table): void {
            // Existing drafts predate dual-game intake and were PoE1-only.
            $table->string('game_edition', 8)->default('poe1');
            $table->index(['game_edition', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('analysis_drafts', function (Blueprint $table): void {
            $table->dropIndex(['game_edition', 'expires_at']);
            $table->dropColumn('game_edition');
        });
    }
};

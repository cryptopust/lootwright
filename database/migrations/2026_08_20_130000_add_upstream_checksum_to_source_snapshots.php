<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->char('upstream_checksum_sha256', 64)->nullable()->after('checksum_sha256');
        });
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->unique(
                ['source_code', 'game_edition', 'upstream_checksum_sha256'],
                'source_snapshot_upstream_content_identity',
            );
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE source_snapshots ADD CONSTRAINT source_snapshot_upstream_checksum_check CHECK (upstream_checksum_sha256 ~ '^[0-9a-f]{64}$')");
        }
    }

    public function down(): void
    {
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->dropUnique('source_snapshot_upstream_content_identity');
            $table->dropColumn('upstream_checksum_sha256');
        });
    }
};

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
            $table->dropUnique('source_snapshot_upstream_content_identity');
            $table->char('source_locator_sha256', 64)->nullable();
        });
        foreach (DB::table('source_snapshots')->get(['id', 'source_url']) as $snapshot) {
            DB::table('source_snapshots')->where('id', $snapshot->id)->update([
                'source_locator_sha256' => hash('sha256', (string) $snapshot->source_url),
            ]);
        }
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->unique(
                ['source_code', 'game_edition', 'source_locator_sha256', 'upstream_checksum_sha256'],
                'source_snapshot_upstream_content_identity',
            );
        });

        Schema::table('policy_data_sources', function (Blueprint $table): void {
            $table->jsonb('game_editions')->default('[]');
            $table->text('reference_url')->nullable();
            $table->text('documentation_url')->nullable();
            $table->text('terms_url')->nullable();
            $table->string('redistribution_status', 32)->default('unknown');
            $table->string('commercial_use_status', 32)->default('unknown');
            $table->string('cache_storage_status', 32)->default('prohibited');
            $table->timestampTz('last_policy_review_at')->nullable();
        });

        Schema::create('source_import_reports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('import_run_id')->unique();
            $table->string('source_code', 64);
            $table->string('source_version', 128);
            $table->string('game_edition', 8);
            $table->string('ruleset_target', 160)->nullable();
            $table->string('status', 32);
            $table->string('policy_status', 32);
            $table->char('source_checksum_sha256', 64);
            $table->char('normalized_checksum_sha256', 64);
            $table->char('import_identity_sha256', 64)->nullable()->unique();
            $table->unsignedInteger('records_received')->default(0);
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_rejected')->default(0);
            $table->jsonb('summary');
            $table->uuid('source_snapshot_id')->nullable();
            $table->uuid('rollback_of_report_id')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->foreign('import_run_id')->references('id')->on('external_source_sync_runs')->restrictOnDelete();
            $table->foreign('source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->foreign('source_snapshot_id')->references('id')->on('source_snapshots')->restrictOnDelete();
            $table->index(['source_code', 'game_edition', 'status', 'created_at'], 'source_import_report_lookup');
        });

        Schema::table('source_import_reports', function (Blueprint $table): void {
            $table->foreign('rollback_of_report_id')->references('id')->on('source_import_reports')->restrictOnDelete();
        });

        Schema::create('source_import_staging_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('import_report_id');
            $table->string('record_key', 192);
            $table->char('checksum_sha256', 64);
            $table->string('status', 32);
            $table->string('rejection_code', 96)->nullable();
            $table->jsonb('normalized_payload')->nullable();
            $table->timestampsTz();

            $table->foreign('import_report_id')->references('id')->on('source_import_reports')->restrictOnDelete();
            $table->unique(['import_report_id', 'record_key'], 'source_staging_record_identity');
            $table->index(['import_report_id', 'status']);
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE source_import_reports ADD CONSTRAINT source_import_report_edition_check CHECK (game_edition IN ('poe1', 'poe2'))");
            DB::statement("ALTER TABLE source_import_reports ADD CONSTRAINT source_import_report_status_check CHECK (status IN ('staged', 'approved', 'rejected', 'rolled_back'))");
            DB::statement("ALTER TABLE source_import_reports ADD CONSTRAINT source_import_identity_checksum_check CHECK (import_identity_sha256 IS NULL OR import_identity_sha256 ~ '^[0-9a-f]{64}$')");
            DB::statement("ALTER TABLE source_import_staging_records ADD CONSTRAINT source_staging_status_check CHECK (status IN ('staged', 'rejected', 'approved', 'rolled_back'))");
            DB::statement('ALTER TABLE source_import_staging_records ADD CONSTRAINT source_staging_payload_size CHECK (normalized_payload IS NULL OR octet_length(normalized_payload::text) <= 262144)');
            DB::statement("ALTER TABLE source_snapshots ADD CONSTRAINT source_snapshot_locator_checksum_check CHECK (source_locator_sha256 ~ '^[0-9a-f]{64}$')");
            DB::statement('ALTER TABLE source_snapshots ALTER COLUMN source_locator_sha256 SET NOT NULL');
            DB::statement("ALTER TABLE policy_data_sources ADD CONSTRAINT source_redistribution_status_check CHECK (redistribution_status IN ('allowed', 'restricted', 'prohibited', 'unknown'))");
            DB::statement("ALTER TABLE policy_data_sources ADD CONSTRAINT source_commercial_status_check CHECK (commercial_use_status IN ('allowed', 'restricted', 'prohibited', 'unknown'))");
            DB::statement("ALTER TABLE policy_data_sources ADD CONSTRAINT source_cache_status_check CHECK (cache_storage_status IN ('allowed', 'bounded', 'prohibited', 'unknown'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('source_import_staging_records');
        Schema::dropIfExists('source_import_reports');
        Schema::table('policy_data_sources', function (Blueprint $table): void {
            $table->dropColumn([
                'game_editions', 'reference_url', 'documentation_url', 'terms_url',
                'redistribution_status', 'commercial_use_status', 'cache_storage_status',
                'last_policy_review_at',
            ]);
        });
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->dropUnique('source_snapshot_upstream_content_identity');
            $table->dropColumn('source_locator_sha256');
            $table->unique(
                ['source_code', 'game_edition', 'upstream_checksum_sha256'],
                'source_snapshot_upstream_content_identity',
            );
        });
    }
};

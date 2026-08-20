<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruleset_versions', function (Blueprint $table): void {
            $table->unique(['id', 'game_edition'], 'ruleset_version_edition_identity');
        });
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->unique(['id', 'game_edition'], 'source_snapshot_edition_identity');
        });

        Schema::create('ruleset_dataset_approvals', function (Blueprint $table): void {
            $table->uuid('ruleset_version_id')->primary();
            $table->string('game_edition', 8);
            $table->string('dataset_classification', 32);
            $table->string('provenance_status', 24);
            $table->string('compatibility_status', 32);
            $table->uuid('approved_by_source_snapshot_id')->nullable();
            $table->timestampTz('imported_at')->nullable();
            $table->timestampTz('created_at');

            $table->foreign(['ruleset_version_id', 'game_edition'], 'ruleset_approval_edition_fk')
                ->references(['id', 'game_edition'])->on('ruleset_versions')->restrictOnDelete();
            $table->foreign(['approved_by_source_snapshot_id', 'game_edition'], 'ruleset_approval_source_edition_fk')
                ->references(['id', 'game_edition'])->on('source_snapshots')->restrictOnDelete();
            $table->index(['game_edition', 'dataset_classification', 'provenance_status'], 'ruleset_approval_lookup');
        });

        Schema::create('canonical_game_data', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ruleset_version_id');
            $table->string('game_edition', 8);
            $table->string('entity_type', 48);
            $table->string('external_id', 191);
            $table->string('display_name', 255)->nullable();
            $table->string('parent_entity_type', 48)->nullable();
            $table->string('parent_external_id', 191)->nullable();
            $table->uuid('source_snapshot_id');
            $table->jsonb('payload');
            $table->char('payload_checksum_sha256', 64);
            $table->timestampTz('created_at');

            $table->unique(
                ['game_edition', 'ruleset_version_id', 'entity_type', 'external_id'],
                'canonical_game_data_identity',
            );
            $table->foreign(['ruleset_version_id', 'game_edition'], 'canonical_data_ruleset_edition_fk')
                ->references(['id', 'game_edition'])->on('ruleset_versions')->restrictOnDelete();
            $table->foreign(['source_snapshot_id', 'game_edition'], 'canonical_data_source_edition_fk')
                ->references(['id', 'game_edition'])->on('source_snapshots')->restrictOnDelete();
            $table->index(['game_edition', 'entity_type', 'external_id'], 'canonical_game_data_lookup');
        });

        Schema::table('canonical_game_data', function (Blueprint $table): void {
            $table->foreign(
                ['game_edition', 'ruleset_version_id', 'parent_entity_type', 'parent_external_id'],
                'canonical_game_data_parent_fk',
            )->references(
                ['game_edition', 'ruleset_version_id', 'entity_type', 'external_id'],
            )->on('canonical_game_data')->restrictOnDelete();
        });

        $this->installIntegrityChecks();
        $this->installImmutabilityGuards();
    }

    public function down(): void
    {
        Schema::dropIfExists('canonical_game_data');
        Schema::dropIfExists('ruleset_dataset_approvals');
        Schema::table('source_snapshots', function (Blueprint $table): void {
            $table->dropUnique('source_snapshot_edition_identity');
        });
        Schema::table('ruleset_versions', function (Blueprint $table): void {
            $table->dropUnique('ruleset_version_edition_identity');
        });
    }

    private function installIntegrityChecks(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE ruleset_dataset_approvals ADD CONSTRAINT ruleset_approval_game_check CHECK (game_edition IN ('poe1', 'poe2'))");
        DB::statement("ALTER TABLE ruleset_dataset_approvals ADD CONSTRAINT ruleset_approval_class_check CHECK (dataset_classification IN ('approved_import', 'fixture', 'unavailable'))");
        DB::statement("ALTER TABLE ruleset_dataset_approvals ADD CONSTRAINT ruleset_approval_provenance_check CHECK (provenance_status IN ('approved', 'pending', 'invalid'))");
        DB::statement("ALTER TABLE ruleset_dataset_approvals ADD CONSTRAINT ruleset_approval_compatibility_check CHECK (compatibility_status IN ('compatible', 'unsupported_patch', 'outdated', 'incompatible_parser', 'unavailable', 'invalid_provenance', 'fixture_rejected'))");
        DB::statement("ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_game_check CHECK (game_edition IN ('poe1', 'poe2'))");
        DB::statement("ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_type_check CHECK (entity_type IN ('character_class', 'ascendancy', 'passive_node', 'keystone', 'skill_gem', 'support_gem', 'item_base', 'unique_item', 'modifier_definition', 'stat_definition', 'content_goal_definition'))");
        DB::statement('ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_parent_pair_check CHECK ((parent_entity_type IS NULL) = (parent_external_id IS NULL))');
        DB::statement("ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_checksum_check CHECK (payload_checksum_sha256 ~ '^[0-9a-f]{64}$')");
        DB::statement('ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_payload_size CHECK (octet_length(payload::text) <= 262144)');
    }

    private function installImmutabilityGuards(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            foreach (['ruleset_dataset_approvals', 'canonical_game_data'] as $table) {
                DB::unprepared("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION lootwright_reject_immutable_change()");
            }

            return;
        }

        if ($driver === 'sqlite') {
            foreach (['ruleset_dataset_approvals', 'canonical_game_data'] as $table) {
                DB::unprepared("CREATE TRIGGER {$table}_immutable_update BEFORE UPDATE ON {$table} BEGIN SELECT RAISE(ABORT, '{$table} is immutable'); END");
                DB::unprepared("CREATE TRIGGER {$table}_immutable_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, '{$table} is immutable'); END");
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_data_sources', function (Blueprint $table): void {
            $table->string('technical_access', 32)->default('unknown');
            $table->string('license_identifier', 128)->default('NOASSERTION');
            $table->string('rate_limit_status', 64)->default('unknown');
            $table->string('auth_requirements', 64)->default('unknown');
            $table->string('data_quality_status', 32)->default('unknown');
            $table->string('patch_versioning_status', 32)->default('unknown');
            $table->string('update_frequency', 64)->default('unknown');
            $table->string('provenance_status', 32)->default('requires_review');
        });

        Schema::create('game_data_source_authorities', function (Blueprint $table): void {
            $table->string('game_edition', 8);
            $table->string('data_category', 48);
            $table->string('source_code', 64);
            $table->string('authority_tier', 32);
            $table->unsignedSmallInteger('priority');
            $table->boolean('enabled')->default(false);
            $table->timestampTz('reviewed_at');
            $table->timestampsTz();

            $table->primary(['game_edition', 'data_category', 'source_code'], 'game_data_source_authority_identity');
            $table->foreign('source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->unique(['game_edition', 'data_category', 'priority'], 'game_data_source_authority_priority');
        });

        Schema::create('canonical_data_conflicts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('game_edition', 8);
            $table->string('data_category', 48);
            $table->string('external_id', 191);
            $table->uuid('ruleset_version_id')->nullable();
            $table->string('left_source_code', 64);
            $table->char('left_checksum_sha256', 64);
            $table->string('right_source_code', 64);
            $table->char('right_checksum_sha256', 64);
            $table->string('reason_code', 96);
            $table->string('status', 32)->default('quarantined');
            $table->timestampTz('detected_at');
            $table->timestampTz('resolved_at')->nullable();
            $table->string('resolution_code', 96)->nullable();
            $table->timestampTz('created_at');

            $table->foreign(['ruleset_version_id', 'game_edition'], 'canonical_conflict_ruleset_edition_fk')
                ->references(['id', 'game_edition'])->on('ruleset_versions')->restrictOnDelete();
            $table->foreign('left_source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->foreign('right_source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->unique(
                ['game_edition', 'data_category', 'external_id', 'left_source_code', 'left_checksum_sha256', 'right_source_code', 'right_checksum_sha256'],
                'canonical_data_conflict_identity',
            );
            $table->index(['game_edition', 'data_category', 'status', 'detected_at'], 'canonical_data_conflict_queue');
        });

        Schema::create('source_update_observations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_code', 64);
            $table->string('game_edition', 8);
            $table->string('source_version', 128);
            $table->char('previous_checksum_sha256', 64)->nullable();
            $table->char('observed_checksum_sha256', 64)->nullable();
            $table->string('status', 32);
            $table->string('failure_code', 96)->nullable();
            $table->timestampTz('checked_at');
            $table->timestampTz('created_at');

            $table->foreign('source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->index(['source_code', 'game_edition', 'checked_at'], 'source_update_observation_lookup');
        });

        $this->installChecks();
        $this->installImmutability();
    }

    public function down(): void
    {
        Schema::dropIfExists('source_update_observations');
        Schema::dropIfExists('canonical_data_conflicts');
        Schema::dropIfExists('game_data_source_authorities');
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE canonical_game_data DROP CONSTRAINT canonical_game_data_type_check');
            DB::statement("ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_type_check CHECK (entity_type IN ('character_class', 'ascendancy', 'passive_node', 'keystone', 'skill_gem', 'support_gem', 'item_base', 'unique_item', 'modifier_definition', 'stat_definition', 'content_goal_definition'))");
        }
        Schema::table('policy_data_sources', function (Blueprint $table): void {
            $table->dropColumn([
                'technical_access', 'license_identifier', 'rate_limit_status', 'auth_requirements',
                'data_quality_status', 'patch_versioning_status', 'update_frequency', 'provenance_status',
            ]);
        });
    }

    private function installChecks(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE game_data_source_authorities ADD CONSTRAINT game_data_authority_game_check CHECK (game_edition IN ('poe1', 'poe2'))");
        DB::statement("ALTER TABLE game_data_source_authorities ADD CONSTRAINT game_data_authority_tier_check CHECK (authority_tier IN ('official_structured', 'approved_upstream', 'trusted_community', 'derived', 'heuristic'))");
        DB::statement("ALTER TABLE canonical_data_conflicts ADD CONSTRAINT canonical_conflict_game_check CHECK (game_edition IN ('poe1', 'poe2'))");
        DB::statement("ALTER TABLE canonical_data_conflicts ADD CONSTRAINT canonical_conflict_status_check CHECK (status IN ('quarantined', 'resolved', 'dismissed'))");
        DB::statement("ALTER TABLE canonical_data_conflicts ADD CONSTRAINT canonical_conflict_checksum_check CHECK (left_checksum_sha256 ~ '^[0-9a-f]{64}$' AND right_checksum_sha256 ~ '^[0-9a-f]{64}$')");
        DB::statement("ALTER TABLE source_update_observations ADD CONSTRAINT source_update_game_check CHECK (game_edition IN ('poe1', 'poe2'))");
        DB::statement("ALTER TABLE source_update_observations ADD CONSTRAINT source_update_status_check CHECK (status IN ('unchanged', 'changed_staged', 'failed', 'disabled'))");
        DB::statement("ALTER TABLE source_update_observations ADD CONSTRAINT source_update_checksum_check CHECK ((previous_checksum_sha256 IS NULL OR previous_checksum_sha256 ~ '^[0-9a-f]{64}$') AND (observed_checksum_sha256 IS NULL OR observed_checksum_sha256 ~ '^[0-9a-f]{64}$'))");

        DB::statement('ALTER TABLE canonical_game_data DROP CONSTRAINT canonical_game_data_type_check');
        $types = implode("','", array_map(static fn (CanonicalEntityType $type): string => $type->value, CanonicalEntityType::cases()));
        DB::statement("ALTER TABLE canonical_game_data ADD CONSTRAINT canonical_game_data_type_check CHECK (entity_type IN ('{$types}'))");
    }

    private function installImmutability(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            foreach (['canonical_data_conflicts', 'source_update_observations'] as $table) {
                DB::unprepared("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION lootwright_reject_immutable_change()");
            }

            return;
        }
        if ($driver === 'sqlite') {
            foreach (['canonical_data_conflicts', 'source_update_observations'] as $table) {
                DB::unprepared("CREATE TRIGGER {$table}_immutable_update BEFORE UPDATE ON {$table} BEGIN SELECT RAISE(ABORT, '{$table} is immutable'); END");
                DB::unprepared("CREATE TRIGGER {$table}_immutable_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, '{$table} is immutable'); END");
            }
        }
    }
};

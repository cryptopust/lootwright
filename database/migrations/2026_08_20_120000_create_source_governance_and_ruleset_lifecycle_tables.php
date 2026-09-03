<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policy_data_sources', function (Blueprint $table): void {
            $table->string('governance_status', 24)->default('conditional');
            $table->boolean('enabled_by_default')->default(false);
            $table->string('mvp_scope', 24)->default('candidate');
        });

        $this->canonicalizeLegacySourceIds();

        Schema::table('policy_data_source_versions', function (Blueprint $table): void {
            $table->unique(['source_id', 'id'], 'policy_source_version_owner_identity');
        });

        Schema::table('external_source_sync_runs', function (Blueprint $table): void {
            $table->unsignedBigInteger('policy_source_version_id')->nullable();
            $table->foreign('policy_source_version_id')->references('id')->on('policy_data_source_versions')->restrictOnDelete();
            $table->foreign('source_key')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->foreign(['source_key', 'policy_source_version_id'])->references(['source_id', 'id'])->on('policy_data_source_versions')->restrictOnDelete();
        });

        Schema::create('source_snapshots', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('first_import_run_id')->unique();
            $table->unsignedBigInteger('source_version_id');
            $table->string('source_code', 64);
            $table->string('game_edition', 8);
            $table->text('source_url');
            $table->string('upstream_revision', 160)->nullable();
            $table->timestampTz('retrieved_at');
            $table->char('checksum_sha256', 64);
            $table->string('content_type', 128);
            $table->string('license_identifier', 128);
            $table->string('status', 32);
            $table->string('schema_version', 64);
            $table->jsonb('normalized_payload');
            $table->timestampTz('created_at');

            $table->foreign('first_import_run_id')->references('id')->on('external_source_sync_runs')->restrictOnDelete();
            $table->foreign('source_version_id')->references('id')->on('policy_data_source_versions')->restrictOnDelete();
            $table->foreign('source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->foreign(['source_code', 'source_version_id'], 'source_snapshot_version_owner_fk')->references(['source_id', 'id'])->on('policy_data_source_versions')->restrictOnDelete();
            $table->unique(['source_code', 'game_edition', 'checksum_sha256'], 'source_snapshot_content_identity');
            $table->index(['source_code', 'game_edition', 'status', 'retrieved_at'], 'source_snapshot_lookup');
            $table->index(['source_code', 'game_edition', 'upstream_revision'], 'source_snapshot_revision_lookup');
        });

        Schema::table('external_source_sync_runs', function (Blueprint $table): void {
            $table->uuid('source_snapshot_id')->nullable();
            $table->foreign('source_snapshot_id')->references('id')->on('source_snapshots')->restrictOnDelete();
        });

        Schema::create('source_conflicts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('import_run_id')->unique();
            $table->unsignedBigInteger('source_version_id');
            $table->string('source_code', 64);
            $table->string('game_edition', 8);
            $table->uuid('existing_snapshot_id')->nullable();
            $table->string('upstream_revision', 160)->nullable();
            $table->char('candidate_checksum_sha256', 64);
            $table->string('reason_code', 96);
            $table->string('status', 32)->default('quarantined');
            $table->timestampTz('detected_at');
            $table->timestampTz('resolved_at')->nullable();
            $table->string('resolution_code', 96)->nullable();
            $table->timestampsTz();

            $table->foreign('import_run_id')->references('id')->on('external_source_sync_runs')->restrictOnDelete();
            $table->foreign('source_version_id')->references('id')->on('policy_data_source_versions')->restrictOnDelete();
            $table->foreign('source_code')->references('id')->on('policy_data_sources')->restrictOnDelete();
            $table->foreign(['source_code', 'source_version_id'], 'source_conflict_version_owner_fk')->references(['source_id', 'id'])->on('policy_data_source_versions')->restrictOnDelete();
            $table->foreign('existing_snapshot_id')->references('id')->on('source_snapshots')->restrictOnDelete();
            $table->index(['source_code', 'game_edition', 'status', 'detected_at'], 'source_conflict_queue');
        });

        Schema::create('ruleset_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('game_edition', 8);
            $table->string('version', 64);
            $table->string('patch', 32);
            $table->string('league', 128)->nullable();
            $table->string('league_key', 128)->default('');
            $table->string('parser_version', 64);
            $table->char('checksum_sha256', 64);
            $table->string('schema_version', 64);
            $table->string('status', 32);
            $table->jsonb('canonical_payload');
            $table->uuid('supersedes_ruleset_version_id')->nullable();
            $table->timestampTz('published_at');
            $table->timestampTz('created_at');

            $table->unique(['game_edition', 'version'], 'ruleset_version_identity');
            $table->unique(['game_edition', 'checksum_sha256'], 'ruleset_content_identity');
            $table->index(['game_edition', 'patch', 'league_key', 'parser_version', 'status'], 'ruleset_resolution_lookup');
        });

        Schema::table('ruleset_versions', function (Blueprint $table): void {
            $table->foreign('supersedes_ruleset_version_id')->references('id')->on('ruleset_versions')->restrictOnDelete();
        });

        Schema::create('ruleset_source_snapshots', function (Blueprint $table): void {
            $table->uuid('ruleset_version_id');
            $table->uuid('source_snapshot_id');
            $table->timestampTz('created_at');

            $table->primary(['ruleset_version_id', 'source_snapshot_id']);
            $table->foreign('ruleset_version_id')->references('id')->on('ruleset_versions')->restrictOnDelete();
            $table->foreign('source_snapshot_id')->references('id')->on('source_snapshots')->restrictOnDelete();
        });

        Schema::create('ruleset_activations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('game_edition', 8);
            $table->string('patch', 32);
            $table->string('league_key', 128)->default('');
            $table->string('parser_version', 64);
            $table->uuid('ruleset_version_id');
            $table->timestampTz('activated_at');
            $table->timestampsTz();

            $table->foreign('ruleset_version_id')->references('id')->on('ruleset_versions')->restrictOnDelete();
            $table->unique(['game_edition', 'patch', 'league_key', 'parser_version'], 'ruleset_active_scope');
            $table->index(['ruleset_version_id', 'activated_at']);
        });

        Schema::create('ruleset_activation_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('activation_id');
            $table->string('game_edition', 8);
            $table->string('patch', 32);
            $table->string('league_key', 128)->default('');
            $table->string('parser_version', 64);
            $table->uuid('previous_ruleset_version_id')->nullable();
            $table->uuid('ruleset_version_id');
            $table->string('actor_type', 32);
            $table->timestampTz('activated_at');
            $table->timestampTz('created_at');

            $table->foreign('activation_id')->references('id')->on('ruleset_activations')->restrictOnDelete();
            $table->foreign('previous_ruleset_version_id')->references('id')->on('ruleset_versions')->restrictOnDelete();
            $table->foreign('ruleset_version_id')->references('id')->on('ruleset_versions')->restrictOnDelete();
            $table->index(['game_edition', 'patch', 'league_key', 'parser_version', 'activated_at'], 'ruleset_activation_audit_lookup');
        });

        $this->installImmutabilityGuards();
        $this->installIntegrityChecks();
    }

    public function down(): void
    {
        $this->dropImmutabilityGuards();
        Schema::dropIfExists('ruleset_activation_history');
        Schema::dropIfExists('ruleset_activations');
        Schema::dropIfExists('ruleset_source_snapshots');
        Schema::dropIfExists('ruleset_versions');
        Schema::dropIfExists('source_conflicts');
        Schema::table('external_source_sync_runs', function (Blueprint $table): void {
            $table->dropForeign(['source_snapshot_id']);
            $table->dropColumn('source_snapshot_id');
            $table->dropForeign(['source_key', 'policy_source_version_id']);
            $table->dropForeign(['source_key']);
            $table->dropForeign(['policy_source_version_id']);
            $table->dropColumn('policy_source_version_id');
        });
        Schema::dropIfExists('source_snapshots');
        Schema::table('policy_data_sources', function (Blueprint $table): void {
            $table->dropColumn(['governance_status', 'enabled_by_default', 'mvp_scope']);
        });
        Schema::table('policy_data_source_versions', function (Blueprint $table): void {
            $table->dropUnique('policy_source_version_owner_identity');
        });
    }

    private function installImmutabilityGuards(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION lootwright_reject_immutable_change()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION '% is immutable', TG_TABLE_NAME USING ERRCODE = '55000';
                END;
                $$ LANGUAGE plpgsql;
                SQL);

            foreach (['source_snapshots', 'ruleset_versions', 'ruleset_source_snapshots', 'ruleset_activation_history'] as $table) {
                DB::unprepared("CREATE TRIGGER {$table}_immutable BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION lootwright_reject_immutable_change()");
            }

            DB::statement('ALTER TABLE source_snapshots ADD CONSTRAINT source_snapshot_payload_size CHECK (octet_length(normalized_payload::text) <= 2097152)');
            DB::statement('ALTER TABLE ruleset_versions ADD CONSTRAINT ruleset_payload_size CHECK (octet_length(canonical_payload::text) <= 2097152)');

            return;
        }

        if ($driver === 'sqlite') {
            foreach (['source_snapshots', 'ruleset_versions', 'ruleset_source_snapshots', 'ruleset_activation_history'] as $table) {
                DB::unprepared("CREATE TRIGGER {$table}_immutable_update BEFORE UPDATE ON {$table} BEGIN SELECT RAISE(ABORT, '{$table} is immutable'); END");
                DB::unprepared("CREATE TRIGGER {$table}_immutable_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, '{$table} is immutable'); END");
            }
        }
    }

    private function canonicalizeLegacySourceIds(): void
    {
        foreach ([
            'USER-PASTED-POB' => 'USER-POB-001',
            'USER-PASTED-ITEM' => 'USER-ITEM-TEXT-001',
            'POE-NINJA-ECONOMY-001' => 'POENINJA-ECONOMY-001',
            'POE-WIKI-CARGO-001' => 'POEWIKI-CARGO-001',
        ] as $legacy => $canonical) {
            $source = DB::table('policy_data_sources')->where('id', $legacy)->first();

            if ($source === null) {
                continue;
            }

            if (! DB::table('policy_data_sources')->where('id', $canonical)->exists()) {
                $data = get_object_vars($source);
                $data['id'] = $canonical;
                DB::table('policy_data_sources')->insert($data);
            }

            DB::table('policy_data_source_versions')->where('source_id', $legacy)->update(['source_id' => $canonical]);
            DB::table('policy_kill_switches')->where('source_id', $legacy)->update(['source_id' => $canonical]);
            DB::table('policy_decision_audits')->where('source_id', $legacy)->update(['source_id' => $canonical]);
            DB::table('external_source_sync_runs')->where('source_key', $legacy)->update(['source_key' => $canonical]);
            DB::table('economy_quotes')->where('source_key', $legacy)->update(['source_key' => $canonical]);
            DB::table('policy_data_sources')->where('id', $legacy)->delete();
        }
    }

    private function dropImmutabilityGuards(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS lootwright_reject_immutable_change() CASCADE');
        }
    }

    private function installIntegrityChecks(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['external_source_sync_runs', 'source_snapshots', 'source_conflicts', 'ruleset_versions', 'ruleset_activations', 'ruleset_activation_history'] as $table) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$table}_game_edition_check CHECK (game_edition IN ('poe1', 'poe2'))");
        }

        DB::statement("ALTER TABLE policy_data_sources ADD CONSTRAINT policy_source_governance_status_check CHECK (governance_status IN ('allowed', 'conditional', 'prohibited'))");
        DB::statement("ALTER TABLE source_snapshots ADD CONSTRAINT source_snapshot_status_check CHECK (status IN ('valid', 'rejected'))");
        DB::statement("ALTER TABLE source_conflicts ADD CONSTRAINT source_conflict_status_check CHECK (status IN ('quarantined', 'resolved', 'dismissed'))");
        DB::statement("ALTER TABLE ruleset_versions ADD CONSTRAINT ruleset_status_check CHECK (status = 'published')");
        DB::statement("ALTER TABLE ruleset_versions ADD CONSTRAINT ruleset_league_key_check CHECK (league_key = COALESCE(league, ''))");
        DB::statement("ALTER TABLE source_snapshots ADD CONSTRAINT source_snapshot_checksum_check CHECK (checksum_sha256 ~ '^[0-9a-f]{64}$')");
        DB::statement("ALTER TABLE source_conflicts ADD CONSTRAINT source_conflict_checksum_check CHECK (candidate_checksum_sha256 ~ '^[0-9a-f]{64}$')");
        DB::statement("ALTER TABLE ruleset_versions ADD CONSTRAINT ruleset_checksum_check CHECK (checksum_sha256 ~ '^[0-9a-f]{64}$')");
    }
};

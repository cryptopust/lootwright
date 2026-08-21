<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PostgreSqlMigrationCompatibilityTest extends TestCase
{
    public function test_canonical_game_data_migration_compiles_edition_scoped_postgresql_constraints(): void
    {
        config(['database.default' => 'pgsql']);

        $queries = DB::connection('pgsql')->pretend(static function (): void {
            $migration = require database_path('migrations/2026_08_20_140000_create_canonical_game_data_tables.php');
            $migration->up();
        });
        $sql = implode("\n", array_column($queries, 'query'));

        self::assertStringContainsString('create table "canonical_game_data"', $sql);
        self::assertStringContainsString('canonical_data_ruleset_edition_fk', $sql);
        self::assertStringContainsString('canonical_data_source_edition_fk', $sql);
        self::assertStringContainsString('canonical_game_data_parent_fk', $sql);
        self::assertStringContainsString('canonical_game_data_immutable', $sql);
        self::assertStringContainsString('jsonb', $sql);
    }

    public function test_analysis_primary_key_is_created_before_its_self_referencing_foreign_key(): void
    {
        config(['database.default' => 'pgsql']);

        $queries = DB::connection('pgsql')->pretend(static function (): void {
            $migration = require database_path('migrations/2026_08_14_140000_create_analysis_workflow_tables.php');
            $migration->up();
        });
        $statements = array_column($queries, 'query');
        $primaryKey = array_search('alter table "analyses" add primary key ("id")', $statements, true);
        $selfReference = array_search(
            'alter table "analyses" add constraint "analyses_parent_analysis_id_foreign" foreign key ("parent_analysis_id") references "analyses" ("id") on delete set null',
            $statements,
            true,
        );

        self::assertIsInt($primaryKey);
        self::assertIsInt($selfReference);
        self::assertLessThan($selfReference, $primaryKey, 'The primary key must be created before the self-referencing foreign key.');
    }

    public function test_external_source_staging_migration_compiles_postgresql_constraints_and_self_reference_order(): void
    {
        config(['database.default' => 'pgsql']);

        $queries = DB::connection('pgsql')->pretend(static function (): void {
            $migration = require database_path('migrations/2026_08_21_090000_add_external_source_registry_and_staging.php');
            $migration->up();
        });
        $sql = implode("\n", array_column($queries, 'query'));

        self::assertStringContainsString('create table "source_import_reports"', $sql);
        self::assertStringContainsString('"import_identity_sha256" char(64) null', $sql);
        self::assertStringContainsString('source_import_reports_import_identity_sha256_unique', $sql);
        self::assertStringContainsString('source_import_reports_rollback_of_report_id_foreign', $sql);
        self::assertStringContainsString('source_staging_payload_size', $sql);
        self::assertStringContainsString('source_snapshot_locator_checksum_check', $sql);
    }

    public function test_ai_runtime_control_migration_compiles_postgresql_constraints(): void
    {
        config(['database.default' => 'pgsql']);

        $up = DB::connection('pgsql')->pretend(static function (): void {
            $migration = require database_path('migrations/2026_08_21_120000_create_ai_runtime_control_tables.php');
            $migration->up();
        });
        $upSql = implode("\n", array_column($up, 'query'));

        self::assertStringContainsString('create table "ai_runtime_controls"', $upSql);
        self::assertStringContainsString('create table "ai_user_quota_overrides"', $upSql);
        self::assertStringContainsString('foreign key ("user_id") references "users" ("id") on delete cascade', $upSql);
        self::assertStringContainsString('foreign key ("updated_by_user_id") references "users" ("id") on delete set null', $upSql);
    }

    /**
     * This is intentionally opt-in: CI supplies a disposable PostgreSQL
     * database. It never runs destructive migrations against a shared remote.
     */
    public function test_real_postgresql_enforces_the_analysis_parent_constraint_when_enabled(): void
    {
        if (config('database.default') !== 'pgsql' || ! (bool) config('database.postgres_migration_integration')) {
            self::markTestSkipped('A disposable PostgreSQL integration database is required.');
        }

        $connection = DB::connection('pgsql');
        $connection->statement('drop table if exists analyses cascade');
        $connection->statement('drop table if exists build_artifacts cascade');
        $migration = require database_path('migrations/2026_08_14_140000_create_analysis_workflow_tables.php');
        $migration->up();

        self::assertTrue(Schema::connection('pgsql')->hasTable('analyses'));
        $primary = $connection->selectOne("select contype from pg_constraint where conrelid = 'analyses'::regclass and contype = 'p'");
        self::assertNotNull($primary);
        self::assertTrue($connection->selectOne("select attnotnull from pg_attribute where attrelid = 'analyses'::regclass and attname = 'parent_analysis_id'")->attnotnull === false);

        $artifact = '11111111-1111-4111-8111-111111111111';
        $parent = '22222222-2222-4222-8222-222222222222';
        $child = '33333333-3333-4333-8333-333333333333';
        $now = now();
        $connection->table('build_artifacts')->insert(['id' => $artifact, 'owner_id_hash' => str_repeat('a', 64), 'idempotency_key_hash' => str_repeat('b', 64), 'game_edition' => 'poe1', 'locale' => 'en', 'artifact_type' => 'pob', 'blob_key' => 'pg-test', 'artifact_hash_sha256' => str_repeat('c', 64), 'artifact_bytes' => 1, 'state' => 'submitted', 'raw_expires_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        foreach ([[$parent, null, 1], [$child, $parent, 2]] as [$id, $parentId, $version]) {
            $connection->table('analyses')->insert(['id' => $id, 'artifact_id' => $artifact, 'owner_id_hash' => str_repeat('a', 64), 'parent_analysis_id' => $parentId, 'game_edition' => 'poe1', 'version' => $version, 'state' => 'submitted', 'parameters_snapshot_encrypted' => 'x', 'parameters_hash_sha256' => str_repeat('d', 64), 'created_at' => $now, 'updated_at' => $now]);
        }
        try {
            $connection->table('analyses')->insert(['id' => '44444444-4444-4444-8444-444444444444', 'artifact_id' => $artifact, 'owner_id_hash' => str_repeat('a', 64), 'parent_analysis_id' => '55555555-5555-4555-8555-555555555555', 'game_edition' => 'poe1', 'version' => 3, 'state' => 'submitted', 'parameters_snapshot_encrypted' => 'x', 'parameters_hash_sha256' => str_repeat('d', 64), 'created_at' => $now, 'updated_at' => $now]);
            self::fail('An invalid analysis parent must be rejected by PostgreSQL.');
        } catch (QueryException) {
            self::addToAssertionCount(1);
        }
        $connection->table('analyses')->where('id', $parent)->delete();
        self::assertNull($connection->table('analyses')->where('id', $child)->value('parent_analysis_id'));
        $migration->down();
        self::assertFalse(Schema::connection('pgsql')->hasTable('analyses'));
    }

    public function test_all_migrations_fresh_rollback_and_reapply_on_disposable_postgresql_when_enabled(): void
    {
        if (config('database.default') !== 'pgsql' || ! (bool) config('database.postgres_migration_integration')) {
            self::markTestSkipped('A disposable PostgreSQL integration database is required.');
        }

        $connection = DB::connection('pgsql');
        self::assertSame(0, Artisan::call('migrate:fresh', ['--database' => 'pgsql', '--force' => true]));
        self::assertTrue(Schema::connection('pgsql')->hasTable('admin_audit_logs'));
        self::assertTrue(Schema::connection('pgsql')->hasColumn('analyses', 'user_id'));
        foreach (['source_snapshots', 'source_conflicts', 'ruleset_versions', 'ruleset_activations', 'ruleset_activation_history', 'ruleset_dataset_approvals', 'canonical_game_data', 'source_import_reports', 'source_import_staging_records', 'ai_runtime_controls', 'ai_user_quota_overrides'] as $table) {
            self::assertTrue(Schema::connection('pgsql')->hasTable($table));
        }

        $foreignKeyTypes = $connection->select(<<<'SQL'
            select
                child_table.relname as child_table,
                child_attribute.attname as child_column,
                pg_catalog.format_type(child_attribute.atttypid, child_attribute.atttypmod) as child_type,
                parent_table.relname as parent_table,
                parent_attribute.attname as parent_column,
                pg_catalog.format_type(parent_attribute.atttypid, parent_attribute.atttypmod) as parent_type
            from pg_constraint constraint_record
            join pg_class child_table on child_table.oid = constraint_record.conrelid
            join pg_class parent_table on parent_table.oid = constraint_record.confrelid
            join lateral unnest(constraint_record.conkey) with ordinality child_key(attnum, position) on true
            join lateral unnest(constraint_record.confkey) with ordinality parent_key(attnum, position) on parent_key.position = child_key.position
            join pg_attribute child_attribute on child_attribute.attrelid = child_table.oid and child_attribute.attnum = child_key.attnum
            join pg_attribute parent_attribute on parent_attribute.attrelid = parent_table.oid and parent_attribute.attnum = parent_key.attnum
            where constraint_record.contype = 'f'
              and child_table.relname in ('external_source_sync_runs', 'source_snapshots', 'source_conflicts', 'ruleset_versions', 'ruleset_source_snapshots', 'ruleset_activations', 'ruleset_activation_history', 'ruleset_dataset_approvals', 'canonical_game_data', 'source_import_reports', 'source_import_staging_records', 'ai_runtime_controls', 'ai_user_quota_overrides')
            SQL);

        self::assertNotEmpty($foreignKeyTypes);
        foreach ($foreignKeyTypes as $foreignKey) {
            self::assertSame(
                $foreignKey->parent_type,
                $foreignKey->child_type,
                "{$foreignKey->child_table}.{$foreignKey->child_column} must exactly match {$foreignKey->parent_table}.{$foreignKey->parent_column}.",
            );
        }

        self::assertNotNull($connection->selectOne("select tgname from pg_trigger where tgrelid = 'source_snapshots'::regclass and tgname = 'source_snapshots_immutable' and not tgisinternal"));
        self::assertNotNull($connection->selectOne("select tgname from pg_trigger where tgrelid = 'ruleset_versions'::regclass and tgname = 'ruleset_versions_immutable' and not tgisinternal"));
        self::assertNotNull($connection->selectOne("select tgname from pg_trigger where tgrelid = 'canonical_game_data'::regclass and tgname = 'canonical_game_data_immutable' and not tgisinternal"));

        self::assertSame(0, Artisan::call('migrate:rollback', ['--database' => 'pgsql', '--force' => true]));
        self::assertFalse(Schema::connection('pgsql')->hasTable('users'));

        self::assertSame(0, Artisan::call('migrate', ['--database' => 'pgsql', '--force' => true]));
        self::assertTrue(Schema::connection('pgsql')->hasTable('admin_audit_logs'));
    }
}

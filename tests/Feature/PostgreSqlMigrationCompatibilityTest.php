<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Tests\TestCase;

final class PostgreSqlMigrationCompatibilityTest extends TestCase
{
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

    /**
     * This is intentionally opt-in: CI supplies a disposable PostgreSQL
     * database. It never runs destructive migrations against a shared remote.
     */
    public function test_real_postgresql_enforces_the_analysis_parent_constraint_when_enabled(): void
    {
        if (config('database.default') !== 'pgsql' || ! (bool) env('POSTGRES_MIGRATION_INTEGRATION', false)) {
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
}

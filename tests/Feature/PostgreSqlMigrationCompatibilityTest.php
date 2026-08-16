<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
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
        self::assertLessThan($selfReference, $primaryKey);
    }
}

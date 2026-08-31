<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AccountSavedRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_records_are_owner_scoped_and_can_be_removed(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        [$buildId, $analysisId] = $this->fixture($owner);

        $this->actingAs($other)->postJson("/api/saved/builds/{$buildId}")->assertNotFound();
        $this->actingAs($other)->postJson("/api/saved/analyses/{$analysisId}")->assertNotFound();
        $this->actingAs($owner)->postJson("/api/saved/builds/{$buildId}", ['label' => 'Bossing'])->assertCreated();
        $this->actingAs($owner)->postJson("/api/saved/analyses/{$analysisId}")->assertCreated();
        $this->actingAs($owner)->getJson('/api/saved')->assertJsonPath('builds.0.id', $buildId)->assertJsonPath('analyses.0.id', $analysisId);
        $this->actingAs($other)->getJson('/api/saved')->assertJsonCount(0, 'builds')->assertJsonCount(0, 'analyses');
        $this->actingAs($owner)->deleteJson("/api/saved/builds/{$buildId}")->assertOk();
        $this->actingAs($owner)->deleteJson("/api/saved/analyses/{$analysisId}")->assertOk();
        $this->assertDatabaseCount('user_saved_builds', 0);
        $this->assertDatabaseCount('user_saved_analyses', 0);
    }

    /** @return array{string,string} */
    private function fixture(User $owner): array
    {
        $artifact = '018f0000-0000-7000-8000-000000000120';
        $snapshot = '018f0000-0000-7000-8000-000000000121';
        $build = '018f0000-0000-7000-8000-000000000122';
        $analysis = '018f0000-0000-7000-8000-000000000123';
        $now = now();
        $hash = hash_hmac('sha256', 'owner:'.$owner->id, (string) config('app.key'));
        DB::table('build_artifacts')->insert(['id' => $artifact, 'owner_id_hash' => $hash, 'idempotency_key_hash' => str_repeat('a', 64), 'game_edition' => 'poe1', 'locale' => 'en-US', 'artifact_type' => 'pob', 'blob_key' => 'saved-test', 'artifact_hash_sha256' => str_repeat('b', 64), 'artifact_bytes' => 1, 'state' => 'completed', 'raw_expires_at' => $now->copy()->addHour(), 'created_at' => $now, 'updated_at' => $now]);
        DB::table('normalized_build_snapshots')->insert(['id' => $snapshot, 'artifact_id' => $artifact, 'game_edition' => 'poe1', 'adapter_key' => 'test', 'parser_version' => 'test', 'payload_encrypted' => 'x', 'payload_hash_sha256' => str_repeat('c', 64), 'created_at' => $now, 'updated_at' => $now]);
        DB::table('builds')->insert(['id' => $build, 'artifact_id' => $artifact, 'normalized_snapshot_id' => $snapshot, 'owner_id_hash' => $hash, 'game_edition' => 'poe1', 'deletion_status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('analyses')->insert(['id' => $analysis, 'artifact_id' => $artifact, 'build_id' => $build, 'owner_id_hash' => $hash, 'user_id' => $owner->id, 'game_edition' => 'poe1', 'version' => 1, 'state' => 'completed', 'parameters_snapshot_encrypted' => 'x', 'parameters_hash_sha256' => str_repeat('d', 64), 'created_at' => $now, 'updated_at' => $now]);

        return [$build, $analysis];
    }
}

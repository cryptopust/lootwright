<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CatalogAndMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_endpoint_is_versioned_and_cacheable(): void
    {
        $response = $this->getJson('/api/catalog/poe1/character-options')->assertOk()->assertJsonPath('game', 'poe1')->assertJsonCount(7, 'classes');
        $etag = $response->headers->get('ETag');
        self::assertNotNull($etag);
        $this->withHeader('If-None-Match', $etag)->get('/api/catalog/poe1/character-options')->assertStatus(304);
    }

    public function test_poe2_catalog_is_game_scoped_and_has_a_distinct_etag(): void
    {
        $poe1 = $this->getJson('/api/catalog/poe1/character-options')->assertOk();
        $poe2 = $this->getJson('/api/catalog/poe2/character-options')
            ->assertOk()->assertJsonPath('game', 'poe2')->assertJsonPath('early_access', true)->assertJsonCount(12, 'classes');
        self::assertNotSame($poe1->headers->get('ETag'), $poe2->headers->get('ETag'));
        $this->withHeader('If-None-Match', $poe2->headers->get('ETag'))->get('/api/catalog/poe2/character-options')->assertStatus(304);
        $this->getJson('/api/catalog/poe3/character-options')->assertNotFound();
    }

    public function test_member_cannot_access_admin_and_suspended_session_is_terminated(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get('/admin')->assertForbidden();
        $member->forceFill(['status' => UserStatus::Suspended])->save();
        $this->actingAs($member)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_cannot_manage_another_admin_but_super_admin_can_change_role(): void
    {
        $admin = User::factory()->admin()->create(['two_factor_confirmed_at' => now()]);
        $otherAdmin = User::factory()->admin()->create(['two_factor_confirmed_at' => now()]);
        $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => time()])->put("/admin/users/{$otherAdmin->id}/status", ['status' => 'suspended', 'reason' => 'Policy reason'])->assertForbidden();

        $super = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);
        $this->actingAs($super)->withSession(['auth.password_confirmed_at' => time()])->put("/admin/users/{$otherAdmin->id}/role", ['role' => 'member', 'reason' => 'Approved role change'])->assertRedirect();
        self::assertSame(UserRole::Member, $otherAdmin->fresh()->role);
        $this->assertDatabaseHas('admin_audit_logs', ['actor_user_id' => $super->id, 'target_user_id' => $otherAdmin->id, 'action' => 'user.role.changed']);
    }

    public function test_last_active_super_admin_is_protected_and_recent_password_is_required(): void
    {
        $super = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);
        $target = User::factory()->create();
        $this->actingAs($super)->put("/admin/users/{$target->id}/role", ['role' => 'admin', 'reason' => 'No recent password'])->assertRedirect('/user/confirm-password');

        $second = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);
        $this->actingAs($super)->withSession(['auth.password_confirmed_at' => time()])->put("/admin/users/{$second->id}/status", ['status' => 'suspended', 'reason' => 'Temporary suspension'])->assertRedirect();
        self::assertSame(UserStatus::Suspended, $second->fresh()->status);
    }

    public function test_last_active_super_admin_cannot_be_demoted_or_suspended(): void
    {
        $super = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);
        $session = ['auth.password_confirmed_at' => time()];

        $this->actingAs($super)->withSession($session)->put("/admin/users/{$super->id}/role", ['role' => 'member', 'reason' => 'Invalid self change'])->assertForbidden();

        $other = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now(), 'status' => UserStatus::Suspended]);
        $this->actingAs($other)->withSession($session)->put("/admin/users/{$super->id}/status", ['status' => 'suspended', 'reason' => 'Would remove last active operator'])->assertRedirect('/login');

        self::assertSame(UserRole::SuperAdmin, $super->fresh()->role);
        self::assertSame(UserStatus::Active, $super->fresh()->status);
    }

    public function test_last_active_super_admin_cannot_delete_account_or_disable_two_factor(): void
    {
        $super = User::factory()->superAdmin()->create(['two_factor_confirmed_at' => now()]);

        $this->actingAs($super)->deleteJson('/api/user-data')->assertUnprocessable();
        self::assertSame(UserStatus::Active, $super->fresh()->status);

        $this->actingAs($super)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->delete('/user/two-factor-authentication')
            ->assertForbidden();
    }

    public function test_member_cannot_read_another_members_analysis_or_draft(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        DB::table('analysis_drafts')->insert(['id' => '018f0000-0000-7000-8000-000000000001', 'user_id' => $owner->id, 'flow' => 'plan', 'safe_fields' => '{}', 'current_step' => 2, 'expires_at' => now()->addDay(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($other)->getJson('/api/analysis-draft')->assertOk()->assertJsonPath('draft', null);
        $this->actingAs($other)->get('/analyses/018f0000-0000-7000-8000-000000000099')->assertNotFound();
    }

    public function test_member_can_delete_only_their_analysis_after_password_confirmation(): void
    {
        Storage::fake('analysis-artifacts');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $artifactId = '018f0000-0000-7000-8000-000000000010';
        $analysisId = '018f0000-0000-7000-8000-000000000011';
        $now = now();
        DB::table('build_artifacts')->insert(['id' => $artifactId, 'owner_id_hash' => str_repeat('a', 64), 'idempotency_key_hash' => str_repeat('b', 64), 'game_edition' => 'poe1', 'locale' => 'tr-TR', 'artifact_type' => 'wizard_plan', 'blob_key' => 'build-artifacts/test.payload', 'artifact_hash_sha256' => str_repeat('c', 64), 'artifact_bytes' => 1, 'state' => 'submitted', 'raw_expires_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('analyses')->insert(['id' => $analysisId, 'artifact_id' => $artifactId, 'owner_id_hash' => str_repeat('a', 64), 'user_id' => $owner->id, 'game_edition' => 'poe1', 'version' => 1, 'state' => 'submitted', 'parameters_snapshot_encrypted' => 'x', 'parameters_hash_sha256' => str_repeat('d', 64), 'created_at' => $now, 'updated_at' => $now]);

        $this->actingAs($other)->withSession(['auth.password_confirmed_at' => time()])->delete("/analyses/{$analysisId}")->assertNotFound();
        $this->assertDatabaseHas('analyses', ['id' => $analysisId]);
        $this->flushSession()->actingAs($owner)->delete("/analyses/{$analysisId}")->assertRedirect('/user/confirm-password');
        $this->actingAs($owner)->withSession(['auth.password_confirmed_at' => time()])->delete("/analyses/{$analysisId}")->assertRedirect('/analyses');
        $this->assertDatabaseMissing('analyses', ['id' => $analysisId]);
    }

    public function test_sensitive_fields_are_not_shared_in_inertia_auth_props(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->missing('auth.user.password')->missing('auth.user.remember_token')->missing('auth.user.two_factor_secret')->missing('auth.user.two_factor_recovery_codes'));
    }

    public function test_promote_command_requires_verified_existing_user_and_force_in_production(): void
    {
        $unverified = User::factory()->unverified()->create();
        self::assertSame(1, Artisan::call('lootwright:admin:promote', ['email' => $unverified->email]));
        self::assertSame(UserRole::Member, $unverified->fresh()->role);

        $verified = User::factory()->create();
        $this->app->detectEnvironment(static fn (): string => 'production');
        self::assertSame(1, Artisan::call('lootwright:admin:promote', ['email' => $verified->email]));
        self::assertSame(0, Artisan::call('lootwright:admin:promote', ['email' => $verified->email, '--force' => true]));
        self::assertSame(UserRole::SuperAdmin, $verified->fresh()->role);
        $this->assertDatabaseHas('admin_audit_logs', ['actor_user_id' => $verified->id, 'target_user_id' => $verified->id, 'action' => 'user.super_admin.promoted']);
    }
}

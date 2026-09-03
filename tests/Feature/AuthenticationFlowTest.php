<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_login_logout_and_verification_notification(): void
    {
        Notification::fake();
        $this->post('/register', ['name' => 'Yeni Üye', 'email' => 'member@example.test', 'password' => 'StrongPassword123!', 'password_confirmation' => 'StrongPassword123!'])->assertRedirect('/dashboard');
        $user = User::query()->where('email', 'member@example.test')->firstOrFail();
        self::assertSame('member', $user->role->value);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $user->markEmailAsVerified();
        $this->post('/login', ['email' => $user->email, 'password' => 'StrongPassword123!'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_request_and_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = app('auth.password.broker')->createToken($user);
        $this->post('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'AnotherStrong123!', 'password_confirmation' => 'AnotherStrong123!'])->assertSessionHas('status');
        self::assertTrue(Hash::check('AnotherStrong123!', $user->fresh()->password));
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Administration\AdminAuditLogger;
use Illuminate\Console\Command;

/**
 * Operator-only escape hatch for verifying a controlled QA/operations account.
 * Normal users must continue to use the emailed verification link.
 */
final class VerifyUserEmail extends Command
{
    protected $signature = 'lootwright:user:verify-email
        {email : Exact email address of the controlled QA/operations account}
        {--force : Confirm the explicit production verification action}
        {--reason= : Bounded operational reason for the audit record}';

    protected $description = 'Verify one existing user email from an operator CLI session.';

    public function handle(AdminAuditLogger $audit): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Production email verification requires --force.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error('No registered user was found for that email.');

            return self::FAILURE;
        }

        $reason = trim((string) ($this->option('reason') ?: 'Explicit operator QA email verification'));

        if ($user->hasVerifiedEmail()) {
            $this->info('The user email is already verified.');

            return self::SUCCESS;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $audit->record($user, 'user.email_verified.operator', $reason, $user, ['method' => 'artisan']);
        $this->info('The user email was verified by explicit operator action.');

        return self::SUCCESS;
    }
}

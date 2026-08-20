<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use App\Modules\Administration\AdminAuditLogger;
use Illuminate\Console\Command;

final class PromoteSuperAdmin extends Command
{
    protected $signature = 'lootwright:admin:promote {email} {--force : Confirm the privileged production change}';

    protected $description = 'Promote an existing verified user to super-admin without handling a password.';

    public function handle(AdminAuditLogger $audit): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Production promotion requires --force.');

            return self::FAILURE;
        }
        $user = User::query()->where('email', mb_strtolower((string) $this->argument('email')))->first();
        if (! $user instanceof User) {
            $this->error('No registered user was found for that email.');

            return self::FAILURE;
        }
        if (! $user->hasVerifiedEmail()) {
            $this->error('The user must verify their email before promotion.');

            return self::FAILURE;
        }
        $user->forceFill(['role' => UserRole::SuperAdmin, 'status' => UserStatus::Active])->save();
        $audit->record($user, 'user.super_admin.promoted', 'Explicit operator CLI promotion', $user, ['method' => 'artisan']);
        $this->info('The verified user was promoted to super-admin.');

        return self::SUCCESS;
    }
}

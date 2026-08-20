<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $role = $user instanceof User ? UserRole::tryFrom((string) $user->getRawOriginal('role')) : null;
        $status = $user instanceof User ? UserStatus::tryFrom((string) $user->getRawOriginal('status')) : null;
        $authenticatedUser = $user instanceof User && $role !== null && $status !== null ? $user : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $authenticatedUser === null ? null : [
                    'id' => $authenticatedUser->getAuthIdentifier(),
                    'name' => $authenticatedUser->name,
                    'email' => $authenticatedUser->email,
                    'role' => $role->value,
                    'status' => $status->value,
                    'email_verified_at' => $authenticatedUser->email_verified_at?->toIso8601String(),
                    'two_factor_enabled' => $authenticatedUser->two_factor_confirmed_at !== null,
                ],
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserRole;
use App\Models\UserStatus;
use App\Modules\Administration\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120'], 'role' => ['nullable', Rule::enum(UserRole::class)], 'status' => ['nullable', Rule::enum(UserStatus::class)]]);
        $query = User::query()->withCount('analyses');
        if ($filters['search'] ?? null) {
            $needle = mb_strtolower($filters['search']);
            $query->where(fn ($q) => $q->whereRaw('lower(name) like ?', ['%'.$needle.'%'])->orWhereRaw('lower(email) like ?', ['%'.$needle.'%']));
        }
        if ($filters['role'] ?? null) {
            $query->where('role', $filters['role']);
        }
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }

        return Inertia::render('Admin/Users', ['users' => $query->latest()->paginate(25)->withQueryString(), 'filters' => $filters]);
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        return Inertia::render('Admin/UserShow', ['managedUser' => $user->only(['id', 'name', 'email', 'role', 'status', 'email_verified_at', 'last_login_at', 'created_at', 'suspended_at', 'suspension_reason']), 'analysisCount' => DB::table('analyses')->where('user_id', $user->id)->count()]);
    }

    public function status(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('suspend', $user);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended'])], 'reason' => ['required', 'string', 'between:3,500']]);
        if ($user->isSuperAdmin() && $data['status'] === 'suspended') {
            $this->assertAnotherActiveSuperAdmin($user);
        }
        $user->forceFill(['status' => $data['status'], 'suspended_at' => $data['status'] === 'suspended' ? now() : null, 'suspension_reason' => $data['status'] === 'suspended' ? $data['reason'] : null])->save();
        if ($data['status'] === 'suspended') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        $audit->record($request->user(), 'user.status.changed', $data['reason'], $user, ['status' => $data['status']]);

        return back();
    }

    public function role(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('changeRole', $user);
        $data = $request->validate(['role' => ['required', Rule::enum(UserRole::class)], 'reason' => ['required', 'string', 'between:3,500']]);
        if ($user->isSuperAdmin() && $data['role'] !== UserRole::SuperAdmin->value) {
            $this->assertAnotherActiveSuperAdmin($user);
        }
        $user->forceFill(['role' => $data['role']])->save();
        $audit->record($request->user(), 'user.role.changed', $data['reason'], $user, ['role' => $data['role']]);

        return back();
    }

    private function assertAnotherActiveSuperAdmin(User $target): void
    {
        abort_if(User::query()->where('role', UserRole::SuperAdmin)->where('status', UserStatus::Active)->whereKeyNot($target->id)->doesntExist(), 422, 'Son aktif super-admin korunmalıdır.');
    }
}

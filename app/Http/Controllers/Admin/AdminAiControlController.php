<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Administration\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class AdminAiControlController extends Controller
{
    public function settings(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $maximumDaily = max(1, (int) config('ai.budgets_micro_usd.global_daily'));
        $maximumMonthly = max(1, (int) config('ai.budgets_micro_usd.global_monthly'));
        $data = $request->validate([
            'global_enabled' => ['required', 'boolean'],
            'intent_enabled' => ['required', 'boolean'],
            'explanation_enabled' => ['required', 'boolean'],
            'global_daily_budget_micro_usd' => ['required', 'integer', 'between:1,'.$maximumDaily],
            'global_monthly_budget_micro_usd' => ['required', 'integer', 'between:1,'.$maximumMonthly],
            'reason' => ['required', 'string', 'between:3,500'],
        ]);

        DB::transaction(function () use ($audit, $data, $request): void {
            $control = DB::table('ai_runtime_controls')->where('scope', 'global')->lockForUpdate()->first();
            if ($control === null) {
                throw new RuntimeException('AI runtime controls are unavailable.');
            }
            DB::table('ai_runtime_controls')->where('scope', 'global')->update([
                'global_enabled' => $data['global_enabled'],
                'intent_enabled' => $data['intent_enabled'],
                'explanation_enabled' => $data['explanation_enabled'],
                'global_daily_budget_micro_usd' => $data['global_daily_budget_micro_usd'],
                'global_monthly_budget_micro_usd' => $data['global_monthly_budget_micro_usd'],
                'updated_by_user_id' => $request->user()->id,
                'updated_at' => now(),
            ]);
            $audit->record($request->user(), 'ai.runtime.updated', $data['reason'], metadata: [
                'global_enabled' => (bool) $data['global_enabled'],
                'intent_enabled' => (bool) $data['intent_enabled'],
                'explanation_enabled' => (bool) $data['explanation_enabled'],
                'global_daily_budget_micro_usd' => (int) $data['global_daily_budget_micro_usd'],
                'global_monthly_budget_micro_usd' => (int) $data['global_monthly_budget_micro_usd'],
            ]);
        }, 3);

        return back();
    }

    public function userQuota(Request $request, User $user, AdminAuditLogger $audit): RedirectResponse
    {
        $maximum = max(1, (int) config('ai.budgets_micro_usd.per_user_daily'));
        $data = $request->validate([
            'daily_budget_micro_usd' => ['required', 'integer', 'between:1,'.$maximum],
            'reason' => ['required', 'string', 'between:3,500'],
        ]);
        $hash = hash_hmac('sha256', 'user:'.$user->id, (string) config('app.key'));
        DB::transaction(function () use ($audit, $data, $hash, $request, $user): void {
            DB::table('ai_user_quota_overrides')->upsert([[
                'user_id' => $user->id,
                'user_hash' => $hash,
                'daily_budget_micro_usd' => $data['daily_budget_micro_usd'],
                'updated_by_user_id' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['user_id'], ['user_hash', 'daily_budget_micro_usd', 'updated_by_user_id', 'updated_at']);
            $audit->record($request->user(), 'ai.user_quota.updated', $data['reason'], $user, [
                'daily_budget_micro_usd' => (int) $data['daily_budget_micro_usd'],
            ]);
        }, 3);

        return back();
    }
}

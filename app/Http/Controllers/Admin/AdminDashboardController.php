<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $runtime = DB::table('ai_runtime_controls')->where('scope', 'global')->first();
        abort_if($runtime === null, 503, 'AI runtime controls are unavailable.');
        $dailyCeiling = (int) config('ai.budgets_micro_usd.global_daily');
        $monthlyCeiling = (int) config('ai.budgets_micro_usd.global_monthly');
        $today = DB::table('ai_request_audits')->where('created_at', '>=', now()->startOfDay());
        $month = DB::table('ai_request_audits')->where('created_at', '>=', now()->startOfMonth());

        return Inertia::render('Admin/Dashboard', [
            'users' => ['total' => DB::table('users')->count(), 'active' => DB::table('users')->where('status', 'active')->count(), 'suspended' => DB::table('users')->where('status', 'suspended')->count(), 'verified' => DB::table('users')->whereNotNull('email_verified_at')->count()],
            'analyses' => DB::table('analyses')->select('state', DB::raw('count(*) as total'))->groupBy('state')->pluck('total', 'state'),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'killSwitches' => DB::table('policy_kill_switches')->where('active', true)->count(),
            'catalogs' => [['game' => 'poe1', 'version' => '3.28', 'data_version' => 'poe1-3.28-2026-08-20'], ['game' => 'poe2', 'version' => '0.5', 'data_version' => 'poe2-0.5-2026-08-20']],
            'source' => DB::table('external_source_sync_runs')->latest('started_at')->first(['source_key', 'status', 'completed_at', 'failure_code']),
            'aiCostMicroUsd' => (int) DB::table('ai_request_audits')->sum('cost_micro_usd'),
            'aiRuntime' => [
                'global_enabled' => (bool) $runtime->global_enabled,
                'intent_enabled' => (bool) $runtime->intent_enabled,
                'explanation_enabled' => (bool) $runtime->explanation_enabled,
                'circuit_open_until' => $runtime->circuit_open_until,
                'consecutive_provider_failures' => (int) $runtime->consecutive_provider_failures,
                'global_daily_budget_micro_usd' => min((int) ($runtime->global_daily_budget_micro_usd ?? $dailyCeiling), $dailyCeiling),
                'global_monthly_budget_micro_usd' => min((int) ($runtime->global_monthly_budget_micro_usd ?? $monthlyCeiling), $monthlyCeiling),
                'global_daily_budget_ceiling_micro_usd' => $dailyCeiling,
                'global_monthly_budget_ceiling_micro_usd' => $monthlyCeiling,
            ],
            'aiUsage' => [
                'calls_today' => (int) (clone $today)->count(),
                'input_tokens_today' => (int) (clone $today)->sum('input_tokens'),
                'output_tokens_today' => (int) (clone $today)->sum('output_tokens'),
                'cost_today_micro_usd' => (int) (clone $today)->sum('cost_micro_usd'),
                'cost_month_micro_usd' => (int) (clone $month)->sum('cost_micro_usd'),
                'cache_hits_today' => (int) (clone $today)->where('cache_status', '!=', 'miss')->count(),
                'failures_today' => (int) (clone $today)->where('validation_outcome', '!=', 'valid')->count(),
            ],
        ]);
    }
}

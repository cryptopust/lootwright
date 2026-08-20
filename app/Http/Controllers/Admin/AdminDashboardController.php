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
        return Inertia::render('Admin/Dashboard', [
            'users' => ['total' => DB::table('users')->count(), 'active' => DB::table('users')->where('status', 'active')->count(), 'suspended' => DB::table('users')->where('status', 'suspended')->count(), 'verified' => DB::table('users')->whereNotNull('email_verified_at')->count()],
            'analyses' => DB::table('analyses')->select('state', DB::raw('count(*) as total'))->groupBy('state')->pluck('total', 'state'),
            'failedJobs' => DB::table('failed_jobs')->count(),
            'killSwitches' => DB::table('policy_kill_switches')->where('active', true)->count(),
            'catalogs' => [['game' => 'poe1', 'version' => '3.28', 'data_version' => 'poe1-3.28-2026-08-20'], ['game' => 'poe2', 'version' => '0.5', 'data_version' => 'poe2-0.5-2026-08-20']],
            'source' => DB::table('external_source_sync_runs')->latest('started_at')->first(['source_key', 'status', 'completed_at', 'failure_code']),
            'aiCostMicroUsd' => (int) DB::table('ai_request_audits')->sum('cost_micro_usd'),
        ]);
    }
}

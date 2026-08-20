<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = DB::table('analyses')->where('user_id', $request->user()->id);
        $counts = (clone $query)->selectRaw('count(*) as total')
            ->selectRaw("sum(case when state in ('queued','processing') then 1 else 0 end) as pending")
            ->selectRaw("sum(case when state = 'completed' then 1 else 0 end) as completed")
            ->selectRaw("sum(case when state = 'failed' then 1 else 0 end) as failed")->first();

        $latestSourceRun = DB::table('external_source_sync_runs')->where('status', 'success')->latest('completed_at')->first(['source_key', 'league', 'fetched_at', 'expires_at']);
        $userHash = hash_hmac('sha256', 'user:'.$request->user()->id, (string) config('app.key'));

        return Inertia::render('Member/Dashboard', [
            'counts' => ['total' => (int) ($counts->total ?? 0), 'pending' => (int) ($counts->pending ?? 0), 'completed' => (int) ($counts->completed ?? 0), 'failed' => (int) ($counts->failed ?? 0)],
            'recent' => (clone $query)->latest()->limit(5)->get(['id', 'state', 'game_edition', 'created_at']),
            'source' => $latestSourceRun,
            'aiUsageMicroUsd' => (int) DB::table('ai_request_audits')->where('user_hash', $userHash)->where('created_at', '>=', now()->startOfDay())->sum('cost_micro_usd'),
        ]);
    }
}

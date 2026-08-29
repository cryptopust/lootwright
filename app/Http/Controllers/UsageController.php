<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class UsageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $hash = hash_hmac('sha256', 'user:'.$request->user()->id, (string) config('app.key'));
        $base = DB::table('ai_request_audits')->where('user_hash', $hash);
        $today = (clone $base)->where('created_at', '>=', now()->startOfDay());
        $month = (clone $base)->where('created_at', '>=', now()->startOfMonth());

        return Inertia::render('Usage', ['usage' => ['calls_today' => (int) (clone $today)->count(), 'cost_today_micro_usd' => (int) (clone $today)->sum('cost_micro_usd'), 'calls_month' => (int) (clone $month)->count(), 'cost_month_micro_usd' => (int) (clone $month)->sum('cost_micro_usd'), 'input_tokens_month' => (int) (clone $month)->sum('input_tokens'), 'output_tokens_month' => (int) (clone $month)->sum('output_tokens'), 'failures_month' => (int) (clone $month)->where('validation_outcome', '!=', 'valid')->count()]]);
    }
}

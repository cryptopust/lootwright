<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Manage per-user bookmarks for builds and analyses. */
final class SavedRecordsController extends Controller
{
    public function page(Request $request): Response
    {
        $userId = (int) $request->user()->id;

        return Inertia::render('Member/Saved', [
            'builds' => DB::table('user_saved_builds as saved')->join('builds', 'builds.id', '=', 'saved.build_id')->where('saved.user_id', $userId)->latest('saved.created_at')->get(['saved.build_id as id', 'saved.label', 'saved.created_at', 'builds.game_edition', 'builds.league', 'builds.content_goal']),
            'analyses' => DB::table('user_saved_analyses as saved')->join('analyses', 'analyses.id', '=', 'saved.analysis_id')->where('saved.user_id', $userId)->latest('saved.created_at')->get(['saved.analysis_id as id', 'saved.created_at', 'analyses.state', 'analyses.game_edition', 'analyses.version']),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        return response()->json([
            'builds' => DB::table('user_saved_builds as saved')
                ->join('builds', 'builds.id', '=', 'saved.build_id')
                ->where('saved.user_id', $userId)
                ->latest('saved.created_at')
                ->get(['saved.build_id as id', 'saved.label', 'saved.created_at', 'builds.game_edition', 'builds.league']),
            'analyses' => DB::table('user_saved_analyses as saved')
                ->join('analyses', 'analyses.id', '=', 'saved.analysis_id')
                ->where('saved.user_id', $userId)
                ->latest('saved.created_at')
                ->get(['saved.analysis_id as id', 'saved.created_at', 'analyses.state', 'analyses.game_edition', 'analyses.version']),
        ], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function saveBuild(Request $request, string $buildId): JsonResponse
    {
        $data = $request->validate(['label' => ['nullable', 'string', 'max:120']]);
        $userId = (int) $request->user()->id;
        $owned = DB::table('builds')
            ->join('analyses', 'analyses.build_id', '=', 'builds.id')
            ->where('builds.id', $buildId)->where('analyses.user_id', $userId)->exists();
        abort_unless($owned, 404);
        DB::table('user_saved_builds')->upsert([
            ['user_id' => $userId, 'build_id' => $buildId, 'label' => $data['label'] ?? null, 'created_at' => now()],
        ], ['user_id', 'build_id'], ['label']);

        return response()->json(['status' => 'saved', 'build_id' => $buildId], 201, ['Cache-Control' => 'no-store, private']);
    }

    public function unsaveBuild(Request $request, string $buildId): JsonResponse
    {
        DB::table('user_saved_builds')->where('user_id', $request->user()->id)->where('build_id', $buildId)->delete();

        return response()->json(['status' => 'removed'], headers: ['Cache-Control' => 'no-store, private']);
    }

    public function saveAnalysis(Request $request, string $analysisId): JsonResponse
    {
        $userId = (int) $request->user()->id;
        abort_unless(DB::table('analyses')->where('id', $analysisId)->where('user_id', $userId)->exists(), 404);
        DB::table('user_saved_analyses')->insertOrIgnore([
            'user_id' => $userId,
            'analysis_id' => $analysisId,
            'created_at' => now(),
        ]);

        return response()->json(['status' => 'saved', 'analysis_id' => $analysisId], 201, ['Cache-Control' => 'no-store, private']);
    }

    public function unsaveAnalysis(Request $request, string $analysisId): JsonResponse
    {
        DB::table('user_saved_analyses')->where('user_id', $request->user()->id)->where('analysis_id', $analysisId)->delete();

        return response()->json(['status' => 'removed'], headers: ['Cache-Control' => 'no-store, private']);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Lootwright\Application\Workflow\Ports\ArtifactStorage;

final class MemberAnalysisController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(['queued', 'processing', 'completed', 'failed', 'clarification_required'])], 'search' => ['nullable', 'string', 'max:64'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $query = DB::table('analyses')->where('user_id', $request->user()->id);
        if ($filters['status'] ?? null) {
            $query->where('state', $filters['status']);
        }
        if ($filters['search'] ?? null) {
            $query->where('id', 'like', '%'.$filters['search'].'%');
        }
        if ($filters['from'] ?? null) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to'] ?? null) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return Inertia::render('Member/Analyses', ['analyses' => $query->latest()->paginate(20)->withQueryString(), 'filters' => $filters]);
    }

    public function show(Request $request, string $analysis): Response
    {
        $row = DB::table('analyses')->where('id', $analysis)->where('user_id', $request->user()->id)->first(['id', 'state', 'game_edition', 'version', 'failure_code', 'created_at', 'updated_at']);
        abort_if($row === null, 404);

        return Inertia::render('Member/AnalysisShow', ['analysis' => $row]);
    }

    public function destroy(Request $request, string $analysis, ArtifactStorage $storage): RedirectResponse
    {
        $artifact = DB::table('analyses')
            ->join('build_artifacts', 'build_artifacts.id', '=', 'analyses.artifact_id')
            ->where('analyses.id', $analysis)
            ->where('analyses.user_id', $request->user()->id)
            ->first(['build_artifacts.id', 'build_artifacts.blob_key']);
        abort_if($artifact === null, 404);

        $storage->delete($artifact->blob_key);
        DB::table('build_artifacts')->where('id', $artifact->id)->delete();

        return redirect()->route('member.analyses.index')->with('status', 'Analiz silindi.');
    }
}

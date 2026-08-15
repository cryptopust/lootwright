<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;

class PolicyEvidenceController extends Controller
{
    public function index(): JsonResponse
    {
        $evidence = DB::table('policy_permission_evidence as evidence')
            ->join('policy_data_source_versions as versions', 'versions.id', '=', 'evidence.source_version_id')
            ->orderBy('versions.source_id')
            ->orderBy('evidence.id')
            ->get([
                'evidence.id',
                'versions.source_id',
                'versions.version as source_version',
                'evidence.evidence_url',
                'evidence.retrieved_at',
                'evidence.effective_from',
                'evidence.effective_until',
                'evidence.permission_status',
                'evidence.attribution_required',
                'evidence.attribution_notice',
                'evidence.summary',
                'evidence.reviewer_role',
            ]);

        return response()->json(['evidence' => $evidence], headers: ['Cache-Control' => 'no-store']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:96', 'regex:/^[A-Z][A-Z0-9-]{2,95}$/'],
            'source_id' => ['required', 'string', 'exists:policy_data_sources,id'],
            'source_version' => ['required', 'string', 'max:128'],
            'evidence_url' => ['required', 'url:https', 'max:2048', $this->approvedEvidenceHost(...)],
            'retrieved_at' => ['required', 'date'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after:effective_from'],
            'permission_status' => ['required', Rule::enum(PermissionStatus::class)],
            'attribution_required' => ['required', 'boolean'],
            'attribution_notice' => ['nullable', 'string', 'max:1000', 'required_if:attribution_required,true'],
            'summary' => ['required', 'string', 'max:2000'],
        ]);

        $versionId = DB::table('policy_data_source_versions')
            ->where('source_id', $validated['source_id'])
            ->where('version', $validated['source_version'])
            ->value('id');

        abort_if($versionId === null, 422, 'The selected source version does not exist.');

        DB::table('policy_permission_evidence')->updateOrInsert(
            ['id' => $validated['id']],
            [
                'source_version_id' => $versionId,
                'evidence_url' => $validated['evidence_url'],
                'retrieved_at' => $validated['retrieved_at'],
                'effective_from' => $validated['effective_from'],
                'effective_until' => $validated['effective_until'] ?? null,
                'permission_status' => $validated['permission_status'],
                'attribution_required' => $validated['attribution_required'],
                'attribution_notice' => $validated['attribution_notice'] ?? null,
                'summary' => $validated['summary'],
                'reviewer_role' => 'policy_admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        return response()->json(['status' => 'stored'], 201, ['Cache-Control' => 'no-store']);
    }

    private function approvedEvidenceHost(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The evidence URL is invalid.');

            return;
        }

        $parts = parse_url($value);
        $host = is_array($parts) && is_string($parts['host'] ?? null) ? strtolower($parts['host']) : null;
        $port = is_array($parts) ? ($parts['port'] ?? null) : null;
        $hasCredentials = is_array($parts) && (isset($parts['user']) || isset($parts['pass']));
        $allowed = config('security.policy_admin.evidence_hosts', []);

        if ($host === null || ! is_array($allowed) || ! in_array($host, $allowed, true)
            || ($port !== null && $port !== 443) || $hasCredentials
        ) {
            $fail('The evidence URL host is not approved.');
        }
    }
}

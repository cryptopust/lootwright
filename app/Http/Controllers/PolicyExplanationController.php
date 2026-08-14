<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PolicyExplanationController extends Controller
{
    public function __invoke(string $sourceId): JsonResponse
    {
        $source = DB::table('policy_data_sources')->where('id', $sourceId)->first();

        abort_if($source === null, 404);

        $rules = DB::table('policy_rules as rules')
            ->join('policy_data_source_versions as versions', 'versions.id', '=', 'rules.source_version_id')
            ->where('versions.source_id', $sourceId)
            ->where('rules.enabled', true)
            ->orderBy('rules.capability')
            ->orderBy('rules.operation')
            ->get([
                'versions.version as source_version',
                'rules.capability',
                'rules.operation',
                'rules.decision',
                'rules.explanation',
                'rules.policy_version',
            ]);

        return response()->json([
            'source' => [
                'id' => $source->id,
                'name' => $source->name,
                'source_type' => $source->source_type,
                'access_mode' => $source->access_mode,
                'description' => $source->description,
            ],
            'rules' => $rules,
            'notice' => 'A require_review result does not permit execution.',
        ], headers: ['Cache-Control' => 'public, max-age=300']);
    }
}

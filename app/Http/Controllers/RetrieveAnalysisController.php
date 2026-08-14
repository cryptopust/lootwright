<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkflowAnalysisResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\RetrieveAnalysis;

final class RetrieveAnalysisController extends Controller
{
    public function __invoke(string $analysisId, Request $request, RetrieveAnalysis $useCase): JsonResponse
    {
        $owner = $request->user()?->getAuthIdentifier();
        $ownerId = is_int($owner) || is_string($owner) ? (string) $owner : '';

        try {
            $analysis = $useCase->handle($ownerId, $analysisId);
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['analysis' => WorkflowAnalysisResource::make($analysis)], headers: ['Cache-Control' => 'no-store']);
    }
}

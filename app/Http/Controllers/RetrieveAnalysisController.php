<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorkflowAnalysisResource;
use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\RetrieveAnalysis;

final class RetrieveAnalysisController extends Controller
{
    public function __invoke(string $analysisId, Request $request, RetrieveAnalysis $useCase, PrivacyPrincipalResolver $principals): JsonResponse
    {
        $ownerId = $principals->resolve($request);

        if ($ownerId === null) {
            return response()->json(['status' => 'unauthorized'], 401, ['Cache-Control' => 'no-store']);
        }

        try {
            $analysis = $useCase->handle($ownerId, $analysisId);
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['analysis' => WorkflowAnalysisResource::make($analysis)], headers: ['Cache-Control' => 'no-store']);
    }
}

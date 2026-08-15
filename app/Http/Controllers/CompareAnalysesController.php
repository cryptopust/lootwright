<?php

namespace App\Http\Controllers;

use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\CompareAnalysisVersions;

final class CompareAnalysesController extends Controller
{
    public function __invoke(string $leftId, string $rightId, Request $request, CompareAnalysisVersions $useCase, PrivacyPrincipalResolver $principals): JsonResponse
    {
        $ownerId = $principals->resolve($request);

        if ($ownerId === null) {
            return response()->json(['status' => 'unauthorized'], 401, ['Cache-Control' => 'no-store']);
        }

        try {
            $comparison = $useCase->handle($ownerId, $leftId, $rightId);
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        } catch (InvalidWorkflowInput) {
            return response()->json(['status' => 'invalid', 'message' => 'The comparison request is invalid.'], 422, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['comparison' => $comparison], headers: ['Cache-Control' => 'no-store']);
    }
}

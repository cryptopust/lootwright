<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\CompareAnalysisVersions;

final class CompareAnalysesController extends Controller
{
    public function __invoke(string $leftId, string $rightId, Request $request, CompareAnalysisVersions $useCase): JsonResponse
    {
        $owner = $request->user()?->getAuthIdentifier();
        $ownerId = is_int($owner) || is_string($owner) ? (string) $owner : '';

        try {
            $comparison = $useCase->handle($ownerId, $leftId, $rightId);
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        } catch (InvalidWorkflowInput $exception) {
            return response()->json(['status' => 'invalid', 'message' => $exception->getMessage()], 422, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['comparison' => $comparison], headers: ['Cache-Control' => 'no-store']);
    }
}

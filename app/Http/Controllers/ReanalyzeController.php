<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReanalyzeRequest;
use App\Http\Resources\WorkflowAnalysisResource;
use Illuminate\Http\JsonResponse;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\ReanalyzeWithGoalsOrBudget;

final class ReanalyzeController extends Controller
{
    public function __invoke(string $analysisId, ReanalyzeRequest $request, ReanalyzeWithGoalsOrBudget $useCase): JsonResponse
    {
        $owner = $request->user()?->getAuthIdentifier();
        $ownerId = is_int($owner) || is_string($owner) ? (string) $owner : '';
        $goals = $request->validated('goals', []);
        $goals = is_array($goals) ? array_values(array_filter($goals, is_string(...))) : [];
        $budget = $request->validated('budget_amount');
        $currency = $request->validated('budget_currency');

        try {
            $analysis = $useCase->handle($ownerId, $analysisId, new AnalysisParameters(
                $goals,
                is_string($budget) ? $budget : null,
                is_string($currency) ? $currency : null,
            ));
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        } catch (InvalidWorkflowInput $exception) {
            return response()->json(['status' => 'invalid', 'message' => $exception->getMessage()], 422, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['analysis' => WorkflowAnalysisResource::make($analysis)], 202, ['Cache-Control' => 'no-store']);
    }
}

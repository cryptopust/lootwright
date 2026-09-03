<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReanalyzeRequest;
use App\Http\Resources\WorkflowAnalysisResource;
use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Exception\WorkflowNotFound;
use Lootwright\Application\Workflow\UseCases\ReanalyzeWithGoalsOrBudget;

final class ReanalyzeController extends Controller
{
    public function __invoke(string $analysisId, ReanalyzeRequest $request, ReanalyzeWithGoalsOrBudget $useCase, PrivacyPrincipalResolver $principals): JsonResponse
    {
        $ownerId = $principals->resolve($request) ?? '';
        $goals = $request->validated('goals', []);
        $goals = is_array($goals) ? array_values(array_filter($goals, is_string(...))) : [];
        $budget = $request->validated('budget_amount');
        $currency = $request->validated('budget_currency');
        $lockedItems = $request->validated('locked_items', []);
        $lockedItems = is_array($lockedItems) ? array_values(array_filter($lockedItems, is_string(...))) : [];

        try {
            $analysis = $useCase->handle($ownerId, $analysisId, new AnalysisParameters(
                $goals,
                is_string($budget) ? $budget : null,
                is_string($currency) ? $currency : null,
                null,
                $lockedItems,
            ));
        } catch (WorkflowNotFound) {
            return response()->json(['status' => 'not_found'], 404, ['Cache-Control' => 'no-store']);
        } catch (InvalidWorkflowInput) {
            return response()->json(['status' => 'invalid', 'message' => 'The reanalysis request is invalid.'], 422, ['Cache-Control' => 'no-store']);
        }

        return response()->json(['analysis' => WorkflowAnalysisResource::make($analysis)], 202, ['Cache-Control' => 'no-store']);
    }
}

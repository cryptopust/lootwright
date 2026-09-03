<?php

namespace Lootwright\Application\AIGateway\Services;

use Lootwright\Application\AIGateway\DTO\FollowUpQuestionRequest;
use Lootwright\Application\AIGateway\Ports\FollowUpInterpreter;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\UseCases\ReanalyzeWithGoalsOrBudget;
use Lootwright\Application\Workflow\UseCases\RetrieveAnalysis;
use RuntimeException;

final readonly class ProcessFollowUpQuestion
{
    public function __construct(
        private RetrieveAnalysis $analyses,
        private FollowUpInterpreter $interpreter,
        private ReanalyzeWithGoalsOrBudget $reanalyze,
    ) {}

    /** @return array<string,mixed> */
    public function handle(string $ownerId, string $analysisId, FollowUpQuestionRequest $request): array
    {
        $analysis = $this->analyses->handle($ownerId, $analysisId);
        if ($analysis->state !== AnalysisState::Completed || $analysis->edition !== $request->edition) {
            throw new RuntimeException('Only a completed analysis of the requested edition may be followed up.');
        }

        $outcome = $this->interpreter->interpretFollowUp($request);
        if ($outcome->action === null || $outcome->action->action === 'unsupported') {
            return ['status' => 'unsupported', 'message' => $outcome->message, 'ai' => $outcome];
        }

        $action = $outcome->action;
        if ($action->action === 'change_budget') {
            [$amount, $currency] = $this->budget($action->value);
            $parameters = json_decode($analysis->parametersSnapshot, true, flags: JSON_THROW_ON_ERROR);
            $goals = is_array($parameters['goals'] ?? null) ? array_values(array_filter($parameters['goals'], 'is_string')) : ['follow-up'];
            $locked = is_array($parameters['locked_items'] ?? null) ? array_values(array_filter($parameters['locked_items'], 'is_string')) : [];
            $newAnalysis = $this->reanalyze->handle($ownerId, $analysisId, new AnalysisParameters($goals, $amount, $currency, null, $locked));

            return ['status' => 'recalculation_queued', 'message' => 'A deterministic recalculation was queued with the requested budget.', 'analysis_id' => $newAnalysis->id, 'ai' => $outcome];
        }

        if (in_array($action->action, ['keep_item', 'remove_item'], true)) {
            $parameters = json_decode($analysis->parametersSnapshot, true, flags: JSON_THROW_ON_ERROR);
            $goals = is_array($parameters['goals'] ?? null) ? array_values(array_filter($parameters['goals'], 'is_string')) : ['follow-up'];
            $budget = is_array($parameters['budget'] ?? null) ? $parameters['budget'] : null;
            $locked = is_array($parameters['locked_items'] ?? null) ? array_values(array_filter($parameters['locked_items'], 'is_string')) : [];
            if ($action->action === 'keep_item' && ! in_array($action->referenceId, $locked, true)) {
                $locked[] = $action->referenceId;
            }
            if ($action->action === 'remove_item') {
                $locked = array_values(array_filter($locked, static fn (string $id): bool => $id !== $action->referenceId));
            }
            $newAnalysis = $this->reanalyze->handle($ownerId, $analysisId, new AnalysisParameters($goals, $budget['amount'] ?? null, $budget['currency'] ?? null, null, $locked));

            return ['status' => 'recalculation_queued', 'message' => 'A deterministic recalculation was queued with the requested item constraint.', 'analysis_id' => $newAnalysis->id, 'ai' => $outcome];
        }

        return ['status' => 'deterministic_context_only', 'message' => 'This question is answered from the existing deterministic result; no canonical data was changed.', 'ai' => $outcome, 'analysis' => $analysis->id];
    }

    /** @return array{?string,?string} */
    private function budget(string $value): array
    {
        if (preg_match('/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?\s+([A-Z][A-Z0-9_]{2,11})$/D', trim($value), $m) !== 1) {
            throw new RuntimeException('The follow-up budget is not a canonical amount and currency.');
        }

        return [$m[1], $m[2]];
    }
}

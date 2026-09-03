<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiFollowUpRequest;
use App\Modules\AI\AiRequestContextFactory;
use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Lootwright\Application\AIGateway\DTO\FollowUpQuestionRequest;
use Lootwright\Application\AIGateway\Services\ProcessFollowUpQuestion;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\UseCases\RetrieveAnalysis;
use Throwable;

final class AiFollowUpController extends Controller
{
    public function __invoke(string $analysisId, AiFollowUpRequest $request, ProcessFollowUpQuestion $process, RetrieveAnalysis $analyses, PrivacyPrincipalResolver $principals, AiRequestContextFactory $contexts): JsonResponse
    {
        $ownerId = $principals->resolve($request) ?? '';
        try {
            $analysis = $analyses->handle($ownerId, $analysisId);
            if ($analysis->state !== AnalysisState::Completed) {
                return response()->json(['status' => 'invalid'], 422);
            }
            $output = is_string($analysis->outputSnapshot) ? json_decode($analysis->outputSnapshot, true, flags: JSON_THROW_ON_ERROR) : [];
            $parameters = json_decode($analysis->parametersSnapshot, true, flags: JSON_THROW_ON_ERROR);
            $refs = [];
            foreach (($output['recommendations'] ?? []) as $row) {
                if (is_array($row) && is_string($row['code'] ?? null)) {
                    $refs[] = $row['code'];
                }
            }
            foreach (($parameters['locked_items'] ?? []) as $id) {
                if (is_string($id)) {
                    $refs[] = $id;
                }
            }
            $refs = array_values(array_unique($refs));
            $context = $contexts->make($ownerId, (string) $request->ip(), (bool) $request->validated('ai_opt_in'), (bool) $request->validated('cache_permitted', false));
            $result = $process->handle($ownerId, $analysisId, new FollowUpQuestionRequest(
                (string) $request->validated('question'), $analysis->edition, (string) ($analysis->rulesetVersion ?? ''), $refs,
                ['findings' => array_slice((array) ($output['findings'] ?? []), 0, 30), 'recommendations' => array_slice((array) ($output['recommendations'] ?? []), 0, 30), 'parameters' => $parameters], $context,
            ));

            return response()->json($result, 200, ['Cache-Control' => 'no-store']);
        } catch (Throwable) {
            return response()->json(['status' => 'fallback', 'message' => 'The deterministic analysis remains available; follow-up could not be processed.'], 422, ['Cache-Control' => 'no-store']);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAnalysisRequest;
use Illuminate\Http\JsonResponse;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\SubmitBuildArtifactCommand;
use Lootwright\Application\Workflow\Exception\IdempotencyConflict;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\UseCases\SubmitBuildArtifact;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Locale;
use RuntimeException;

final class SubmitAnalysisController extends Controller
{
    public function __invoke(SubmitAnalysisRequest $request, SubmitBuildArtifact $useCase): JsonResponse
    {
        $owner = $request->user()?->getAuthIdentifier();
        $ownerId = is_int($owner) || is_string($owner) ? (string) $owner : '';
        $locale = Locale::from($request->string('locale')->toString());

        if ($locale->isFailure() || ! $locale->value() instanceof Locale) {
            throw new RuntimeException('Validated locale could not be constructed.');
        }

        try {
            $receipt = $useCase->handle(new SubmitBuildArtifactCommand(
                $ownerId,
                $request->string('idempotency_key')->toString(),
                GameEdition::from($request->string('game')->toString()),
                $locale->value(),
                $request->string('artifact_type')->toString(),
                $request->string('artifact')->toString(),
                new AnalysisParameters(
                    $this->goals($request->validated('goals', [])),
                    $this->nullableString($request->validated('budget_amount')),
                    $this->nullableString($request->validated('budget_currency')),
                ),
            ));
        } catch (IdempotencyConflict) {
            return response()->json(['status' => 'idempotency_conflict'], 409, ['Cache-Control' => 'no-store']);
        } catch (InvalidWorkflowInput $exception) {
            return response()->json(['status' => 'invalid', 'message' => $exception->getMessage()], 422, ['Cache-Control' => 'no-store']);
        } catch (TransientWorkflowFailure) {
            return response()->json(['status' => 'temporarily_unavailable'], 503, ['Cache-Control' => 'no-store']);
        }

        return response()->json([
            'status' => $receipt->state->value,
            'artifact_id' => $receipt->artifactId,
            'analysis_id' => $receipt->analysisId,
            'idempotent_replay' => $receipt->replayed,
        ], $receipt->replayed ? 200 : 202, ['Cache-Control' => 'no-store']);
    }

    /** @return list<string> */
    private function goals(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $goal): bool => is_string($goal)));
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}

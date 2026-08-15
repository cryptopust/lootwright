<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAnalysisRequest;
use App\Modules\Identity\PrivacyPrincipalResolver;
use Illuminate\Http\JsonResponse;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\AnalysisSelection;
use Lootwright\Application\Workflow\DTO\SubmitBuildArtifactCommand;
use Lootwright\Application\Workflow\Exception\IdempotencyConflict;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\UseCases\SubmitBuildArtifact;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use Lootwright\Domain\Shared\Value\Locale;
use RuntimeException;

final class SubmitAnalysisController extends Controller
{
    public function __invoke(SubmitAnalysisRequest $request, SubmitBuildArtifact $useCase, PrivacyPrincipalResolver $principals): JsonResponse
    {
        $ownerId = $principals->resolve($request) ?? '';
        $edition = GameEdition::from($request->string('game')->toString());
        $locale = Locale::from($request->string('locale')->toString());

        if ($locale->isFailure() || ! $locale->value() instanceof Locale) {
            throw new RuntimeException('Validated locale could not be constructed.');
        }

        try {
            $receipt = $useCase->handle(new SubmitBuildArtifactCommand(
                $ownerId,
                $request->string('idempotency_key')->toString(),
                $edition,
                $locale->value(),
                $request->string('artifact_type')->toString(),
                $request->string('artifact')->toString(),
                new AnalysisParameters(
                    $this->goals($request->validated('goals', [])),
                    $this->nullableString($request->validated('budget_amount')),
                    $this->nullableString($request->validated('budget_currency')),
                    new AnalysisSelection(
                        PlatformRealm::from($request->string('platform_realm', $edition === GameEdition::Poe2 ? 'poe2' : 'pc')->toString()),
                        $this->nullableString($request->validated('league')),
                        $this->nullableString($request->validated('content_goal')),
                        $this->nullableString($request->validated('ruleset_id')),
                        $this->nullableString($request->validated('ruleset_version')),
                        $this->nullableString($request->validated('ruleset_checksum_sha256')),
                        $request->boolean('ai_explanation_opt_in'),
                    ),
                ),
            ));
        } catch (IdempotencyConflict) {
            return response()->json(['status' => 'idempotency_conflict'], 409, ['Cache-Control' => 'no-store']);
        } catch (InvalidWorkflowInput) {
            return response()->json(['status' => 'invalid', 'message' => 'The analysis request is invalid.'], 422, ['Cache-Control' => 'no-store']);
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

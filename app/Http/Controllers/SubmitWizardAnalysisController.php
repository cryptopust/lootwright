<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitWizardAnalysisRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Workflow\DTO\AnalysisParameters;
use Lootwright\Application\Workflow\DTO\AnalysisSelection;
use Lootwright\Application\Workflow\DTO\SubmitBuildArtifactCommand;
use Lootwright\Application\Workflow\UseCases\SubmitBuildArtifact;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use Lootwright\Domain\Shared\Value\Locale;

final class SubmitWizardAnalysisController extends Controller
{
    public function __invoke(SubmitWizardAnalysisRequest $request, SubmitBuildArtifact $useCase): JsonResponse
    {
        $data = $request->validated();
        $edition = GameEdition::from($data['game']);
        $type = $data['flow'] === 'analyse' ? 'pob' : ($data['flow'] === 'upgrade' && $request->filled('item_text') ? 'item_text' : 'wizard_plan');
        $artifact = $type === 'pob' ? $data['pob'] : ($type === 'item_text' ? $data['item_text'] : json_encode(['game' => $edition->value, 'flow' => $data['flow'], 'class' => $data['character_class'], 'ascendancy' => $data['ascendancy'] ?? null, 'alternate_ascendancy' => $data['alternate_ascendancy'] ?? null, 'secondary_progression' => $data['secondary_progression'] ?? null, 'level' => $data['character_level']], JSON_THROW_ON_ERROR));
        $goals = array_values(array_unique([...$data['goals'], $data['priority'], $data['play_style'], ...array_filter([$data['problem'] ?? null, $data['description'] ?? null])]));
        $budgetAmount = $data['budget_amount'] ?? null;
        $budgetCurrency = $budgetAmount === null ? null : ($data['budget_currency'] ?? null);
        $receipt = $useCase->handle(new SubmitBuildArtifactCommand((string) $request->user()->id, $data['idempotency_key'], $edition, Locale::from('tr-TR')->value(), $type, $artifact, new AnalysisParameters($goals, $budgetAmount, $budgetCurrency, new AnalysisSelection(PlatformRealm::Pc, $data['league'] ?? null, $data['goals'][0], null, null, null, (bool) $data['ai_explanation_opt_in'], $data['character_class'], $data['ascendancy'] ?? null, (int) $data['character_level'], $data['flow'], $data['alternate_ascendancy'] ?? null, $data['secondary_progression'] ?? null))));
        DB::table('analyses')->where('id', $receipt->analysisId)->whereNull('user_id')->update(['user_id' => $request->user()->id]);

        return response()->json(['analysis_id' => $receipt->analysisId, 'status' => $receipt->state->value, 'idempotent_replay' => $receipt->replayed], $receipt->replayed ? 200 : 202, ['Cache-Control' => 'no-store']);
    }
}

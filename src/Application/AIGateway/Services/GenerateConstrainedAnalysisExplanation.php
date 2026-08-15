<?php

namespace Lootwright\Application\AIGateway\Services;

use Lootwright\Application\AIGateway\DTO\AiGatewayOutcome;
use Lootwright\Application\AIGateway\DTO\ExplanationBundle;
use Lootwright\Application\AIGateway\DTO\GatewayExplanationRequest;
use Lootwright\Application\AIGateway\Ports\AiGateway;
use Lootwright\Application\AIGateway\Ports\AnalysisExplanationRepository;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\UseCases\RetrieveAnalysis;
use Lootwright\Domain\Shared\Identity\AnalysisId;

final readonly class GenerateConstrainedAnalysisExplanation
{
    public function __construct(
        private RetrieveAnalysis $analyses,
        private AiGateway $gateway,
        private AnalysisExplanationRepository $explanations,
    ) {}

    public function handle(string $ownerId, string $analysisId, GatewayExplanationRequest $request): AiGatewayOutcome
    {
        $analysis = $this->analyses->handle($ownerId, $analysisId);

        if ($analysis->state !== AnalysisState::Completed) {
            throw new InvalidWorkflowInput('Only completed deterministic analyses can be explained.');
        }

        $identity = AnalysisId::from($analysis->edition, $analysisId);
        if ($identity->isFailure() || ! $identity->value() instanceof AnalysisId) {
            throw new InvalidWorkflowInput('The analysis identity is invalid.');
        }

        foreach ([...$request->findings, ...$request->recommendations] as $product) {
            if (! $product->analysisId->equals($identity->value())) {
                throw new InvalidWorkflowInput('Explanation products must belong to the selected analysis.');
            }
        }

        $outcome = $this->gateway->explain($request);

        if (! $outcome->value instanceof ExplanationBundle
            || ! $this->explanations->storeForOwner($analysisId, $ownerId, $outcome->value, $outcome->status)
        ) {
            throw new InvalidWorkflowInput('The constrained explanation could not be persisted.');
        }

        return $outcome;
    }
}

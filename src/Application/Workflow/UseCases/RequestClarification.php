<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class RequestClarification
{
    public function __construct(private WorkflowRepository $repository) {}

    /** @param list<array{code: string, question: string}> $clarifications */
    public function handle(string $analysisId, array $clarifications): void
    {
        if ($clarifications === []) {
            throw new InvalidWorkflowInput('At least one clarification is required.');
        }

        foreach ($clarifications as $clarification) {
            if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $clarification['code']) !== 1
                || trim($clarification['question']) === ''
                || mb_strlen($clarification['question']) > 240
            ) {
                throw new InvalidWorkflowInput('Clarifications require canonical codes and bounded questions.');
            }
        }

        $this->repository->transitionAnalysis(
            $analysisId,
            AnalysisState::ClarificationRequired,
            CanonicalJson::encode(['clarifications' => $clarifications]),
        );
    }
}

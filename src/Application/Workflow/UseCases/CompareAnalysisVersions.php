<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisComparison;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;

final readonly class CompareAnalysisVersions
{
    public function __construct(private RetrieveAnalysis $retrieve) {}

    public function handle(string $ownerId, string $leftAnalysisId, string $rightAnalysisId): AnalysisComparison
    {
        $left = $this->retrieve->handle($ownerId, $leftAnalysisId);
        $right = $this->retrieve->handle($ownerId, $rightAnalysisId);

        if ($left->artifactId !== $right->artifactId || $left->edition !== $right->edition) {
            throw new InvalidWorkflowInput('Only versions of the same game-scoped artifact can be compared.');
        }

        return new AnalysisComparison(
            $left->id,
            $right->id,
            $left->inputHashSha256 !== $right->inputHashSha256,
            $left->outputHashSha256 !== $right->outputHashSha256,
            $left->rulesetId !== $right->rulesetId
                || $left->rulesetVersion !== $right->rulesetVersion
                || $left->rulesetChecksumSha256 !== $right->rulesetChecksumSha256,
            $left->outputHashSha256,
            $right->outputHashSha256,
        );
    }
}

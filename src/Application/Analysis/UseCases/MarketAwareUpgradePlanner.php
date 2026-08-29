<?php

namespace Lootwright\Application\Analysis\UseCases;

use Lootwright\Application\Market\MarketEvidenceResolver;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\BudgetEvaluator;
use Lootwright\Domain\Recommendations\MarketDataRequirement;
use Lootwright\Domain\Recommendations\MarketPriceEvidence;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\UpgradeGraph;
use Lootwright\Domain\Recommendations\UpgradeMarketValueScorer;
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Shared\Error\DomainResult;

/** Enriches deterministic candidates; it never creates findings or game facts. */
final readonly class MarketAwareUpgradePlanner implements UpgradePlanner
{
    public function __construct(private UpgradePlanner $deterministic, private MarketEvidenceResolver $resolver, private BudgetEvaluator $budgets = new BudgetEvaluator, private UpgradeMarketValueScorer $valueScorer = new UpgradeMarketValueScorer) {}

    public function plan(array|AnalysisResult $analysis, BuildIntent $intent, ?BudgetConstraint $budget = null, ?UserConstraints $constraints = null): DomainResult
    {
        $result = $this->deterministic->plan($analysis, $intent, $budget, $constraints);
        if ($result->isFailure() || ! $result->value() instanceof UpgradeGraph) {
            return $result;
        }
        $graph = $result->value();
        $candidates = [];
        foreach ($graph->candidates as $candidate) {
            $evidence = $candidate->marketDataRequirement === MarketDataRequirement::Required
                ? $this->resolver->resolve($candidate)
                : null;
            if ($evidence === null) {
                $candidates[] = $candidate;

                continue;
            }
            if ($evidence->edition !== null && $evidence->edition !== $candidate->gameEdition) {
                $candidates[] = $candidate;

                continue;
            }
            $value = $this->valueScorer->score(
                min(10_000, $candidate->score),
                $evidence->price,
                $evidence->liquidityBasisPoints,
                min(10_000, count($candidate->dependentSlots) * 1_500),
                $evidence->confidenceBasisPoints,
            );
            $enriched = $candidate->withPriceEvidence($evidence, $value);
            $budgetEvaluation = $this->budgets->evaluate($enriched, $budget ?? BudgetConstraint::unknown());
            $candidates[] = $enriched->evaluated($enriched->score, $budgetEvaluation->uncertainty, ! $budgetEvaluation->allowed, $budgetEvaluation->reason);
        }
        usort($candidates, static fn ($left, $right): int => [$right->score, $left->id] <=> [$left->score, $right->id]);

        return DomainResult::success(new UpgradeGraph($graph->gameEdition, $graph->ruleset, $candidates, $graph->impossibleCandidates));
    }

}

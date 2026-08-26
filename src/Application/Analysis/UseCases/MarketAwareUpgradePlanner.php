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
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Shared\Error\DomainResult;

/** Enriches deterministic candidates; it never creates findings or game facts. */
final readonly class MarketAwareUpgradePlanner implements UpgradePlanner
{
    public function __construct(private UpgradePlanner $deterministic, private MarketEvidenceResolver $resolver, private BudgetEvaluator $budgets = new BudgetEvaluator) {}

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
            $enriched = $candidate->withPriceEvidence($evidence, $this->marketScore($candidate->score, $evidence));
            $budgetEvaluation = $this->budgets->evaluate($enriched, $budget ?? BudgetConstraint::unknown());
            $candidates[] = $enriched->evaluated($enriched->score, $budgetEvaluation->uncertainty, ! $budgetEvaluation->allowed, $budgetEvaluation->reason);
        }
        usort($candidates, static fn ($left, $right): int => [$right->score, $left->id] <=> [$left->score, $right->id]);

        return DomainResult::success(new UpgradeGraph($graph->gameEdition, $graph->ruleset, $candidates, $graph->impossibleCandidates));
    }

    private function marketScore(int $deterministicScore, MarketPriceEvidence $evidence): int
    {
        if ($evidence->freshness->value !== 'fresh') {
            return $deterministicScore;
        }

        $price = $this->decimalHundredths($evidence->price->amount);
        $priceValue = max(0, 2_000 - min(2_000, intdiv($price, 10)));
        $quality = intdiv($evidence->confidenceBasisPoints + $evidence->liquidityBasisPoints, 20);

        return min(100_000, $deterministicScore + $priceValue + $quality);
    }

    private function decimalHundredths(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return min(200_000, ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0'));
    }
}

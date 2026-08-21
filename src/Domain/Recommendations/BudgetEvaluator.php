<?php

namespace Lootwright\Domain\Recommendations;

final readonly class BudgetEvaluator
{
    public function evaluate(UpgradeCandidate $candidate, BudgetConstraint $constraint): BudgetEvaluation
    {
        if ($candidate->marketDataRequirement === MarketDataRequirement::NotRequired) {
            return new BudgetEvaluation(true, BudgetUncertainty::NotApplicable);
        }
        if (! $constraint->isKnown()) {
            return new BudgetEvaluation(true, BudgetUncertainty::BudgetUnknown, 'No currency budget was supplied.');
        }
        if ($candidate->priceEvidence === null
            || $candidate->priceEvidence->freshness !== MarketEvidenceFreshness::Fresh
            || ! $candidate->priceEvidence->price->currency->equals($constraint->budget->currency)
        ) {
            return new BudgetEvaluation(true, BudgetUncertainty::MarketPriceUnknown, 'No approved comparable market price is available.');
        }
        if ($this->compareDecimal($candidate->priceEvidence->price->amount, $constraint->budget->amount) > 0) {
            return new BudgetEvaluation(false, BudgetUncertainty::VerifiedOverBudget, 'Approved market evidence exceeds the supplied budget.');
        }

        return new BudgetEvaluation(true, BudgetUncertainty::VerifiedWithinBudget);
    }

    private function compareDecimal(string $left, string $right): int
    {
        [$leftWhole, $leftFraction] = array_pad(explode('.', $left, 2), 2, '');
        [$rightWhole, $rightFraction] = array_pad(explode('.', $right, 2), 2, '');
        $leftWhole = ltrim($leftWhole, '0') ?: '0';
        $rightWhole = ltrim($rightWhole, '0') ?: '0';
        if (strlen($leftWhole) !== strlen($rightWhole)) {
            return strlen($leftWhole) <=> strlen($rightWhole);
        }
        $whole = strcmp($leftWhole, $rightWhole);
        if ($whole !== 0) {
            return $whole <=> 0;
        }
        $width = max(strlen($leftFraction), strlen($rightFraction));

        return strcmp(str_pad($leftFraction, $width, '0'), str_pad($rightFraction, $width, '0')) <=> 0;
    }
}

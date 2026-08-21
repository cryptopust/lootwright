<?php

namespace Lootwright\Domain\Recommendations;

enum BudgetUncertainty: string
{
    case NotApplicable = 'not_applicable';
    case BudgetUnknown = 'budget_unknown';
    case MarketPriceUnknown = 'market_price_unknown';
    case VerifiedWithinBudget = 'verified_within_budget';
    case VerifiedOverBudget = 'verified_over_budget';
}

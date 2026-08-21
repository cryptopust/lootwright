<?php

namespace Lootwright\Domain\Recommendations;

use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\RulesetReference;

final readonly class DeterministicUpgradePlanner implements UpgradePlanner
{
    /** @param list<UpgradeCandidateFactory> $factories */
    public function __construct(
        private array $factories,
        private ConstraintEvaluator $constraints = new ConstraintEvaluator,
        private BudgetEvaluator $budgets = new BudgetEvaluator,
        private DependencyResolver $dependencies = new DependencyResolver,
    ) {}

    public function plan(array|AnalysisResult $analysis, BuildIntent $intent, ?BudgetConstraint $budget = null, ?UserConstraints $constraints = null): DomainResult
    {
        if (is_array($analysis)) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::UnsupportedInput, 'Canonical upgrade graph planning requires AnalysisResult.'));
        }
        $budget ??= BudgetConstraint::unknown();
        $constraints ??= new UserConstraints;
        if ($intent->goal->edition !== $analysis->gameEdition) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::EditionMismatch, 'Upgrade planning inputs must share one game edition.'));
        }
        $factory = null;
        foreach ($this->factories as $candidateFactory) {
            if ($candidateFactory->edition() === $analysis->gameEdition) {
                $factory = $candidateFactory;
                break;
            }
        }
        if ($factory === null) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::UnsupportedInput, 'No independently validated upgrade factory exists for this game edition.'));
        }

        $allowed = [];
        $impossible = [];
        foreach ($factory->create($analysis, $intent) as $candidate) {
            $constraint = $this->constraints->evaluate($candidate, $constraints);
            $budgetEvaluation = $this->budgets->evaluate($candidate, $budget);
            $evaluated = $candidate->evaluated(
                max(0, min(100_000, $candidate->score + $constraint->scoreAdjustment)),
                $budgetEvaluation->uncertainty,
                ! $constraint->allowed || ! $budgetEvaluation->allowed,
                $constraint->reason ?? $budgetEvaluation->reason,
            );
            if ($evaluated->impossible) {
                $impossible[] = $evaluated;
            } else {
                $allowed[] = $evaluated;
            }
        }

        $resolved = $this->dependencies->resolve($allowed);
        if ($resolved->isFailure()) {
            return $resolved;
        }

        $ruleset = new RulesetReference(
            $analysis->gameEdition,
            $analysis->ruleset->id->value,
            $analysis->ruleset->version->value,
            $analysis->ruleset->checksumSha256,
        );

        return DomainResult::success(new UpgradeGraph($analysis->gameEdition, $ruleset, $resolved->value(), $impossible));
    }
}

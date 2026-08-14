<?php

namespace Lootwright\Application\TradePlanning\DTO;

use InvalidArgumentException;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Evidence\RuleReference;

final readonly class ApprovedTradeVocabulary
{
    /**
     * @param  list<TradeFilterDefinition>  $filters
     * @param  list<ItemTargetDefinition>  $itemTargets
     * @param  list<ItemConstraintDefinition>  $constraints
     */
    public function __construct(
        public RulesetIdentity $ruleset,
        public array $filters,
        public array $itemTargets,
        public array $constraints,
    ) {
        $this->guardUniqueKeys();
    }

    public function filter(string $modifierId): ?TradeFilterDefinition
    {
        foreach ($this->filters as $filter) {
            if ($filter->modifierId->value === $modifierId) {
                return $filter;
            }
        }

        return null;
    }

    public function itemTarget(string $code): ?ItemTargetDefinition
    {
        foreach ($this->itemTargets as $target) {
            if ($target->code === $code) {
                return $target;
            }
        }

        return null;
    }

    public function constraint(string $code): ?ItemConstraintDefinition
    {
        foreach ($this->constraints as $constraint) {
            if ($constraint->code === $code) {
                return $constraint;
            }
        }

        return null;
    }

    private function guardUniqueKeys(): void
    {
        $keys = [];

        foreach ($this->filters as $filter) {
            $key = 'filter:'.$filter->modifierId->value;
            $this->guardKey($keys, $key);
            $this->guardRule($filter->rule);

            if (! $filter->modifierId->belongsTo($this->ruleset->edition)) {
                throw new InvalidArgumentException('Vocabulary modifier IDs must belong to the ruleset edition.');
            }

            foreach ($filter->conflictingModifierIds as $conflictId) {
                if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $conflictId) !== 1) {
                    throw new InvalidArgumentException('Conflicting modifier IDs must be canonical vocabulary keys.');
                }
            }
        }

        foreach ($this->itemTargets as $target) {
            $key = 'target:'.$target->code;
            $this->guardCanonicalCode($target->code);
            $this->guardKey($keys, $key);
            $this->guardRule($target->rule);
        }

        foreach ($this->constraints as $constraint) {
            $key = 'constraint:'.$constraint->code;
            $this->guardCanonicalCode($constraint->code);
            $this->guardKey($keys, $key);
            $this->guardRule($constraint->rule);
        }
    }

    /** @param array<string, true> $keys */
    private function guardKey(array &$keys, string $key): void
    {
        if (isset($keys[$key])) {
            throw new InvalidArgumentException('Approved Trade vocabulary keys must be unique.');
        }

        $keys[$key] = true;
    }

    private function guardRule(RuleReference $rule): void
    {
        if ($rule->edition !== $this->ruleset->edition
            || ! $rule->rulesetId->equals($this->ruleset->id)
            || ! $rule->rulesetVersion->equals($this->ruleset->version)
        ) {
            throw new InvalidArgumentException('Every vocabulary entry must cite the exact approved ruleset.');
        }
    }

    private function guardCanonicalCode(string $code): void
    {
        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $code) !== 1) {
            throw new InvalidArgumentException('Trade vocabulary codes must be canonical.');
        }
    }
}

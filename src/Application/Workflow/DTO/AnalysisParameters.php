<?php

namespace Lootwright\Application\Workflow\DTO;

use InvalidArgumentException;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class AnalysisParameters
{
    /** @param list<string> $goals
     * @param  list<string>  $lockedItems
     */
    public function __construct(
        public array $goals,
        public ?string $budgetAmount,
        public ?string $budgetCurrency,
        public ?AnalysisSelection $selection = null,
        public array $lockedItems = [],
    ) {}

    public function canonicalJson(): string
    {
        if (! $this->isValid()) {
            throw new InvalidArgumentException('Analysis parameters must use bounded goals and a complete canonical budget.');
        }

        return CanonicalJson::encode([
            'budget' => $this->budgetAmount === null ? null : [
                'amount' => $this->budgetAmount,
                'currency' => $this->budgetCurrency,
            ],
            'goals' => $this->goals,
            'locked_items' => $this->lockedItems,
            'selection' => $this->selection,
        ]);
    }

    private function isValid(): bool
    {
        if (count($this->goals) > 10
            || ($this->budgetAmount === null) !== ($this->budgetCurrency === null)
            || ($this->budgetAmount !== null
                && preg_match('/^(0|[1-9]\d{0,14})(?:\.\d{1,4})?$/D', $this->budgetAmount) !== 1)
            || ($this->budgetCurrency !== null
                && preg_match('/^[A-Z][A-Z0-9_]{2,11}$/D', $this->budgetCurrency) !== 1)
        ) {
            return false;
        }

        foreach ($this->goals as $goal) {
            if (trim($goal) === ''
                || mb_strlen($goal) > 500
                || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $goal) === 1
            ) {
                return false;
            }
        }

        foreach ($this->lockedItems as $item) {
            if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $item) !== 1) {
                return false;
            }
        }

        return true;
    }
}

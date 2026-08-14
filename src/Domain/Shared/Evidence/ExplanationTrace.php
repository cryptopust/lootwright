<?php

namespace Lootwright\Domain\Shared\Evidence;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class ExplanationTrace implements JsonSerializable
{
    /** @param  list<TraceStep>  $steps */
    private function __construct(
        public GameEdition $edition,
        public array $steps,
    ) {}

    /**
     * @param  array<array-key, mixed>  $steps
     */
    public static function create(GameEdition $edition, array $steps): DomainResult
    {
        if ($steps === []) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EmptyCollection,
                'An explanation trace requires at least one step.',
            ));
        }

        $validatedSteps = [];

        foreach ($steps as $step) {
            if (! $step instanceof TraceStep
                || ($step->rule !== null && $step->rule->edition !== $edition)
            ) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every explanation step and rule must belong to the trace edition.',
                ));
            }

            $validatedSteps[] = $step;
        }

        return DomainResult::success(new self($edition, $validatedSteps));
    }

    /** @return array{edition: string, steps: list<TraceStep>} */
    public function jsonSerialize(): array
    {
        return ['edition' => $this->edition->value, 'steps' => $this->steps];
    }
}

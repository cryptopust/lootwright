<?php

namespace Lootwright\Domain\BuildIntake\Intent;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class BuildIntent implements JsonSerializable
{
    private const CLARIFICATION_THRESHOLD = 5_000;

    /**
     * @param  array<array-key, mixed>  $clarifications
     */
    private function __construct(
        public PlayerGoal $goal,
        public Locale $locale,
        public Confidence $confidence,
        public array $clarifications,
    ) {}

    /**
     * @param  array<array-key, mixed>  $clarifications
     */
    public static function create(
        PlayerGoal $goal,
        Locale $locale,
        Confidence $confidence,
        array $clarifications,
    ): DomainResult {
        $validatedClarifications = [];

        foreach ($clarifications as $clarification) {
            if (! $clarification instanceof ClarificationRequirement) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Every clarification must be a ClarificationRequirement.',
                ));
            }

            $validatedClarifications[] = $clarification;
        }

        if ($confidence->isBelow(self::CLARIFICATION_THRESHOLD) && $validatedClarifications === []) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::ClarificationRequired,
                'Low-confidence intent must include at least one clarification requirement.',
            ));
        }

        return DomainResult::success(new self(
            $goal,
            $locale,
            $confidence,
            $validatedClarifications,
        ));
    }

    public function requiresClarification(): bool
    {
        return $this->clarifications !== [];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'goal' => $this->goal,
            'locale' => $this->locale,
            'confidence_basis_points' => $this->confidence->basisPoints,
            'clarifications' => $this->clarifications,
        ];
    }
}

<?php

namespace Lootwright\Domain\BuildIntake\Intent;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
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

    /** Explicit unknown intent for workflows that predate structured intent. */
    public static function unspecified(GameEdition $edition, Locale $locale): self
    {
        $content = ContentGoal::from($edition, 'intent.unspecified')->value();
        $style = PlayStyle::from($edition, 'intent.unspecified')->value();
        $goal = PlayerGoal::create(
            $edition,
            'No structured build intent was supplied.',
            $content,
            $style,
        )->value();
        $clarification = ClarificationRequirement::create(
            'intent.required',
            'Which content goal and play style should the analysis evaluate?',
        )->value();

        return self::create(
            $goal,
            $locale,
            Confidence::fromBasisPoints(0)->value(),
            [$clarification],
        )->value();
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

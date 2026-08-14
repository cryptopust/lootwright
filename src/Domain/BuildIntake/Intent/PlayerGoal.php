<?php

namespace Lootwright\Domain\BuildIntake\Intent;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class PlayerGoal implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $constraints
     */
    private function __construct(
        public GameEdition $edition,
        public string $description,
        public ContentGoal $contentGoal,
        public PlayStyle $playStyle,
        public array $constraints,
    ) {}

    /**
     * @param  array<array-key, mixed>  $constraints
     */
    public static function create(
        GameEdition $edition,
        string $description,
        ContentGoal $contentGoal,
        PlayStyle $playStyle,
        array $constraints = [],
    ): DomainResult {
        $description = trim($description);

        if ($description === ''
            || mb_strlen($description) > 500
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $description) === 1
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A player goal requires bounded plain text.',
            ));
        }

        if (! $contentGoal->belongsTo($edition) || ! $playStyle->belongsTo($edition)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'Player goal concepts must belong to the selected edition.',
            ));
        }

        $validatedConstraints = [];

        foreach ($constraints as $constraint) {
            if (! $constraint instanceof Constraint) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Every player-goal constraint must be a Constraint value object.',
                ));
            }

            $validatedConstraints[] = $constraint;
        }

        return DomainResult::success(new self(
            $edition,
            $description,
            $contentGoal,
            $playStyle,
            $validatedConstraints,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'description' => $this->description,
            'content_goal' => $this->contentGoal,
            'play_style' => $this->playStyle,
            'constraints' => $this->constraints,
        ];
    }
}

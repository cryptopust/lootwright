<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class BuildIntentCandidate implements JsonSerializable
{
    /** @param list<array{code: string, value: string, priority: string}> $constraints */
    public function __construct(
        public GameEdition $edition,
        public string $contentGoal,
        public string $playStyle,
        public array $constraints,
        public int $confidenceBasisPoints,
    ) {
        if ($confidenceBasisPoints < 0 || $confidenceBasisPoints > 10_000) {
            throw new InvalidArgumentException('AI intent confidence must be expressed in basis points.');
        }

        foreach ($constraints as $constraint) {
            if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $constraint['code']) !== 1
                || trim($constraint['value']) === ''
                || mb_strlen($constraint['value']) > 128
                || ! in_array($constraint['priority'], ['critical', 'high', 'medium', 'low'], true)
            ) {
                throw new InvalidArgumentException('AI intent constraints must use the closed priority vocabulary.');
            }
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'content_goal' => $this->contentGoal,
            'play_style' => $this->playStyle,
            'constraints' => $this->constraints,
            'confidence_basis_points' => $this->confidenceBasisPoints,
        ];
    }
}

<?php

namespace Lootwright\Domain\BuildIntake\Intent;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class ClarificationRequirement implements JsonSerializable
{
    private function __construct(
        public string $code,
        public string $question,
    ) {}

    public static function create(string $code, string $question): DomainResult
    {
        $code = trim($code);
        $question = trim($question);

        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $code) !== 1
            || $question === ''
            || mb_strlen($question) > 240
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A clarification requires a canonical code and bounded question.',
            ));
        }

        return DomainResult::success(new self($code, $question));
    }

    /** @return array{code: string, question: string} */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'question' => $this->question];
    }
}

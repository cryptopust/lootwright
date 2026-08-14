<?php

namespace Lootwright\Domain\Recommendations;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class AlternativePath implements JsonSerializable
{
    /** @param  list<string>  $steps */
    private function __construct(
        public string $code,
        public string $title,
        public array $steps,
    ) {}

    /**
     * @param  array<array-key, mixed>  $steps
     */
    public static function create(string $code, string $title, array $steps): DomainResult
    {
        $code = trim($code);
        $title = trim($title);

        if (preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $code) !== 1
            || $title === ''
            || mb_strlen($title) > 160
            || $steps === []
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'An alternative path requires a canonical code, title, and steps.',
            ));
        }

        $validatedSteps = [];

        foreach ($steps as $step) {
            if (! is_string($step) || trim($step) === '' || mb_strlen($step) > 300) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Alternative path steps must be bounded plain text.',
                ));
            }

            $validatedSteps[] = trim($step);
        }

        return DomainResult::success(new self($code, $title, $validatedSteps));
    }

    /** @return array{code: string, title: string, steps: list<string>} */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'title' => $this->title, 'steps' => $this->steps];
    }
}

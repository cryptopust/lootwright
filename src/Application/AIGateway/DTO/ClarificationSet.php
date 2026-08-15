<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use JsonSerializable;

final readonly class ClarificationSet implements JsonSerializable
{
    /** @param list<array{code: string, question: string}> $questions */
    public function __construct(public string $language, public array $questions)
    {
        if (! in_array($language, ['en', 'tr'], true) || $questions === [] || count($questions) > 3) {
            throw new InvalidArgumentException('Clarifications require a supported language and one to three questions.');
        }
    }

    /** @return array{language: string, questions: list<array{code: string, question: string}>} */
    public function jsonSerialize(): array
    {
        return ['language' => $this->language, 'questions' => $this->questions];
    }
}

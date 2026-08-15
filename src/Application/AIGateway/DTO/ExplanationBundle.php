<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use JsonSerializable;

final readonly class ExplanationBundle implements JsonSerializable
{
    /**
     * @param  list<array{code: string, text: string}>  $findings
     * @param  list<array{code: string, text: string}>  $recommendations
     */
    public function __construct(
        public string $language,
        public string $summary,
        public array $findings,
        public array $recommendations,
    ) {
        if (! in_array($language, ['en', 'tr'], true) || trim($summary) === '') {
            throw new InvalidArgumentException('Explanation bundles require a supported language and summary.');
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'language' => $this->language,
            'summary' => $this->summary,
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
        ];
    }
}

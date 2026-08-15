<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;
use Lootwright\Domain\Shared\Value\Locale;

final readonly class NaturalLanguageIntentRequest
{
    public string $description;

    public function __construct(
        string $description,
        public Locale $locale,
        public IntentVocabulary $vocabulary,
        public AiRequestContext $context,
    ) {
        $description = trim($description);

        if ($description === '' || mb_strlen($description) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $description) === 1) {
            throw new InvalidArgumentException('Natural-language intent must be bounded plain text.');
        }

        $this->description = $description;
    }
}

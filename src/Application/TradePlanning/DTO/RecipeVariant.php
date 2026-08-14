<?php

namespace Lootwright\Application\TradePlanning\DTO;

use JsonSerializable;

final readonly class RecipeVariant implements JsonSerializable
{
    /**
     * @param  list<RecipeFilter>  $required
     * @param  list<RecipeFilter>  $weighted
     * @param  list<RecipeFilter>  $excluded
     */
    public function __construct(
        public string $name,
        public array $required,
        public array $weighted,
        public array $excluded,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'required' => $this->required,
            'weighted_optional' => $this->weighted,
            'excluded' => $this->excluded,
        ];
    }
}

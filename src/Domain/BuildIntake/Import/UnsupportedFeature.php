<?php

namespace Lootwright\Domain\BuildIntake\Import;

use JsonSerializable;

final readonly class UnsupportedFeature implements JsonSerializable
{
    /** @param array<string, string> $attributes */
    public function __construct(
        public string $path,
        public string $element,
        public array $attributes,
    ) {}

    /** @return array{path: string, element: string, attributes: array<string, string>} */
    public function jsonSerialize(): array
    {
        return ['path' => $this->path, 'element' => $this->element, 'attributes' => $this->attributes];
    }
}

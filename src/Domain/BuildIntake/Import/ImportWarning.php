<?php

namespace Lootwright\Domain\BuildIntake\Import;

use JsonSerializable;

final readonly class ImportWarning implements JsonSerializable
{
    public function __construct(
        public string $code,
        public string $message,
        public ?string $path = null,
    ) {}

    /** @return array{code: string, message: string, path: ?string} */
    public function jsonSerialize(): array
    {
        return ['code' => $this->code, 'message' => $this->message, 'path' => $this->path];
    }
}

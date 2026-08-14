<?php

namespace Lootwright\Domain\Shared\Error;

use JsonSerializable;

final readonly class DomainError implements JsonSerializable
{
    /**
     * @param  array<string, bool|int|string>  $context
     */
    private function __construct(
        public DomainErrorCode $code,
        public string $message,
        public array $context = [],
    ) {}

    /**
     * @param  array<string, bool|int|string>  $context
     */
    public static function because(
        DomainErrorCode $code,
        string $message,
        array $context = [],
    ): self {
        return new self($code, $message, $context);
    }

    /** @return array{code: string, message: string, context: array<string, bool|int|string>} */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code->value,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}

<?php

namespace Lootwright\Domain\Shared\Error;

use LogicException;

final readonly class DomainResult
{
    private function __construct(
        private mixed $value,
        private ?DomainError $error,
    ) {}

    public static function success(mixed $value): self
    {
        return new self($value, null);
    }

    public static function failure(DomainError $error): self
    {
        return new self(null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    public function isFailure(): bool
    {
        return $this->error !== null;
    }

    public function value(): mixed
    {
        if ($this->error !== null) {
            throw new LogicException('A failed domain result has no value.');
        }

        return $this->value;
    }

    public function error(): DomainError
    {
        if ($this->error === null) {
            throw new LogicException('A successful domain result has no error.');
        }

        return $this->error;
    }
}

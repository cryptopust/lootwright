<?php

namespace Lootwright\Domain\Shared\Identity;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

abstract readonly class EditionScopedValue implements JsonSerializable
{
    final protected function __construct(
        public GameEdition $edition,
        public string $value,
    ) {}

    final public static function from(GameEdition $edition, string $value): DomainResult
    {
        $value = trim($value);

        if (preg_match(static::pattern(), $value) !== 1) {
            return DomainResult::failure(DomainError::because(
                static::errorCode(),
                static::invalidMessage(),
                ['type' => static::class],
            ));
        }

        return DomainResult::success(new static($edition, $value));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    final public static function fromArray(array $payload): DomainResult
    {
        $edition = $payload['edition'] ?? null;
        $value = $payload['value'] ?? null;

        if (! is_string($edition) || ! is_string($value)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::UnsupportedSerialization,
                'An edition-scoped value requires string edition and value fields.',
                ['type' => static::class],
            ));
        }

        $gameEdition = GameEdition::tryFrom($edition);

        if ($gameEdition === null) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::UnsupportedSerialization,
                'The serialized game edition is not supported.',
                ['type' => static::class],
            ));
        }

        return static::from($gameEdition, $value);
    }

    final public function equals(self $other): bool
    {
        return $this::class === $other::class
            && $this->edition === $other->edition
            && $this->value === $other->value;
    }

    final public function belongsTo(GameEdition $edition): bool
    {
        return $this->edition === $edition;
    }

    /** @return array{edition: string, value: string} */
    final public function jsonSerialize(): array
    {
        return ['edition' => $this->edition->value, 'value' => $this->value];
    }

    abstract protected static function pattern(): string;

    protected static function errorCode(): DomainErrorCode
    {
        return DomainErrorCode::InvalidIdentifier;
    }

    protected static function invalidMessage(): string
    {
        return 'The identifier is not in canonical form.';
    }
}

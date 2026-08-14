<?php

namespace Lootwright\Domain\Shared\Game;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class GameScope implements JsonSerializable
{
    private function __construct(
        public GameEdition $edition,
        public PlatformRealm $realm,
    ) {}

    public static function create(GameEdition $edition, PlatformRealm $realm): DomainResult
    {
        if (! $realm->supports($edition)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::RealmMismatch,
                'The platform realm is not valid for the selected game edition.',
                ['edition' => $edition->value, 'realm' => $realm->value],
            ));
        }

        return DomainResult::success(new self($edition, $realm));
    }

    public function equals(self $other): bool
    {
        return $this->edition === $other->edition && $this->realm === $other->realm;
    }

    /** @return array{edition: string, realm: string} */
    public function jsonSerialize(): array
    {
        return ['edition' => $this->edition->value, 'realm' => $this->realm->value];
    }
}

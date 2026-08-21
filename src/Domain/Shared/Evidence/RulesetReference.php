<?php

namespace Lootwright\Domain\Shared\Evidence;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Minimal immutable ruleset identity safe for downstream domain modules. */
final readonly class RulesetReference implements JsonSerializable
{
    public function __construct(
        public GameEdition $edition,
        public string $id,
        public string $version,
        public string $checksumSha256,
    ) {
        if (preg_match('/^[a-z0-9][a-z0-9._:-]{1,127}$/D', $id) !== 1
            || preg_match('/^[0-9A-Za-z][0-9A-Za-z._-]{0,63}$/D', $version) !== 1
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
        ) {
            throw new InvalidArgumentException('A ruleset reference requires bounded canonical identity values.');
        }
    }

    /** @return array{edition:string,id:string,version:string,checksum_sha256:string} */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'id' => $this->id,
            'version' => $this->version,
            'checksum_sha256' => $this->checksumSha256,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->edition === $other->edition
            && $this->id === $other->id
            && $this->version === $other->version
            && $this->checksumSha256 === $other->checksumSha256;
    }
}

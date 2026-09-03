<?php

namespace Lootwright\Domain\Recommendations;

use InvalidArgumentException;
use JsonSerializable;

final readonly class UserConstraints implements JsonSerializable
{
    /** @param list<UserConstraint> $values */
    public function __construct(public array $values = [])
    {
        $keys = [];
        foreach ($values as $constraint) {
            if (isset($keys[$constraint->key])) {
                throw new InvalidArgumentException('User constraints must be typed and unique by key.');
            }
            $keys[$constraint->key] = true;
        }
    }

    /** @return list<UserConstraint> */
    public function jsonSerialize(): array
    {
        return $this->values;
    }
}

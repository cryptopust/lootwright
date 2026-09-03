<?php

namespace Lootwright\Domain\Recommendations;

use InvalidArgumentException;
use JsonSerializable;

final readonly class UserConstraint implements JsonSerializable
{
    public function __construct(
        public string $key,
        public string $value,
        public ConstraintStrength $strength,
    ) {
        if (preg_match('/^[a-z][a-z0-9._:-]{1,127}$/D', $key) !== 1 || trim($value) === '' || mb_strlen($value) > 160) {
            throw new InvalidArgumentException('A user constraint requires a canonical key and bounded value.');
        }
    }

    public static function keepItem(string $canonicalItem): self
    {
        return new self('preserve:item:'.self::slug($canonicalItem), $canonicalItem, ConstraintStrength::Hard);
    }

    public static function preserveMainSkill(): self
    {
        return new self('preserve:main_skill', 'main skill', ConstraintStrength::Hard);
    }

    public static function avoidPassiveTreeRebuild(): self
    {
        return new self('avoid:passive_tree_rebuild', 'full passive-tree rebuild', ConstraintStrength::Hard);
    }

    public static function prefer(string $dimension): self
    {
        return new self('prefer:'.self::slug($dimension), $dimension, ConstraintStrength::Preference);
    }

    private static function slug(string $value): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '_', $value)));
        if ($slug === '') {
            throw new InvalidArgumentException('A constraint value cannot normalize to an empty identifier.');
        }

        return $slug;
    }

    /** @return array{key:string,value:string,strength:string} */
    public function jsonSerialize(): array
    {
        return ['key' => $this->key, 'value' => $this->value, 'strength' => $this->strength->value];
    }
}

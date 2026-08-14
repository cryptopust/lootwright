<?php

namespace Lootwright\Domain\Shared\Serialization;

use JsonSerializable;

final class CanonicalJson
{
    private function __construct() {}

    /**
     * @param  JsonSerializable|array<array-key, mixed>  $value
     */
    public static function encode(JsonSerializable|array $value): string
    {
        $normalized = self::normalize(
            $value instanceof JsonSerializable ? $value->jsonSerialize() : $value,
        );

        return json_encode(
            $normalized,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }

    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof JsonSerializable) {
            return self::normalize($value->jsonSerialize());
        }

        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(self::normalize(...), $value);
    }
}

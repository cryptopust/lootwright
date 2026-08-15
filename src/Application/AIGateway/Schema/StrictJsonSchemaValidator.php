<?php

namespace Lootwright\Application\AIGateway\Schema;

final class StrictJsonSchemaValidator
{
    /** @param array<string, mixed> $schema
     * @return list<string>
     */
    public function validate(mixed $value, array $schema, string $path = '$'): array
    {
        $type = $schema['type'] ?? null;

        if ($type === 'object') {
            if (! is_array($value) || array_is_list($value)) {
                return ["{$path} must be an object."];
            }

            $properties = $schema['properties'] ?? [];
            $errors = [];

            foreach ($schema['required'] ?? [] as $required) {
                if (! array_key_exists($required, $value)) {
                    $errors[] = "{$path}.{$required} is required.";
                }
            }

            if (($schema['additionalProperties'] ?? true) === false) {
                foreach (array_diff(array_keys($value), array_keys($properties)) as $extra) {
                    $errors[] = "{$path}.{$extra} is not allowed.";
                }
            }

            foreach ($properties as $key => $propertySchema) {
                if (array_key_exists($key, $value) && is_array($propertySchema)) {
                    $errors = [...$errors, ...$this->validate($value[$key], $propertySchema, "{$path}.{$key}")];
                }
            }

            return $errors;
        }

        if ($type === 'array') {
            if (! is_array($value) || ! array_is_list($value)) {
                return ["{$path} must be an array."];
            }

            $count = count($value);
            $errors = [];

            if (isset($schema['minItems']) && $count < $schema['minItems']) {
                $errors[] = "{$path} has too few items.";
            }
            if (isset($schema['maxItems']) && $count > $schema['maxItems']) {
                $errors[] = "{$path} has too many items.";
            }

            foreach ($value as $index => $item) {
                $errors = [...$errors, ...$this->validate($item, $schema['items'], "{$path}[{$index}]")];
            }

            return $errors;
        }

        if ($type === 'string') {
            if (! is_string($value)) {
                return ["{$path} must be a string."];
            }

            $length = mb_strlen($value);
            if (isset($schema['minLength']) && $length < $schema['minLength']) {
                return ["{$path} is too short."];
            }
            if (isset($schema['maxLength']) && $length > $schema['maxLength']) {
                return ["{$path} is too long."];
            }
        } elseif ($type === 'integer' && ! is_int($value)) {
            return ["{$path} must be an integer."];
        }

        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            return ["{$path} is outside the approved vocabulary."];
        }
        if (is_int($value) && isset($schema['minimum']) && $value < $schema['minimum']) {
            return ["{$path} is below its minimum."];
        }
        if (is_int($value) && isset($schema['maximum']) && $value > $schema['maximum']) {
            return ["{$path} exceeds its maximum."];
        }

        return [];
    }
}

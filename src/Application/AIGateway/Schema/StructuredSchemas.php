<?php

namespace Lootwright\Application\AIGateway\Schema;

use Lootwright\Application\AIGateway\DTO\IntentVocabulary;

final class StructuredSchemas
{
    private function __construct() {}

    /** @return array<string, mixed> */
    public static function buildIntent(IntentVocabulary $vocabulary): array
    {
        return self::object([
            'edition' => ['type' => 'string', 'enum' => [$vocabulary->edition->value]],
            'content_goal' => ['type' => 'string', 'enum' => $vocabulary->contentGoals],
            'play_style' => ['type' => 'string', 'enum' => $vocabulary->playStyles],
            'constraints' => [
                'type' => 'array',
                'maxItems' => 12,
                'items' => self::object([
                    'code' => ['type' => 'string', 'enum' => $vocabulary->constraintCodes],
                    'value' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 128],
                    'priority' => ['type' => 'string', 'enum' => ['critical', 'high', 'medium', 'low']],
                ]),
            ],
            'confidence_basis_points' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10_000],
        ]);
    }

    /** @param list<string> $allowedCodes
     * @return array<string, mixed>
     */
    public static function clarifications(string $language, array $allowedCodes): array
    {
        return self::object([
            'language' => ['type' => 'string', 'enum' => [$language]],
            'questions' => [
                'type' => 'array',
                'minItems' => 1,
                'maxItems' => 3,
                'items' => self::object([
                    'code' => ['type' => 'string', 'enum' => $allowedCodes],
                    'question' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 240],
                ]),
            ],
        ]);
    }

    /** @param list<string> $findingCodes
     * @param  list<string>  $recommendationCodes
     * @return array<string, mixed>
     */
    public static function explanation(string $language, array $findingCodes, array $recommendationCodes): array
    {
        return self::object([
            'language' => ['type' => 'string', 'enum' => [$language]],
            'summary' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 600],
            'findings' => [
                'type' => 'array',
                'minItems' => count($findingCodes),
                'maxItems' => count($findingCodes),
                'items' => self::object([
                    'code' => ['type' => 'string', 'enum' => $findingCodes],
                    'text' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 500],
                ]),
            ],
            'recommendations' => [
                'type' => 'array',
                'minItems' => count($recommendationCodes),
                'maxItems' => count($recommendationCodes),
                'items' => self::object([
                    'code' => ['type' => 'string', 'enum' => $recommendationCodes],
                    'text' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 500],
                ]),
            ],
        ]);
    }

    /** @param array<string, array<string, mixed>> $properties
     * @return array<string, mixed>
     */
    private static function object(array $properties): array
    {
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }
}

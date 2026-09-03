<?php

use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;

$defaultPrecedence = ['official_structured', 'approved_upstream', 'trusted_community', 'derived', 'heuristic'];

return [
    'schema_versions' => [
        'poe1' => 'lootwright.poe1.game-data.v1',
        'poe2' => 'lootwright.poe2.game-data.v1',
    ],
    'authority_precedence' => array_fill_keys(
        array_map(static fn (CanonicalEntityType $type): string => $type->value, CanonicalEntityType::cases()),
        $defaultPrecedence,
    ),
    'coverage_categories' => [
        ...array_map(static fn (CanonicalEntityType $type): string => $type->value, CanonicalEntityType::cases()),
    ],
];

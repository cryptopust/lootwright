<?php

namespace Lootwright\Domain\PolicyProvenance;

use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class CapabilityRequest
{
    /** @param array<array-key, mixed> $satisfiedConditions */
    private function __construct(
        public Capability $capability,
        public string $operation,
        public string $sourceId,
        public string $sourceVersion,
        public RetrievedAt $evaluatedAt,
        public array $satisfiedConditions,
    ) {}

    /** @param array<array-key, mixed> $satisfiedConditions */
    public static function create(
        Capability $capability,
        string $operation,
        string $sourceId,
        string $sourceVersion,
        RetrievedAt $evaluatedAt,
        array $satisfiedConditions = [],
    ): DomainResult {
        $operation = trim($operation);
        $sourceId = trim($sourceId);
        $sourceVersion = trim($sourceVersion);

        if (preg_match('/^[a-z][a-z0-9._:\/-]{1,191}$/D', $operation) !== 1
            || preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceId) !== 1
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}$/D', $sourceVersion) !== 1
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidIdentifier,
                'Capability requests require canonical operation, source, and version identifiers.',
            ));
        }

        $conditions = [];

        foreach ($satisfiedConditions as $condition) {
            if (! is_string($condition)
                || preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $condition) !== 1
            ) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidIdentifier,
                    'Capability condition identifiers must be canonical strings.',
                ));
            }

            $conditions[] = $condition;
        }

        $conditions = array_values(array_unique($conditions));
        sort($conditions, SORT_STRING);

        return DomainResult::success(new self(
            $capability,
            $operation,
            $sourceId,
            $sourceVersion,
            $evaluatedAt,
            $conditions,
        ));
    }
}

<?php

namespace Lootwright\Domain\PolicyProvenance;

use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class PolicyRule
{
    /** @param list<string> $requiredConditions */
    private function __construct(
        public string $sourceId,
        public string $sourceVersion,
        public Capability $capability,
        public string $operation,
        public PolicyDecision $decision,
        public PolicyDecisionReason $reason,
        public PolicyVersion $policyVersion,
        public array $requiredConditions,
        public string $explanation,
    ) {}

    /** @param array<array-key, mixed> $requiredConditions */
    public static function create(
        DataSourceVersion $sourceVersion,
        Capability $capability,
        string $operation,
        PolicyDecision $decision,
        PolicyDecisionReason $reason,
        array $requiredConditions,
        string $explanation,
    ): DomainResult {
        $operation = trim($operation);
        $explanation = trim($explanation);

        if (preg_match('/^[a-z][a-z0-9._:\/-]{1,191}$/D', $operation) !== 1
            || $explanation === ''
            || mb_strlen($explanation) > 1000
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Policy rules require an exact canonical operation and bounded explanation.',
            ));
        }

        $conditions = [];

        foreach ($requiredConditions as $condition) {
            if (! is_string($condition)
                || preg_match('/^[a-z][a-z0-9._-]{1,63}$/D', $condition) !== 1
            ) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidIdentifier,
                    'Policy rule conditions must be canonical identifiers.',
                ));
            }

            $conditions[] = $condition;
        }

        $conditions = array_values(array_unique($conditions));
        sort($conditions, SORT_STRING);

        return DomainResult::success(new self(
            $sourceVersion->sourceId,
            $sourceVersion->version,
            $capability,
            $operation,
            $decision,
            $reason,
            $sourceVersion->policyVersion,
            $conditions,
            $explanation,
        ));
    }

    public function matches(CapabilityRequest $request): bool
    {
        return $this->sourceId === $request->sourceId
            && $this->sourceVersion === $request->sourceVersion
            && $this->capability === $request->capability
            && $this->operation === $request->operation;
    }
}

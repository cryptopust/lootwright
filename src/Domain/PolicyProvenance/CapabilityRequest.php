<?php

namespace Lootwright\Domain\PolicyProvenance;

use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class CapabilityRequest
{
    private function __construct(
        public Capability $capability,
        public GameEdition $edition,
        public string $operation,
        public string $sourceId,
    ) {}

    public static function create(
        Capability $capability,
        GameEdition $edition,
        string $operation,
        string $sourceId,
    ): DomainResult {
        $operation = trim($operation);
        $sourceId = trim($sourceId);

        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $operation) !== 1
            || preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceId) !== 1
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidIdentifier,
                'Capability requests require canonical operation and source identifiers.',
            ));
        }

        return DomainResult::success(new self($capability, $edition, $operation, $sourceId));
    }
}

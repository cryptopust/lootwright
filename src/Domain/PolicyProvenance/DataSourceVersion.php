<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class DataSourceVersion implements JsonSerializable
{
    private function __construct(
        public string $sourceId,
        public string $version,
        public PolicyVersion $policyVersion,
    ) {}

    public static function create(
        DataSource $source,
        string $version,
        PolicyVersion $policyVersion,
    ): DomainResult {
        $version = trim($version);

        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}$/D', $version) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidVersion,
                'A data-source version must be a canonical opaque version.',
            ));
        }

        return DomainResult::success(new self($source->id, $version, $policyVersion));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'source_id' => $this->sourceId,
            'version' => $this->version,
            'policy_version' => $this->policyVersion,
        ];
    }
}

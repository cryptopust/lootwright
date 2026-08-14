<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\SourceVersion;

final readonly class DataProvenance implements JsonSerializable
{
    private function __construct(
        public GameEdition $edition,
        public string $sourceId,
        public SourceVersion $sourceVersion,
        public string $checksumSha256,
        public PermissionStatus $permission,
        public CommercialUseStatus $commercialUse,
    ) {}

    public static function create(
        GameEdition $edition,
        string $sourceId,
        SourceVersion $sourceVersion,
        string $checksumSha256,
        PermissionStatus $permission,
        CommercialUseStatus $commercialUse,
    ): DomainResult {
        $sourceId = trim($sourceId);

        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceId) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidIdentifier,
                'A provenance source ID must be a canonical uppercase registry ID.',
            ));
        }

        if (! $sourceVersion->belongsTo($edition)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'The provenance source version must belong to its game edition.',
            ));
        }

        if (preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidChecksum,
                'A provenance checksum must be a lowercase SHA-256 digest.',
            ));
        }

        return DomainResult::success(new self(
            $edition,
            $sourceId,
            $sourceVersion,
            $checksumSha256,
            $permission,
            $commercialUse,
        ));
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'source_id' => $this->sourceId,
            'source_version' => $this->sourceVersion->value,
            'checksum_sha256' => $this->checksumSha256,
            'permission' => $this->permission->value,
            'commercial_use' => $this->commercialUse->value,
        ];
    }
}

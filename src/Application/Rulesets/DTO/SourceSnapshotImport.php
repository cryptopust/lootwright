<?php

namespace Lootwright\Application\Rulesets\DTO;

use DateTimeImmutable;
use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class SourceSnapshotImport
{
    /** @param array<string, mixed> $normalizedPayload */
    public function __construct(
        public string $sourceCode,
        public string $sourceVersion,
        public GameEdition $edition,
        public string $operation,
        public string $sourceUrl,
        public ?string $upstreamRevision,
        public DateTimeImmutable $retrievedAt,
        public string $checksumSha256,
        public string $contentType,
        public string $licenseIdentifier,
        public string $schemaVersion,
        public array $normalizedPayload,
    ) {
        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceCode) !== 1
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}$/D', $sourceVersion) !== 1
            || preg_match('/^[a-z][a-z0-9._:-]{1,191}$/D', $operation) !== 1
            || filter_var($sourceUrl, FILTER_VALIDATE_URL) === false
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || trim($contentType) === ''
            || trim($licenseIdentifier) === ''
            || trim($schemaVersion) === ''
        ) {
            throw new InvalidArgumentException('Source snapshot metadata is invalid.');
        }

        $encoded = json_encode($normalizedPayload, JSON_THROW_ON_ERROR);

        if (strlen($encoded) > 2_097_152) {
            throw new InvalidArgumentException('A normalized source snapshot cannot exceed 2 MiB.');
        }
    }
}

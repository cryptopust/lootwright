<?php

namespace Lootwright\Domain\Shared\Provenance;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class SourceProvenanceReference implements JsonSerializable
{
    public function __construct(
        public GameEdition $edition,
        public string $sourceCode,
        public string $sourceVersion,
        public string $checksumSha256,
        public ?string $snapshotId = null,
        public ?DateTimeImmutable $importedAt = null,
    ) {
        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceCode) !== 1
            || preg_match('/^[0-9a-z][0-9a-z._-]{0,127}$/D', $sourceVersion) !== 1
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || ($snapshotId !== null && preg_match('/^[0-9a-f-]{36}$/D', $snapshotId) !== 1)
        ) {
            throw new InvalidArgumentException('A canonical source provenance reference is invalid.');
        }
    }

    /** @return array<string, string|null> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'source_code' => $this->sourceCode,
            'source_version' => $this->sourceVersion,
            'checksum_sha256' => $this->checksumSha256,
            'snapshot_id' => $this->snapshotId,
            'imported_at' => $this->importedAt?->format(DATE_ATOM),
        ];
    }
}

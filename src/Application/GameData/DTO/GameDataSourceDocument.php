<?php

namespace Lootwright\Application\GameData\DTO;

use DateTimeImmutable;
use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class GameDataSourceDocument
{
    /** @param list<array<string, mixed>> $records */
    public function __construct(
        public GameEdition $edition,
        public string $schemaVersion,
        public string $sourceCode,
        public string $sourceVersion,
        public string $sourceSnapshotId,
        public string $sourceChecksumSha256,
        public DateTimeImmutable $importedAt,
        public string $approvalStatus,
        public array $records,
    ) {
        if (trim($schemaVersion) === ''
            || preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceCode) !== 1
            || preg_match('/^[0-9a-z][0-9a-z._-]{0,127}$/D', $sourceVersion) !== 1
            || preg_match('/^[0-9a-f-]{36}$/D', $sourceSnapshotId) !== 1
            || preg_match('/^[0-9a-f]{64}$/D', $sourceChecksumSha256) !== 1
            || ! in_array($approvalStatus, ['approved', 'pending', 'rejected'], true)
            || count($records) > 100_000
        ) {
            throw new InvalidArgumentException('Game-data source document metadata is invalid.');
        }
    }
}

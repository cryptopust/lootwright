<?php

namespace Lootwright\Application\GameData\DTO;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class NormalizedGameDataset implements JsonSerializable
{
    /** @param list<NormalizedGameDataRecord> $records */
    public function __construct(
        public GameEdition $edition,
        public string $schemaVersion,
        public string $sourceSnapshotId,
        public array $records,
        public string $checksumSha256,
    ) {
        foreach ($records as $record) {
            if ($record->edition !== $edition || $record->provenance->snapshotId !== $sourceSnapshotId) {
                throw new InvalidArgumentException('A normalized dataset cannot contain cross-edition or cross-snapshot records.');
            }
        }

        if (preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || ! hash_equals($checksumSha256, hash('sha256', CanonicalJson::encode($records)))
        ) {
            throw new InvalidArgumentException('Normalized dataset checksum is invalid.');
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'schema_version' => $this->schemaVersion,
            'source_snapshot_id' => $this->sourceSnapshotId,
            'records' => $this->records,
            'checksum_sha256' => $this->checksumSha256,
        ];
    }
}

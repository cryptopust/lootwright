<?php

namespace Lootwright\Application\Rulesets\DTO;

use DateTimeImmutable;
use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class RulesetPublication
{
    /**
     * @param  list<string>  $sourceSnapshotIds
     * @param  array<string, mixed>  $canonicalPayload
     */
    public function __construct(
        public string $id,
        public GameEdition $edition,
        public string $version,
        public string $patch,
        public ?string $league,
        public string $parserVersion,
        public string $checksumSha256,
        public string $schemaVersion,
        public array $sourceSnapshotIds,
        public array $canonicalPayload,
        public DateTimeImmutable $publishedAt,
        public ?string $supersedesRulesetVersionId = null,
    ) {
        if (preg_match('/^[0-9a-f-]{36}$/D', $id) !== 1
            || preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9a-z.-]+)?$/D', $version) !== 1
            || preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:[a-z])?$/D', $patch) !== 1
            || preg_match('/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9a-z.-]+)?$/D', $parserVersion) !== 1
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || trim($schemaVersion) === ''
            || $sourceSnapshotIds === []
            || count($sourceSnapshotIds) !== count(array_unique($sourceSnapshotIds))
        ) {
            throw new InvalidArgumentException('Published ruleset metadata is invalid.');
        }

        foreach ($sourceSnapshotIds as $snapshotId) {
            if (preg_match('/^[0-9a-f-]{36}$/D', $snapshotId) !== 1) {
                throw new InvalidArgumentException('Ruleset source snapshot IDs must be UUIDs.');
            }
        }

        if (strlen(json_encode($canonicalPayload, JSON_THROW_ON_ERROR)) > 2_097_152) {
            throw new InvalidArgumentException('A canonical ruleset cannot exceed 2 MiB.');
        }
    }
}

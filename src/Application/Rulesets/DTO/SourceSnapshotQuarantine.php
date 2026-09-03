<?php

namespace Lootwright\Application\Rulesets\DTO;

use DateTimeImmutable;
use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class SourceSnapshotQuarantine
{
    public function __construct(
        public string $sourceCode,
        public string $sourceVersion,
        public GameEdition $edition,
        public string $operation,
        public string $sourceUrl,
        public ?string $upstreamRevision,
        public DateTimeImmutable $retrievedAt,
        public string $sourceChecksumSha256,
        public string $reasonCode,
    ) {
        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $sourceCode) !== 1
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}$/D', $sourceVersion) !== 1
            || preg_match('/^[a-z][a-z0-9._:-]{1,191}$/D', $operation) !== 1
            || filter_var($sourceUrl, FILTER_VALIDATE_URL) === false
            || preg_match('/^[0-9a-f]{64}$/D', $sourceChecksumSha256) !== 1
            || preg_match('/^[a-z][a-z0-9_]{1,95}$/D', $reasonCode) !== 1
        ) {
            throw new InvalidArgumentException('Quarantined source import metadata is invalid.');
        }
    }
}

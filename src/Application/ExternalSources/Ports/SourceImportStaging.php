<?php

namespace Lootwright\Application\ExternalSources\Ports;

use Lootwright\Application\ExternalSources\DTO\SourceImportReport;
use Lootwright\Application\ExternalSources\DTO\StagedSourceRecord;
use Lootwright\Domain\Shared\Game\GameEdition;

interface SourceImportStaging
{
    /**
     * @param  list<StagedSourceRecord>  $records
     * @param  list<string>  $policyConditions
     */
    public function stage(
        string $sourceCode,
        string $sourceVersion,
        string $operation,
        GameEdition $edition,
        string $sourceChecksumSha256,
        string $normalizedChecksumSha256,
        ?string $rulesetTarget,
        array $records,
        array $policyConditions = [],
    ): SourceImportReport;

    public function approve(string $reportId, string $snapshotId): void;

    public function reject(string $reportId, string $failureCode): void;

    public function rollback(string $reportId, string $actorType): string;
}

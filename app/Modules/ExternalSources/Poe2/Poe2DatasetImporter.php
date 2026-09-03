<?php

namespace App\Modules\ExternalSources\Poe2;

use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Str;
use Lootwright\Application\ExternalSources\DTO\StagedSourceRecord;
use Lootwright\Application\ExternalSources\Ports\SourceImportStaging;
use Lootwright\Application\GameData\DTO\GameDataSourceDocument;
use Lootwright\Application\Rulesets\DTO\RulesetPublication;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Application\Rulesets\Services\GovernedRulesetLifecycle;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\GameAdapters\PoE2\Analysis\Poe2AnalysisRuleset;
use Lootwright\GameAdapters\PoE2\GameData\Poe2GameDataNormalizer;
use RuntimeException;

/** Imports the reviewed local PoE2 dataset through staging and immutable publication. */
final readonly class Poe2DatasetImporter
{
    public const SOURCE_CODE = 'POE2-DATASET-CANDIDATE';

    public const SOURCE_VERSION = 'poe2-0.3.0';

    public const SCHEMA_VERSION = 'lootwright.poe2.game-data.v1';

    private const OPERATION = 'poe2.dataset.snapshot.import';

    private const SOURCE_URL = 'https://lootwright.org/data/poe2/poe2-0.3.0.dataset.json';

    private const CONDITIONS = ['approved_source_record', 'poe2_scope', 'checksum_verified'];

    public function __construct(
        private SourceImportStaging $staging,
        private GovernedRulesetLifecycle $lifecycle,
        private SourceGovernancePolicy $policy,
        private Poe2GameDataNormalizer $normalizer,
    ) {}

    public function validateFile(string $path): Poe2DatasetImportResult
    {
        [$payload, $sourceChecksum, $normalizedChecksum] = $this->read($path);

        return new Poe2DatasetImportResult('validated', self::SOURCE_VERSION, $sourceChecksum, $normalizedChecksum, count($payload['records']));
    }

    public function importFile(string $path, bool $activate = false): Poe2DatasetImportResult
    {
        if (! (bool) config('source-governance.poe2_dataset.enabled', false)) {
            throw new DomainException('The PoE2 dataset importer is disabled.');
        }
        if ($activate && ! $this->policy->permitsActivation(self::SOURCE_CODE, self::SOURCE_VERSION)) {
            throw new DomainException('The Policy and Provenance Gate denied PoE2 ruleset activation.');
        }
        [$payload, $sourceChecksum, $normalizedChecksum] = $this->read($path);
        $records = $this->stagedRecords($payload['records']);
        $report = $this->staging->stage(self::SOURCE_CODE, self::SOURCE_VERSION, self::OPERATION, GameEdition::Poe2, $sourceChecksum, $normalizedChecksum, '0.3.0', $records, self::CONDITIONS);
        if ($report->status === 'approved' && $report->sourceSnapshotId !== null) {
            $rulesetId = null;
            if ($activate) {
                $rulesetId = $this->publish($payload, $report->sourceSnapshotId, $sourceChecksum);
                $this->lifecycle->activate($rulesetId, 'operator');
            }

            return new Poe2DatasetImportResult('succeeded', self::SOURCE_VERSION, $sourceChecksum, $normalizedChecksum, count($records), $report->sourceSnapshotId, $rulesetId, true);
        }
        if ($report->status !== 'staged') {
            throw new DomainException('The PoE2 dataset staging report is not eligible for import.');
        }
        $snapshot = $this->lifecycle->import(new SourceSnapshotImport(
            self::SOURCE_CODE,
            self::SOURCE_VERSION,
            GameEdition::Poe2,
            self::OPERATION,
            self::SOURCE_URL,
            self::SOURCE_VERSION,
            new DateTimeImmutable('now'),
            $normalizedChecksum,
            'application/json',
            'LicenseRef-Lootwright-Original',
            self::SCHEMA_VERSION,
            $payload,
            $sourceChecksum,
            $report->importRunId,
        ), self::CONDITIONS);
        if ($snapshot->snapshotId === null || $snapshot->status !== 'succeeded') {
            $this->staging->reject($report->id, 'snapshot_import_failed');
            throw new DomainException('The PoE2 dataset snapshot could not be imported.');
        }
        $this->staging->approve($report->id, $snapshot->snapshotId);
        $rulesetId = null;
        if ($activate) {
            $rulesetId = $this->publish($payload, $snapshot->snapshotId, $sourceChecksum);
            $this->lifecycle->activate($rulesetId, 'operator');
        }

        return new Poe2DatasetImportResult('succeeded', self::SOURCE_VERSION, $sourceChecksum, $normalizedChecksum, count($records), $snapshot->snapshotId, $rulesetId);
    }

    /** @return array{0:array{schema_version:string,edition:string,game_version:string,source:string,records:list<array<string,mixed>>},1:string,2:string} */
    private function read(string $path): array
    {
        if (preg_match('/^[A-Za-z]:[\\\\\/]/D', $path) !== 1 && ! str_starts_with($path, '/')) {
            throw new RuntimeException('The PoE2 dataset path must be absolute.');
        }
        $resolved = realpath($path);
        if (! is_string($resolved) || ! is_file($resolved) || is_link($resolved)) {
            throw new RuntimeException('The PoE2 dataset path must identify a regular local file.');
        }
        $contents = file_get_contents($resolved);
        if (! is_string($contents) || strlen($contents) > 2_097_152) {
            throw new RuntimeException('The PoE2 dataset exceeds the 2 MiB import bound.');
        }
        try {
            $payload = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('The PoE2 dataset is not valid JSON.', previous: $exception);
        }
        if (! is_array($payload)
            || ($payload['schema_version'] ?? null) !== self::SCHEMA_VERSION
            || ($payload['edition'] ?? null) !== GameEdition::Poe2->value
            || ($payload['game_version'] ?? null) !== '0.3.0'
            || ($payload['source'] ?? null) !== 'LOOTWRIGHT-001'
            || ! is_array($payload['records'] ?? null)
            || ! array_is_list($payload['records'])
            || count($payload['records']) < 1
            || count($payload['records']) > 100_000
        ) {
            throw new DomainException('The PoE2 dataset metadata or edition is invalid.');
        }
        $sourceChecksum = hash('sha256', $contents);
        $approvedChecksum = (string) config('source-governance.poe2_dataset.approved_revisions.'.self::SOURCE_VERSION.'.source_checksum_sha256', '');
        if ($approvedChecksum !== '' && ! hash_equals(strtolower($approvedChecksum), $sourceChecksum)) {
            throw new DomainException('The PoE2 dataset checksum does not match its approved revision.');
        }
        $normalizedChecksum = hash('sha256', CanonicalJson::encode($payload));

        return [$payload, $sourceChecksum, $normalizedChecksum];
    }

    /** @param list<array<string,mixed>> $records
     * @return list<StagedSourceRecord>
     */
    private function stagedRecords(array $records): array
    {
        return array_map(static function (array $record, int $index): StagedSourceRecord {
            $encoded = CanonicalJson::encode($record);

            return new StagedSourceRecord(
                (string) ($record['category'] ?? 'unknown').':'.(string) ($record['external_id'] ?? $index),
                hash('sha256', $encoded),
                'staged',
                null,
                $record,
            );
        }, $records, array_keys($records));
    }

    /** @param array{records:list<array<string,mixed>>} $payload */
    private function publish(array $payload, string $snapshotId, string $sourceChecksum): string
    {
        $rulesetId = (string) Str::uuid7();
        $document = new GameDataSourceDocument(GameEdition::Poe2, self::SCHEMA_VERSION, self::SOURCE_CODE, self::SOURCE_VERSION, $snapshotId, $sourceChecksum, new DateTimeImmutable('now'), 'approved', $payload['records']);
        $dataset = $this->normalizer->normalize($document);
        $entities = array_map(static fn ($record) => $record->toEntity($rulesetId), $dataset->records);
        $rulesetPayload = [...$payload, 'deterministic_analysis' => Poe2AnalysisRuleset::publishedV1()->jsonSerialize()];
        $rulesetChecksum = hash('sha256', CanonicalJson::encode($rulesetPayload));

        return $this->lifecycle->publish(new RulesetPublication(
            $rulesetId,
            GameEdition::Poe2,
            '0.3.0-analysis.'.Poe2AnalysisRuleset::publishedV1()->engineVersion,
            '0.3.0',
            null,
            '1.0.0',
            $rulesetChecksum,
            self::SCHEMA_VERSION,
            [$snapshotId],
            $rulesetPayload,
            new DateTimeImmutable('now'),
            datasetClassification: DatasetClassification::ApprovedImport,
            provenanceStatus: ProvenanceStatus::Approved,
            compatibilityStatus: RulesetCompatibilityStatus::Compatible,
            canonicalData: $entities,
        ));
    }
}

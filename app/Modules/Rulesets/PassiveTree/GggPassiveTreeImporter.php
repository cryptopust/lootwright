<?php

namespace App\Modules\Rulesets\PassiveTree;

use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Str;
use Lootwright\Application\Rulesets\DTO\RulesetPublication;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotQuarantine;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Application\Rulesets\Services\GovernedRulesetLifecycle;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1AnalysisRuleset;
use Lootwright\GameAdapters\PoE1\PassiveTree\PassiveTreeNormalizer;
use Lootwright\GameAdapters\PoE1\PassiveTree\PassiveTreeSchemaViolation;
use RuntimeException;

final readonly class GggPassiveTreeImporter
{
    public const MAX_SOURCE_BYTES = 8_000_000;

    private const SOURCE_CODE = 'GGG-POE1-SKILLTREE-001';

    private const OPERATION = 'ggg.poe1.skilltree.snapshot.import';

    private const QUARANTINE_OPERATION = 'ggg.poe1.skilltree.snapshot.quarantine';

    private const FETCH_OPERATION = 'ggg.poe1.skilltree.export.fetch';

    private const CONDITIONS = ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'];

    private const QUARANTINE_CONDITIONS = ['official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'];

    private const FETCH_CONDITIONS = ['exact_url_allowlist', 'operator_contact_configured', 'operator_workflow', 'pinned_commit', 'poe1_scope'];

    public function __construct(
        private GggPassiveTreeHttpClient $http,
        private PassiveTreeNormalizer $normalizer,
        private GovernedRulesetLifecycle $lifecycle,
        private SourceGovernancePolicy $policy,
    ) {}

    public function importFile(string $path, bool $dryRun, bool $activate): GggPassiveTreeImportResult
    {
        if ($this->remotePath($path) || ! $this->absolutePath($path)) {
            throw new RuntimeException('The file option must be an absolute local path.');
        }
        $resolved = realpath($path);
        if (! is_string($resolved) || ! is_file($resolved) || is_link($path)) {
            throw new RuntimeException('The file option must identify a regular local file.');
        }
        $size = filesize($resolved);
        if (! is_int($size) || $size > self::MAX_SOURCE_BYTES) {
            throw new RuntimeException('The passive-tree export exceeds the 8 MB size limit.');
        }
        $contents = file_get_contents($resolved, false, null, 0, self::MAX_SOURCE_BYTES + 1);
        if (! is_string($contents) || strlen($contents) > self::MAX_SOURCE_BYTES) {
            throw new RuntimeException('The passive-tree export could not be read within the size limit.');
        }
        $sourceChecksum = hash('sha256', $contents);
        $revision = $this->revisionForChecksum($sourceChecksum);

        return $this->process($contents, GggPassiveTreeUrl::forRevision($revision), $revision, $dryRun, $activate);
    }

    public function importUrl(string $url, bool $dryRun, bool $activate): GggPassiveTreeImportResult
    {
        $revision = GggPassiveTreeUrl::revision($url);
        $approved = $this->approvedRevision($revision);
        if (trim((string) config('source-governance.ggg_passive_tree.contact')) === '') {
            throw new RuntimeException('GGG_PASSIVE_TREE_CONTACT is required for URL imports.');
        }
        if (! $this->policy->permitsFetch(self::SOURCE_CODE, $revision, self::FETCH_OPERATION, self::FETCH_CONDITIONS)) {
            throw new DomainException('The source policy gate denied this fetch.');
        }
        $contents = $this->http->fetch($url);
        $sourceChecksum = hash('sha256', $contents);
        if (! hash_equals($approved['source_checksum_sha256'], $sourceChecksum)) {
            return $dryRun
                ? $this->dryRunRejection($revision, $sourceChecksum, 'source_checksum_mismatch')
                : $this->quarantine($revision, $url, $sourceChecksum, 'source_checksum_mismatch');
        }

        return $this->process($contents, $url, $revision, $dryRun, $activate);
    }

    private function process(string $contents, string $sourceUrl, string $revision, bool $dryRun, bool $activate): GggPassiveTreeImportResult
    {
        if (! (bool) config('source-governance.ggg_passive_tree.enabled', false)) {
            throw new DomainException('The GGG passive-tree importer is disabled.');
        }
        if ($dryRun && $activate) {
            throw new DomainException('--dry-run and --activate cannot be used together.');
        }
        $sourceChecksum = hash('sha256', $contents);
        $approved = $this->approvedRevision($revision);
        if (! hash_equals($approved['source_checksum_sha256'], $sourceChecksum)) {
            return $this->quarantine($revision, $sourceUrl, $sourceChecksum, 'source_checksum_mismatch');
        }
        if (! $this->policy->permitsImport(self::SOURCE_CODE, $revision, self::OPERATION, self::CONDITIONS)) {
            throw new DomainException('The source policy gate denied this import.');
        }

        try {
            $tree = $this->normalizer->normalize($contents);
        } catch (PassiveTreeSchemaViolation $exception) {
            return $dryRun
                ? $this->dryRunRejection($revision, $sourceChecksum, $exception->reasonCode)
                : $this->quarantine($revision, $sourceUrl, $sourceChecksum, $exception->reasonCode);
        }

        $payload = [
            'provenance' => [
                'game' => 'poe1',
                'source_code' => self::SOURCE_CODE,
                'source_url' => $sourceUrl,
                'upstream_commit' => $revision,
                'upstream_content_sha256' => $sourceChecksum,
            ],
            'passive_tree' => $tree,
        ];
        $snapshotChecksum = hash('sha256', CanonicalJson::encode($payload));
        if ($dryRun) {
            return new GggPassiveTreeImportResult('validated', $revision, $sourceChecksum, $snapshotChecksum, null, null, false, count($tree['classes']), count($tree['nodes']));
        }

        $retrievedAt = new DateTimeImmutable('now');
        $record = $this->lifecycle->import(new SourceSnapshotImport(
            self::SOURCE_CODE,
            $revision,
            GameEdition::Poe1,
            self::OPERATION,
            $sourceUrl,
            $revision,
            $retrievedAt,
            $snapshotChecksum,
            'application/json',
            'LicenseRef-GGG-Terms-of-Use',
            (string) config('source-governance.ggg_passive_tree.schema_version'),
            $payload,
            $sourceChecksum,
        ), self::CONDITIONS);

        $rulesetId = null;
        if ($activate) {
            if ($record->snapshotId === null) {
                throw new DomainException('A quarantined import cannot be activated.');
            }
            $rulesetPayload = [...$payload, 'deterministic_analysis' => Poe1AnalysisRuleset::publishedV1()->jsonSerialize()];
            $rulesetChecksum = hash('sha256', CanonicalJson::encode($rulesetPayload));
            $rulesetId = $this->lifecycle->publish(new RulesetPublication(
                (string) Str::uuid7(),
                GameEdition::Poe1,
                $approved['patch'].'-analysis.'.Poe1AnalysisRuleset::publishedV1()->engineVersion.'.skilltree.'.substr($revision, 0, 8),
                $approved['patch'],
                null,
                (string) config('source-governance.ggg_passive_tree.ruleset_parser_version'),
                $rulesetChecksum,
                (string) config('source-governance.ggg_passive_tree.schema_version'),
                [$record->snapshotId],
                $rulesetPayload,
                $retrievedAt,
            ));
            $this->lifecycle->activate($rulesetId, 'operator');
        }

        return new GggPassiveTreeImportResult($record->status, $revision, $sourceChecksum, $snapshotChecksum, $record->snapshotId, $rulesetId, $record->replayed, count($tree['classes']), count($tree['nodes']));
    }

    private function quarantine(string $revision, string $sourceUrl, string $sourceChecksum, string $reasonCode): GggPassiveTreeImportResult
    {
        if (! (bool) config('source-governance.ggg_passive_tree.enabled', false)) {
            throw new DomainException('The GGG passive-tree importer is disabled.');
        }
        $record = $this->lifecycle->quarantine(new SourceSnapshotQuarantine(
            self::SOURCE_CODE,
            $revision,
            GameEdition::Poe1,
            self::QUARANTINE_OPERATION,
            $sourceUrl,
            $revision,
            new DateTimeImmutable('now'),
            $sourceChecksum,
            $reasonCode,
        ), self::QUARANTINE_CONDITIONS);

        return new GggPassiveTreeImportResult('quarantined', $revision, $sourceChecksum, null, $record->snapshotId, null, $record->replayed, 0, 0, $record->status === 'quarantined' ? $reasonCode : 'quarantine_failed');
    }

    private function dryRunRejection(string $revision, string $sourceChecksum, string $reasonCode): GggPassiveTreeImportResult
    {
        return new GggPassiveTreeImportResult('rejected', $revision, $sourceChecksum, null, null, null, false, 0, 0, $reasonCode);
    }

    /** @return array{patch: string, source_checksum_sha256: string} */
    private function approvedRevision(string $revision): array
    {
        $value = config("source-governance.ggg_passive_tree.approved_revisions.{$revision}");
        if (! is_array($value) || ! is_string($value['patch'] ?? null) || ! is_string($value['source_checksum_sha256'] ?? null)) {
            throw new DomainException('The requested upstream commit is not approved.');
        }

        return ['patch' => $value['patch'], 'source_checksum_sha256' => strtolower($value['source_checksum_sha256'])];
    }

    private function revisionForChecksum(string $checksum): string
    {
        foreach ((array) config('source-governance.ggg_passive_tree.approved_revisions', []) as $revision => $metadata) {
            if (is_string($revision) && is_array($metadata) && is_string($metadata['source_checksum_sha256'] ?? null) && hash_equals(strtolower($metadata['source_checksum_sha256']), $checksum)) {
                return $revision;
            }
        }

        throw new DomainException('The local file checksum does not match an approved upstream revision.');
    }

    private function remotePath(string $path): bool
    {
        return preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $path) === 1 || str_starts_with($path, '\\\\') || str_starts_with($path, '//');
    }

    private function absolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:[\\\\\/]/D', $path) === 1 || str_starts_with($path, '/');
    }
}

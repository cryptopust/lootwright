<?php

namespace App\Modules\Rulesets;

use Illuminate\Support\Facades\DB;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\DataProvenance;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\Ports\RulesetRepository;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\RulesetId;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\Domain\Shared\Version\RulesetVersion;
use Lootwright\Domain\Shared\Version\SourceVersion;
use RuntimeException;

final class PostgresRulesetRepository implements RulesetRepository
{
    public function findById(string $id): ?GameRuleset
    {
        // Ruleset identities contain readonly domain value objects and must not
        // be serialized into the Cloud cache.  Older serialized entries can
        // hydrate as __PHP_Incomplete_Class after a deploy; the database is the
        // authoritative, checksum-verified catalog, so resolve directly.
        return $this->find(['rulesets.id' => $id]);
    }

    public function findByVersion(GameEdition $edition, string $version): ?GameRuleset
    {
        return $this->find(['rulesets.game_edition' => $edition->value, 'rulesets.version' => $version]);
    }

    /** @param array<string, string> $where */
    private function find(array $where): ?GameRuleset
    {
        $row = DB::table('ruleset_versions as rulesets')
            ->leftJoin('ruleset_dataset_approvals as approvals', 'approvals.ruleset_version_id', '=', 'rulesets.id')
            ->join('ruleset_source_snapshots as links', 'links.ruleset_version_id', '=', 'rulesets.id')
            ->join('source_snapshots as snapshots', 'snapshots.id', '=', 'links.source_snapshot_id')
            ->join('policy_data_source_versions as versions', 'versions.id', '=', 'snapshots.source_version_id')
            ->where($where)
            ->orderBy('snapshots.source_code')
            ->first([
                'rulesets.id', 'rulesets.game_edition', 'rulesets.version', 'rulesets.patch', 'rulesets.league',
                'rulesets.parser_version', 'rulesets.checksum_sha256', 'snapshots.source_code',
                'snapshots.checksum_sha256 as source_checksum_sha256', 'versions.version as source_version',
                'approvals.dataset_classification', 'approvals.provenance_status', 'approvals.compatibility_status',
            ]);

        if ($row === null) {
            return null;
        }

        $data = get_object_vars($row);
        $edition = GameEdition::from($this->string($data, 'game_edition'));
        $sourceVersion = $this->value(SourceVersion::from($edition, $this->string($data, 'source_version')), SourceVersion::class);
        $provenance = $this->value(DataProvenance::create(
            $edition,
            $this->string($data, 'source_code'),
            $sourceVersion,
            $this->string($data, 'source_checksum_sha256'),
            PermissionStatus::Allowed,
            CommercialUseStatus::Unknown,
        ), DataProvenance::class);
        $patch = $this->value(PatchVersion::from($edition, $this->string($data, 'patch')), PatchVersion::class);
        $leagueValue = $this->nullableString($data, 'league');
        $league = $leagueValue === null ? null : $this->value(LeagueId::from($edition, $leagueValue), LeagueId::class);
        $identity = $this->value(RulesetIdentity::create(
            $edition,
            $this->value(RulesetId::from($edition, $this->string($data, 'id')), RulesetId::class),
            $this->value(RulesetVersion::from($edition, $this->string($data, 'version')), RulesetVersion::class),
            $patch,
            $league,
            $this->value(ParserVersion::from($edition, $this->string($data, 'parser_version')), ParserVersion::class),
            $this->string($data, 'checksum_sha256'),
            $provenance,
        ), RulesetIdentity::class);

        return new GameRuleset(
            $identity,
            new GameVersion($edition, $patch),
            DatasetClassification::from($this->nullableString($data, 'dataset_classification') ?? 'unavailable'),
            ProvenanceStatus::from($this->nullableString($data, 'provenance_status') ?? 'pending'),
            RulesetCompatibilityStatus::from($this->nullableString($data, 'compatibility_status') ?? 'unavailable'),
        );
    }

    /**
     * @template TObject of object
     *
     * @param  class-string<TObject>  $expectedClass
     * @return TObject
     */
    private function value(DomainResult $result, string $expectedClass): object
    {
        if ($result->isFailure()) {
            throw new RuntimeException('Persisted ruleset metadata is invalid.');
        }

        $value = $result->value();
        if (! $value instanceof $expectedClass) {
            throw new RuntimeException('Persisted ruleset metadata has an unexpected type.');
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        if (! is_string($data[$key] ?? null)) {
            throw new RuntimeException("Expected string database field {$key}.");
        }

        return $data[$key];
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        return ($data[$key] ?? null) === null ? null : $this->string($data, $key);
    }
}

<?php

namespace App\Modules\Rulesets;

use Illuminate\Support\Facades\DB;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\DataProvenance;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\RulesetId;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\Domain\Shared\Version\RulesetVersion;
use Lootwright\Domain\Shared\Version\SourceVersion;
use RuntimeException;
use Throwable;

final class PostgresRulesetResolver implements RulesetResolver
{
    public function resolve(
        GameEdition $edition,
        PatchVersion $patch,
        ?LeagueId $league,
        ParserVersion $parserVersion,
    ): DomainResult {
        if (! $patch->belongsTo($edition)
            || ($league !== null && ! $league->belongsTo($edition))
            || ! $parserVersion->belongsTo($edition)
        ) {
            return $this->failure(DomainErrorCode::EditionMismatch, 'Ruleset resolution scope must belong to one game edition.');
        }

        try {
            $row = DB::table('ruleset_activations as activations')
                ->join('ruleset_versions as rulesets', 'rulesets.id', '=', 'activations.ruleset_version_id')
                ->join('ruleset_source_snapshots as links', 'links.ruleset_version_id', '=', 'rulesets.id')
                ->join('source_snapshots as snapshots', 'snapshots.id', '=', 'links.source_snapshot_id')
                ->join('policy_data_source_versions as versions', 'versions.id', '=', 'snapshots.source_version_id')
                ->where('activations.game_edition', $edition->value)
                ->where('activations.patch', $patch->value)
                ->where('activations.league_key', $league === null ? '' : $league->value)
                ->where('activations.parser_version', $parserVersion->value)
                ->where('rulesets.status', 'published')
                ->where('snapshots.status', 'valid')
                ->orderBy('snapshots.source_code')
                ->first([
                    'rulesets.id',
                    'rulesets.version',
                    'rulesets.patch',
                    'rulesets.league',
                    'rulesets.parser_version',
                    'rulesets.checksum_sha256',
                    'snapshots.source_code',
                    'snapshots.checksum_sha256 as source_checksum_sha256',
                    'versions.version as source_version',
                ]);

            if ($row === null) {
                return $this->failure(DomainErrorCode::RulesetMismatch, 'No active ruleset exactly matches the requested game, patch, league, and parser.');
            }

            $data = get_object_vars($row);
            $rulesetId = RulesetId::from($edition, $this->string($data, 'id'));
            $version = RulesetVersion::from($edition, $this->string($data, 'version'));
            $storedPatch = PatchVersion::from($edition, $this->string($data, 'patch'));
            $storedParser = ParserVersion::from($edition, $this->string($data, 'parser_version'));
            $sourceVersion = SourceVersion::from($edition, $this->string($data, 'source_version'));
            $storedLeagueValue = $this->nullableString($data, 'league');
            $storedLeague = $storedLeagueValue === null ? null : LeagueId::from($edition, $storedLeagueValue);

            foreach ([$rulesetId, $version, $storedPatch, $storedParser, $sourceVersion, $storedLeague] as $value) {
                if ($value instanceof DomainResult && $value->isFailure()) {
                    return $this->failure(DomainErrorCode::RulesetMismatch, 'The active ruleset contains invalid persisted identity metadata.');
                }
            }

            $provenance = DataProvenance::create(
                $edition,
                $this->string($data, 'source_code'),
                $sourceVersion->value(),
                $this->string($data, 'source_checksum_sha256'),
                PermissionStatus::Allowed,
                CommercialUseStatus::Unknown,
            );

            if ($provenance->isFailure()) {
                return $provenance;
            }

            return RulesetIdentity::create(
                $edition,
                $rulesetId->value(),
                $version->value(),
                $storedPatch->value(),
                $storedLeague?->value(),
                $storedParser->value(),
                $this->string($data, 'checksum_sha256'),
                $provenance->value(),
            );
        } catch (Throwable) {
            return $this->failure(DomainErrorCode::RulesetMismatch, 'The ruleset catalog is unavailable; resolution failed closed.');
        }
    }

    private function failure(DomainErrorCode $code, string $message): DomainResult
    {
        return DomainResult::failure(DomainError::because($code, $message));
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException("Expected string database field {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function nullableString(array $data, string $key): ?string
    {
        if (($data[$key] ?? null) === null) {
            return null;
        }

        return $this->string($data, $key);
    }
}

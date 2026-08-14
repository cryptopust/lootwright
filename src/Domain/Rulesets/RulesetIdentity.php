<?php

namespace Lootwright\Domain\Rulesets;

use JsonSerializable;
use Lootwright\Domain\PolicyProvenance\DataProvenance;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\RulesetId;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\Domain\Shared\Version\RulesetVersion;

final readonly class RulesetIdentity implements JsonSerializable
{
    private function __construct(
        public GameEdition $edition,
        public RulesetId $id,
        public RulesetVersion $version,
        public PatchVersion $patch,
        public ?LeagueId $league,
        public ParserVersion $parserVersion,
        public string $checksumSha256,
        public DataProvenance $provenance,
    ) {}

    public static function create(
        GameEdition $edition,
        RulesetId $id,
        RulesetVersion $version,
        PatchVersion $patch,
        ?LeagueId $league,
        ParserVersion $parserVersion,
        string $checksumSha256,
        DataProvenance $provenance,
    ): DomainResult {
        foreach ([$id, $version, $patch, $parserVersion] as $value) {
            if (! $value->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Every ruleset identity value must belong to the ruleset edition.',
                ));
            }
        }

        if ($league !== null && ! $league->belongsTo($edition)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'The ruleset league must belong to the ruleset edition.',
            ));
        }

        if ($provenance->edition !== $edition) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'Ruleset provenance must belong to the ruleset edition.',
            ));
        }

        if (preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidChecksum,
                'A ruleset checksum must be a lowercase SHA-256 digest.',
            ));
        }

        return DomainResult::success(new self(
            $edition,
            $id,
            $version,
            $patch,
            $league,
            $parserVersion,
            $checksumSha256,
            $provenance,
        ));
    }

    public function equals(self $other): bool
    {
        return $this->edition === $other->edition
            && $this->id->equals($other->id)
            && $this->version->equals($other->version)
            && $this->checksumSha256 === $other->checksumSha256;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'id' => $this->id,
            'version' => $this->version,
            'patch' => $this->patch,
            'league' => $this->league,
            'parser_version' => $this->parserVersion,
            'checksum_sha256' => $this->checksumSha256,
            'provenance' => $this->provenance,
        ];
    }
}

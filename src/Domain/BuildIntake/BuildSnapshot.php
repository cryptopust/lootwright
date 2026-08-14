<?php

namespace Lootwright\Domain\BuildIntake;

use JsonSerializable;
use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Identity\BuildId;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;

final readonly class BuildSnapshot implements JsonSerializable
{
    private function __construct(
        public BuildId $buildId,
        public GameScope $scope,
        public PatchVersion $patch,
        public ?LeagueId $league,
        public ParserVersion $parserVersion,
        public Locale $locale,
        public BuildCatalog $catalog,
        public string $inputDigestSha256,
    ) {}

    public static function create(
        BuildId $buildId,
        GameScope $scope,
        PatchVersion $patch,
        ?LeagueId $league,
        ParserVersion $parserVersion,
        Locale $locale,
        BuildCatalog $catalog,
        string $inputDigestSha256,
    ): DomainResult {
        $edition = $scope->edition;

        if (! $buildId->belongsTo($edition)
            || ! $patch->belongsTo($edition)
            || ! $parserVersion->belongsTo($edition)
            || $catalog->edition !== $edition
            || ($league !== null && ! $league->belongsTo($edition))
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'Every build snapshot value must belong to the selected edition.',
            ));
        }

        if (preg_match('/^[0-9a-f]{64}$/D', $inputDigestSha256) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidChecksum,
                'A build input digest must be a lowercase SHA-256 digest.',
            ));
        }

        return DomainResult::success(new self(
            $buildId,
            $scope,
            $patch,
            $league,
            $parserVersion,
            $locale,
            $catalog,
            $inputDigestSha256,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'build_id' => $this->buildId,
            'scope' => $this->scope,
            'patch' => $this->patch,
            'league' => $this->league,
            'parser_version' => $this->parserVersion,
            'locale' => $this->locale,
            'catalog' => $this->catalog,
            'input_digest_sha256' => $this->inputDigestSha256,
        ];
    }
}

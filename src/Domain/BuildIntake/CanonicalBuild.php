<?php

namespace Lootwright\Domain\BuildIntake;

use JsonSerializable;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class CanonicalBuild implements JsonSerializable
{
    private function __construct(
        public BuildSnapshot $snapshot,
        public RulesetIdentity $ruleset,
    ) {}

    public static function create(BuildSnapshot $snapshot, RulesetIdentity $ruleset): DomainResult
    {
        if ($snapshot->scope->edition !== $ruleset->edition) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'A build and ruleset must belong to the same game edition.',
            ));
        }

        if (! $snapshot->patch->equals($ruleset->patch)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::PatchMismatch,
                'A build and ruleset must target the same patch.',
            ));
        }

        if (($snapshot->league === null) !== ($ruleset->league === null)
            || ($snapshot->league !== null
                && $ruleset->league !== null
                && ! $snapshot->league->equals($ruleset->league))
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::LeagueMismatch,
                'A build and ruleset must target the same league scope.',
            ));
        }

        if (! $snapshot->parserVersion->equals($ruleset->parserVersion)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::ParserMismatch,
                'A build and ruleset must use the same parser version.',
            ));
        }

        return DomainResult::success(new self($snapshot, $ruleset));
    }

    /** @return array{snapshot: BuildSnapshot, ruleset: RulesetIdentity} */
    public function jsonSerialize(): array
    {
        return ['snapshot' => $this->snapshot, 'ruleset' => $this->ruleset];
    }
}

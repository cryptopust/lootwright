<?php

namespace Lootwright\Domain\Shared\Evidence;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\RulesetId;
use Lootwright\Domain\Shared\Version\RulesetVersion;

final readonly class RuleReference implements JsonSerializable
{
    private function __construct(
        public GameEdition $edition,
        public RulesetId $rulesetId,
        public RulesetVersion $rulesetVersion,
        public string $ruleKey,
    ) {}

    public static function create(
        GameEdition $edition,
        RulesetId $rulesetId,
        RulesetVersion $rulesetVersion,
        string $ruleKey,
    ): DomainResult {
        if (! $rulesetId->belongsTo($edition) || ! $rulesetVersion->belongsTo($edition)) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'A rule reference and ruleset identity must share an edition.',
            ));
        }

        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $ruleKey) !== 1) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidIdentifier,
                'A rule reference key must be canonical.',
            ));
        }

        return DomainResult::success(new self($edition, $rulesetId, $rulesetVersion, $ruleKey));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'ruleset_id' => $this->rulesetId,
            'ruleset_version' => $this->rulesetVersion,
            'rule_key' => $this->ruleKey,
        ];
    }
}

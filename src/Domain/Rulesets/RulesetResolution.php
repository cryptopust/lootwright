<?php

namespace Lootwright\Domain\Rulesets;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class RulesetResolution implements JsonSerializable
{
    public function __construct(
        public GameEdition $edition,
        public string $requestedPatch,
        public ?string $requestedLeague,
        public string $requestedParserVersion,
        public RulesetCompatibilityStatus $status,
        public ?GameRuleset $ruleset = null,
    ) {}

    public function compatible(): bool
    {
        return $this->status === RulesetCompatibilityStatus::Compatible && $this->ruleset !== null;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'requested_patch' => $this->requestedPatch,
            'requested_league' => $this->requestedLeague,
            'requested_parser_version' => $this->requestedParserVersion,
            'status' => $this->status->value,
            'ruleset' => $this->ruleset,
        ];
    }
}

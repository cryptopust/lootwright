<?php

namespace Lootwright\Application\Workflow\DTO;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\PlatformRealm;

final readonly class AnalysisSelection implements JsonSerializable
{
    public function __construct(
        public PlatformRealm $realm,
        public ?string $league,
        public ?string $contentGoal,
        public ?string $rulesetId,
        public ?string $rulesetVersion,
        public ?string $rulesetChecksumSha256,
        public bool $aiExplanationOptIn = false,
    ) {
        if (($rulesetId === null) !== ($rulesetVersion === null)
            || ($rulesetId === null) !== ($rulesetChecksumSha256 === null)
            || ($league !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $league) !== 1)
            || ($contentGoal !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $contentGoal) !== 1)
            || ($rulesetId !== null && preg_match('/^[0-9a-f-]{36}$/D', $rulesetId) !== 1)
            || ($rulesetVersion !== null && preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-[0-9a-z.-]+)?$/D', $rulesetVersion) !== 1)
            || ($rulesetChecksumSha256 !== null && preg_match('/^[0-9a-f]{64}$/D', $rulesetChecksumSha256) !== 1)
        ) {
            throw new InvalidArgumentException('Analysis selection requires canonical, complete edition-scoped values.');
        }
    }

    /** @return array<string, bool|string|null> */
    public function jsonSerialize(): array
    {
        return [
            'ai_explanation_opt_in' => $this->aiExplanationOptIn,
            'content_goal' => $this->contentGoal,
            'league' => $this->league,
            'platform_realm' => $this->realm->value,
            'ruleset_checksum_sha256' => $this->rulesetChecksumSha256,
            'ruleset_id' => $this->rulesetId,
            'ruleset_version' => $this->rulesetVersion,
        ];
    }
}

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
        public ?string $characterClass = null,
        public ?string $ascendancy = null,
        public ?int $characterLevel = null,
        public ?string $flow = null,
        public ?string $alternateAscendancy = null,
        public ?string $secondaryProgression = null,
    ) {
        if (($rulesetId === null) !== ($rulesetVersion === null)
            || ($rulesetId === null) !== ($rulesetChecksumSha256 === null)
            || ($league !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $league) !== 1)
            || ($contentGoal !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $contentGoal) !== 1)
            || ($rulesetId !== null && preg_match('/^[0-9a-f-]{36}$/D', $rulesetId) !== 1)
            || ($rulesetVersion !== null && preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:-[0-9a-z.-]+)?$/D', $rulesetVersion) !== 1)
            || ($rulesetChecksumSha256 !== null && preg_match('/^[0-9a-f]{64}$/D', $rulesetChecksumSha256) !== 1)
            || ($characterClass !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $characterClass) !== 1)
            || ($ascendancy !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $ascendancy) !== 1)
            || ($characterLevel !== null && ($characterLevel < 1 || $characterLevel > 100))
            || ($flow !== null && ! in_array($flow, ['plan', 'analyse', 'upgrade'], true))
            || ($alternateAscendancy !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $alternateAscendancy) !== 1)
            || ($secondaryProgression !== null && preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $secondaryProgression) !== 1)
        ) {
            throw new InvalidArgumentException('Analysis selection requires canonical, complete edition-scoped values.');
        }
    }

    /** @return array<string, bool|int|string|null> */
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
            'character_class' => $this->characterClass,
            'ascendancy' => $this->ascendancy,
            'character_level' => $this->characterLevel,
            'flow' => $this->flow,
            'alternate_ascendancy' => $this->alternateAscendancy,
            'secondary_progression' => $this->secondaryProgression,
        ];
    }
}

<?php

namespace Lootwright\Domain\Analysis;

use JsonSerializable;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class AnalysisResult implements JsonSerializable
{
    /** @param list<Finding> $findings
     * @param  list<RecommendationCandidate>  $recommendations
     * @param  list<string>  $unsupportedData
     */
    public function __construct(
        public GameEdition $gameEdition,
        public RulesetIdentity $ruleset,
        public string $engineVersion,
        public AnalysisStatus $status,
        public array $findings = [],
        public array $recommendations = [],
        public array $unsupportedData = [],
    ) {}

    public function canonicalJson(): string
    {
        return CanonicalJson::encode($this);
    }

    public function checksumSha256(): string
    {
        return hash('sha256', $this->canonicalJson());
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'game_edition' => $this->gameEdition->value,
            'ruleset' => $this->ruleset,
            'ruleset_version' => $this->ruleset->version->value,
            'engine_version' => $this->engineVersion,
            'status' => $this->status->value,
            'findings' => array_map(self::findingPayload(...), $this->findings),
            'recommendations' => $this->recommendations,
            'unsupported_data' => $this->unsupportedData,
        ];
    }

    /** @return array<string,mixed> */
    private static function findingPayload(Finding $finding): array
    {
        return [
            ...$finding->jsonSerialize(),
            'finding_id' => $finding->findingId,
            'game_edition' => $finding->gameEdition->value,
            'ruleset_version' => $finding->rulesetVersion->value,
            'affected_entity' => $finding->affectedEntity,
            'evidence' => $finding->evidence,
            'rule_id' => $finding->ruleId,
            'confidence' => $finding->confidence,
            'unsupported_data' => $finding->unsupportedData,
            'dependencies' => $finding->dependencies,
            'explanation_trace' => $finding->explanationTrace,
        ];
    }
}

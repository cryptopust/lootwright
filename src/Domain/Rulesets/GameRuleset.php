<?php

namespace Lootwright\Domain\Rulesets;

use InvalidArgumentException;
use JsonSerializable;

final readonly class GameRuleset implements JsonSerializable
{
    public function __construct(
        public RulesetIdentity $identity,
        public GameVersion $gameVersion,
        public DatasetClassification $classification,
        public ProvenanceStatus $provenanceStatus,
        public RulesetCompatibilityStatus $compatibilityStatus,
    ) {
        if ($identity->edition !== $gameVersion->edition || ! $identity->patch->equals($gameVersion->patch)) {
            throw new InvalidArgumentException('A game ruleset and game version must have the same edition and patch.');
        }
    }

    public function approvedForProduction(): bool
    {
        return $this->classification === DatasetClassification::ApprovedImport
            && $this->provenanceStatus === ProvenanceStatus::Approved
            && $this->compatibilityStatus === RulesetCompatibilityStatus::Compatible;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'identity' => $this->identity,
            'game_version' => $this->gameVersion,
            'dataset_classification' => $this->classification->value,
            'provenance_status' => $this->provenanceStatus->value,
            'compatibility_status' => $this->compatibilityStatus->value,
        ];
    }
}

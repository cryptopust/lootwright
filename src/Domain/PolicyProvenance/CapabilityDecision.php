<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;

final readonly class CapabilityDecision implements JsonSerializable
{
    /** @param list<string> $evidenceIds */
    public function __construct(
        public Capability $capability,
        public string $sourceId,
        public PolicyDecision $decision,
        public PolicyDecisionReason $reason,
        public PolicyVersion $policyVersion,
        public string $explanation,
        public array $evidenceIds = [],
    ) {}

    public function permitsExecution(): bool
    {
        return $this->decision === PolicyDecision::Allow;
    }

    public function isDenied(): bool
    {
        return ! $this->permitsExecution();
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'capability' => $this->capability->value,
            'source_id' => $this->sourceId,
            'decision' => $this->decision->value,
            'reason' => $this->reason->value,
            'policy_version' => $this->policyVersion->value,
            'explanation' => $this->explanation,
            'evidence_ids' => $this->evidenceIds,
        ];
    }
}

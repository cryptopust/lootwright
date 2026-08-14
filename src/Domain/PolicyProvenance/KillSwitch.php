<?php

namespace Lootwright\Domain\PolicyProvenance;

final readonly class KillSwitch
{
    public function __construct(
        public KillSwitchScope $scope,
        public bool $active,
        public ?string $sourceId = null,
        public ?Capability $capability = null,
    ) {}

    public function blocks(CapabilityRequest $request): bool
    {
        if (! $this->active) {
            return false;
        }

        return match ($this->scope) {
            KillSwitchScope::Global => true,
            KillSwitchScope::Source => $this->sourceId === $request->sourceId,
            KillSwitchScope::Capability => $this->capability === $request->capability,
            KillSwitchScope::SourceCapability => $this->sourceId === $request->sourceId
                && $this->capability === $request->capability,
        };
    }

    public function reason(): PolicyDecisionReason
    {
        return match ($this->scope) {
            KillSwitchScope::Global => PolicyDecisionReason::GlobalKillSwitch,
            KillSwitchScope::Source => PolicyDecisionReason::SourceKillSwitch,
            KillSwitchScope::Capability => PolicyDecisionReason::CapabilityKillSwitch,
            KillSwitchScope::SourceCapability => PolicyDecisionReason::SourceCapabilityKillSwitch,
        };
    }
}

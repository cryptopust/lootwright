<?php

namespace Lootwright\Domain\PolicyProvenance;

enum PolicyDecisionReason: string
{
    case ActiveEvidence = 'active_evidence';
    case ExplicitDenial = 'explicit_denial';
    case MissingRule = 'missing_rule';
    case MissingEvidence = 'missing_evidence';
    case UnknownEvidence = 'unknown_evidence';
    case ExpiredEvidence = 'expired_evidence';
    case RevokedEvidence = 'revoked_evidence';
    case ConflictingEvidence = 'conflicting_evidence';
    case UnmetConditions = 'unmet_conditions';
    case ReviewRequired = 'review_required';
    case GlobalKillSwitch = 'global_kill_switch';
    case SourceKillSwitch = 'source_kill_switch';
    case CapabilityKillSwitch = 'capability_kill_switch';
    case SourceCapabilityKillSwitch = 'source_capability_kill_switch';
}

<?php

namespace Lootwright\Domain\PolicyProvenance;

final class PolicyEvaluator
{
    /**
     * @param  list<PolicyRule>  $rules
     * @param  list<PermissionEvidence>  $evidence
     * @param  list<KillSwitch>  $killSwitches
     */
    public function decide(
        CapabilityRequest $request,
        array $rules,
        array $evidence,
        array $killSwitches,
    ): CapabilityDecision {
        foreach ($killSwitches as $killSwitch) {
            if ($killSwitch->blocks($request)) {
                return $this->decision(
                    $request,
                    PolicyDecision::Deny,
                    $killSwitch->reason(),
                    PolicyVersion::baseline(),
                    'Execution is disabled by an active emergency policy switch.',
                );
            }
        }

        $rule = null;

        foreach ($rules as $candidate) {
            if ($candidate->matches($request)) {
                $rule = $candidate;
                break;
            }
        }

        if ($rule === null) {
            return $this->decision(
                $request,
                PolicyDecision::Deny,
                PolicyDecisionReason::MissingRule,
                PolicyVersion::baseline(),
                'No exact policy rule exists for the requested operation.',
            );
        }

        if ($rule->decision === PolicyDecision::Deny) {
            return $this->decision(
                $request,
                PolicyDecision::Deny,
                $rule->reason,
                $rule->policyVersion,
                $rule->explanation,
            );
        }

        $matchingEvidence = array_values(array_filter(
            $evidence,
            static fn (PermissionEvidence $item): bool => $item->sourceId === $request->sourceId
                && $item->sourceVersion === $request->sourceVersion,
        ));

        if ($matchingEvidence === []) {
            return $this->review($request, $rule, PolicyDecisionReason::MissingEvidence, []);
        }

        $evidenceIds = array_map(
            static fn (PermissionEvidence $item): string => $item->id,
            $matchingEvidence,
        );
        sort($evidenceIds, SORT_STRING);

        foreach ([
            [PermissionStatus::Revoked, PolicyDecisionReason::RevokedEvidence],
            [PermissionStatus::Conflicting, PolicyDecisionReason::ConflictingEvidence],
            [PermissionStatus::Denied, PolicyDecisionReason::ExplicitDenial],
        ] as [$status, $reason]) {
            if ($this->containsStatus($matchingEvidence, $status)) {
                return $this->decision(
                    $request,
                    PolicyDecision::Deny,
                    $reason,
                    $rule->policyVersion,
                    'Permission evidence explicitly prevents this operation.',
                    $evidenceIds,
                );
            }
        }

        if ($this->containsStatus($matchingEvidence, PermissionStatus::Unknown)) {
            return $this->review($request, $rule, PolicyDecisionReason::UnknownEvidence, $evidenceIds);
        }

        if ($this->containsStatus($matchingEvidence, PermissionStatus::Expired)
            || ! $this->hasEffectiveAllowedEvidence($matchingEvidence, $request->evaluatedAt)
        ) {
            return $this->review($request, $rule, PolicyDecisionReason::ExpiredEvidence, $evidenceIds);
        }

        if ($rule->decision === PolicyDecision::RequireReview) {
            return $this->review($request, $rule, PolicyDecisionReason::ReviewRequired, $evidenceIds);
        }

        if (array_diff($rule->requiredConditions, $request->satisfiedConditions) !== []) {
            return $this->review($request, $rule, PolicyDecisionReason::UnmetConditions, $evidenceIds);
        }

        return $this->decision(
            $request,
            PolicyDecision::Allow,
            PolicyDecisionReason::ActiveEvidence,
            $rule->policyVersion,
            $rule->explanation,
            $evidenceIds,
        );
    }

    /** @param list<PermissionEvidence> $evidence */
    private function containsStatus(array $evidence, PermissionStatus $status): bool
    {
        foreach ($evidence as $item) {
            if ($item->status === $status) {
                return true;
            }
        }

        return false;
    }

    /** @param list<PermissionEvidence> $evidence */
    private function hasEffectiveAllowedEvidence(array $evidence, RetrievedAt $instant): bool
    {
        foreach ($evidence as $item) {
            if ($item->isEffectiveAt($instant)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $evidenceIds */
    private function review(
        CapabilityRequest $request,
        PolicyRule $rule,
        PolicyDecisionReason $reason,
        array $evidenceIds,
    ): CapabilityDecision {
        return $this->decision(
            $request,
            PolicyDecision::RequireReview,
            $reason,
            $rule->policyVersion,
            $rule->explanation,
            $evidenceIds,
        );
    }

    /** @param list<string> $evidenceIds */
    private function decision(
        CapabilityRequest $request,
        PolicyDecision $decision,
        PolicyDecisionReason $reason,
        PolicyVersion $policyVersion,
        string $explanation,
        array $evidenceIds = [],
    ): CapabilityDecision {
        return new CapabilityDecision(
            $request->capability,
            $request->sourceId,
            $decision,
            $reason,
            $policyVersion,
            $explanation,
            $evidenceIds,
        );
    }
}

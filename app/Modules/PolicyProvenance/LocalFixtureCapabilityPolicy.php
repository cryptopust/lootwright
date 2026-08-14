<?php

namespace App\Modules\PolicyProvenance;

use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\PolicyVersion;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;

/**
 * Narrow in-memory policy for the database-free local fixture command.
 * All unlisted source/version/capability/operation tuples deny execution.
 */
final readonly class LocalFixtureCapabilityPolicy implements CapabilityPolicy
{
    public function __construct(private bool $globalKillSwitch = false) {}

    public function authorize(CapabilityRequest $request): DomainResult
    {
        if ($this->globalKillSwitch) {
            return $this->decision($request, PolicyDecision::Deny, PolicyDecisionReason::GlobalKillSwitch, 'The emergency global policy kill switch is active.');
        }

        if ($request->evaluatedAt->value < PolicyDefaults::REVIEWED_AT) {
            return $this->decision($request, PolicyDecision::RequireReview, PolicyDecisionReason::MissingEvidence, 'Pinned format policy evidence was not yet effective.');
        }

        if ($request->evaluatedAt->value >= PolicyDefaults::REVIEW_EXPIRES_AT) {
            return $this->decision($request, PolicyDecision::RequireReview, PolicyDecisionReason::ExpiredEvidence, 'Pinned format policy evidence has expired.');
        }

        foreach ($this->allowedTuples() as $tuple) {
            if ($request->sourceId === $tuple['source_id']
                && $request->sourceVersion === $tuple['source_version']
                && $request->capability === $tuple['capability']
                && $request->operation === $tuple['operation']
                && array_diff($tuple['conditions'], $request->satisfiedConditions) === []
            ) {
                return $this->decision($request, PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence, 'The local fixture operation matches current pinned format evidence.', [$tuple['evidence_id']]);
            }
        }

        return $this->decision($request, PolicyDecision::Deny, PolicyDecisionReason::MissingRule, 'The local fixture policy has no exact allow rule.');
    }

    /** @return list<array{source_id: string, source_version: string, capability: Capability, operation: string, conditions: list<string>, evidence_id: string}> */
    private function allowedTuples(): array
    {
        return [
            [
                'source_id' => 'USER-PASTED-POB',
                'source_version' => '1.0.0',
                'capability' => Capability::Import,
                'operation' => 'user_input.pob_code.import',
                'conditions' => ['explicit_user_submission'],
                'evidence_id' => 'USER-PASTED-POB-EVIDENCE',
            ],
            [
                'source_id' => 'USER-PASTED-POB',
                'source_version' => '1.0.0',
                'capability' => Capability::TransientProcess,
                'operation' => 'user_input.pob_code.process',
                'conditions' => ['explicit_user_submission'],
                'evidence_id' => 'USER-PASTED-POB-EVIDENCE',
            ],
            [
                'source_id' => 'POB-COMMUNITY',
                'source_version' => Pob1Normalizer::SOURCE_COMMIT,
                'capability' => Capability::DerivativeAnalysis,
                'operation' => 'pob.community.format_interpret',
                'conditions' => ['attribution_configured', 'independent_implementation', 'pinned_repository_version'],
                'evidence_id' => 'POB-COMMUNITY-LICENSE-20260814',
            ],
            [
                'source_id' => 'POB2-COMMUNITY',
                'source_version' => Pob2Normalizer::SOURCE_COMMIT,
                'capability' => Capability::DerivativeAnalysis,
                'operation' => 'pob2.community.format_interpret',
                'conditions' => ['attribution_configured', 'independent_implementation', 'pinned_repository_version'],
                'evidence_id' => 'POB2-COMMUNITY-LICENSE-20260814',
            ],
        ];
    }

    /** @param list<string> $evidenceIds */
    private function decision(
        CapabilityRequest $request,
        PolicyDecision $decision,
        PolicyDecisionReason $reason,
        string $explanation,
        array $evidenceIds = [],
    ): DomainResult {
        return DomainResult::success(new CapabilityDecision(
            $request->capability,
            $request->sourceId,
            $decision,
            $reason,
            PolicyVersion::baseline(),
            $explanation,
            $evidenceIds,
        ));
    }
}

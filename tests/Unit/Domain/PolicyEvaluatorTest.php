<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\PolicyProvenance\AccessMode;
use Lootwright\Domain\PolicyProvenance\AttributionRequirement;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\DataSource;
use Lootwright\Domain\PolicyProvenance\DataSourceVersion;
use Lootwright\Domain\PolicyProvenance\EffectivePeriod;
use Lootwright\Domain\PolicyProvenance\EvidenceUrl;
use Lootwright\Domain\PolicyProvenance\KillSwitch;
use Lootwright\Domain\PolicyProvenance\KillSwitchScope;
use Lootwright\Domain\PolicyProvenance\PermissionEvidence;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\PolicyEvaluator;
use Lootwright\Domain\PolicyProvenance\PolicyRule;
use Lootwright\Domain\PolicyProvenance\PolicyVersion;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\PolicyProvenance\SourceType;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PolicyEvaluatorTest extends TestCase
{
    /** @return iterable<string, array{PermissionStatus, PolicyDecision, PolicyDecisionReason}> */
    public static function evidenceTransitions(): iterable
    {
        yield 'allowed' => [PermissionStatus::Allowed, PolicyDecision::Allow, PolicyDecisionReason::ActiveEvidence];
        yield 'unknown' => [PermissionStatus::Unknown, PolicyDecision::RequireReview, PolicyDecisionReason::UnknownEvidence];
        yield 'expired' => [PermissionStatus::Expired, PolicyDecision::RequireReview, PolicyDecisionReason::ExpiredEvidence];
        yield 'revoked' => [PermissionStatus::Revoked, PolicyDecision::Deny, PolicyDecisionReason::RevokedEvidence];
        yield 'conflicting' => [PermissionStatus::Conflicting, PolicyDecision::Deny, PolicyDecisionReason::ConflictingEvidence];
        yield 'denied' => [PermissionStatus::Denied, PolicyDecision::Deny, PolicyDecisionReason::ExplicitDenial];
    }

    #[DataProvider('evidenceTransitions')]
    public function test_evidence_status_transitions_fail_closed(
        PermissionStatus $status,
        PolicyDecision $expectedDecision,
        PolicyDecisionReason $expectedReason,
    ): void {
        $version = $this->version();
        $request = $this->request();
        $rule = $this->rule($version);
        $decision = (new PolicyEvaluator)->decide(
            $request,
            [$rule],
            [$this->evidence($version, $status)],
            [],
        );

        self::assertSame($expectedDecision, $decision->decision);
        self::assertSame($expectedReason, $decision->reason);
        self::assertSame($expectedDecision === PolicyDecision::Allow, $decision->permitsExecution());
    }

    public function test_missing_evidence_and_unmet_conditions_never_allow_execution(): void
    {
        $version = $this->version();
        $evaluator = new PolicyEvaluator;
        $missing = $evaluator->decide($this->request(), [$this->rule($version)], [], []);
        $unmetRequest = $this->request([]);
        $unmet = $evaluator->decide(
            $unmetRequest,
            [$this->rule($version)],
            [$this->evidence($version, PermissionStatus::Allowed)],
            [],
        );

        self::assertSame(PolicyDecision::RequireReview, $missing->decision);
        self::assertSame(PolicyDecisionReason::MissingEvidence, $missing->reason);
        self::assertSame(PolicyDecision::RequireReview, $unmet->decision);
        self::assertSame(PolicyDecisionReason::UnmetConditions, $unmet->reason);
    }

    /** @return iterable<string, array{KillSwitch}> */
    public static function blockingKillSwitches(): iterable
    {
        yield 'global' => [new KillSwitch(KillSwitchScope::Global, true)];
        yield 'source' => [new KillSwitch(KillSwitchScope::Source, true, 'FIXTURE-SOURCE')];
        yield 'capability' => [new KillSwitch(KillSwitchScope::Capability, true, capability: Capability::Import)];
        yield 'source capability' => [new KillSwitch(KillSwitchScope::SourceCapability, true, 'FIXTURE-SOURCE', Capability::Import)];
    }

    #[DataProvider('blockingKillSwitches')]
    public function test_every_kill_switch_scope_overrides_an_allow(KillSwitch $killSwitch): void
    {
        $version = $this->version();
        $decision = (new PolicyEvaluator)->decide(
            $this->request(),
            [$this->rule($version)],
            [$this->evidence($version, PermissionStatus::Allowed)],
            [$killSwitch],
        );

        self::assertSame(PolicyDecision::Deny, $decision->decision);
        self::assertStringContainsString('kill_switch', $decision->reason->value);
    }

    public function test_exact_operation_matching_and_invalid_boundaries_fail_closed(): void
    {
        $version = $this->version();
        $differentRequest = $this->value(CapabilityRequest::create(
            Capability::Import,
            'fixture.other_operation',
            'FIXTURE-SOURCE',
            '1.0.0',
            $this->instant('2026-08-14T13:20:00Z'),
            ['condition.met'],
        ), CapabilityRequest::class);
        $decision = (new PolicyEvaluator)->decide(
            $differentRequest,
            [$this->rule($version)],
            [$this->evidence($version, PermissionStatus::Allowed)],
            [],
        );

        self::assertSame(PolicyDecision::Deny, $decision->decision);
        self::assertSame(PolicyDecisionReason::MissingRule, $decision->reason);
        self::assertSame(DomainErrorCode::InvalidValue, EvidenceUrl::from('http://example.test/evidence')->error()->code);
        self::assertSame(
            DomainErrorCode::InvalidValue,
            EffectivePeriod::create(
                $this->instant('2026-08-15T00:00:00Z'),
                $this->instant('2026-08-14T00:00:00Z'),
            )->error()->code,
        );
    }

    private function version(): DataSourceVersion
    {
        $source = $this->value(DataSource::create(
            'FIXTURE-SOURCE',
            'Fixture source',
            SourceType::UserSupplied,
            AccessMode::PastedText,
        ), DataSource::class);

        return $this->value(DataSourceVersion::create(
            $source,
            '1.0.0',
            PolicyVersion::baseline(),
        ), DataSourceVersion::class);
    }

    /** @param list<string> $conditions */
    private function request(array $conditions = ['condition.met']): CapabilityRequest
    {
        return $this->value(CapabilityRequest::create(
            Capability::Import,
            'fixture.operation',
            'FIXTURE-SOURCE',
            '1.0.0',
            $this->instant('2026-08-14T13:20:00Z'),
            $conditions,
        ), CapabilityRequest::class);
    }

    private function rule(DataSourceVersion $version): PolicyRule
    {
        return $this->value(PolicyRule::create(
            $version,
            Capability::Import,
            'fixture.operation',
            PolicyDecision::Allow,
            PolicyDecisionReason::ActiveEvidence,
            ['condition.met'],
            'Fixture operation is allowed with active evidence.',
        ), PolicyRule::class);
    }

    private function evidence(DataSourceVersion $version, PermissionStatus $status): PermissionEvidence
    {
        $startsAt = $this->instant('2026-08-14T00:00:00Z');
        $period = $this->value(EffectivePeriod::create(
            $startsAt,
            $this->instant('2026-08-15T00:00:00Z'),
        ), EffectivePeriod::class);

        return $this->value(PermissionEvidence::create(
            'FIXTURE-EVIDENCE',
            $version,
            $this->value(EvidenceUrl::from('https://example.test/evidence'), EvidenceUrl::class),
            $startsAt,
            $period,
            $status,
            AttributionRequirement::none(),
            'Fixture permission evidence.',
        ), PermissionEvidence::class);
    }

    private function instant(string $value): RetrievedAt
    {
        return $this->value(RetrievedAt::from($value), RetrievedAt::class);
    }

    /** @template TObject of object
     * @param  class-string<TObject>  $expectedClass
     * @return TObject
     */
    private function value(DomainResult $result, string $expectedClass): object
    {
        $value = $result->value();

        if (! $value instanceof $expectedClass) {
            throw new RuntimeException("Expected {$expectedClass}.");
        }

        return $value;
    }
}

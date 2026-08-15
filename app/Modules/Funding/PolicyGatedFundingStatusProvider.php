<?php

namespace App\Modules\Funding;

use Carbon\CarbonImmutable;
use Lootwright\Application\Funding\DTO\FundingCostProjection;
use Lootwright\Application\Funding\DTO\FundingStatus;
use Lootwright\Application\Funding\Ports\FundingStatusProvider;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Throwable;

final readonly class PolicyGatedFundingStatusProvider implements FundingStatusProvider
{
    private const SOURCE_ID = 'LOOTWRIGHT-FUNDING';

    private const SOURCE_VERSION = '2026-08-15';

    public function __construct(private CapabilityPolicy $policy) {}

    public function current(): FundingStatus
    {
        $requirements = $this->activationRequirements();
        $conditions = ['funding_equality_permanent', 'visible_disclosure'];

        foreach ($requirements as $name => $satisfied) {
            if ($satisfied) {
                $conditions[] = $name;
            }
        }

        $decision = $this->decision($conditions);
        $requested = (bool) config('funding.requested_enabled', false);
        $enabled = $requested
            && ! in_array(false, $requirements, true)
            && $decision === PolicyDecision::Allow;

        return new FundingStatus(
            $requested,
            $enabled,
            $decision->value,
            $requirements,
            (string) config('funding.costs.currency', 'USD'),
            (string) config('funding.costs.pricing_model', 'gpt-5.4-nano'),
            (string) config('funding.costs.pricing_reviewed_on', '2026-08-15'),
            (string) config('funding.costs.pricing_source', ''),
            $this->projections(),
        );
    }

    /** @return array<string, bool> */
    private function activationRequirements(): array
    {
        $decisionId = config('funding.activation.policy_decision_id');
        $decisionDate = config('funding.activation.policy_decision_date');
        $evidenceId = config('funding.activation.evidence_record_id');
        $disclosure = config('funding.activation.disclosure_version');

        return [
            'dated_policy_decision' => is_string($decisionId)
                && preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $decisionId) === 1
                && is_string($decisionDate)
                && $this->validDecisionDate($decisionDate),
            'permission_evidence_recorded' => is_string($evidenceId)
                && preg_match('/^[A-Z][A-Z0-9-]{2,95}$/D', $evidenceId) === 1,
            'operator_activation' => (bool) config('funding.activation.operator_acknowledged', false),
            'public_disclosure_versioned' => is_string($disclosure)
                && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\.[0-9]+$/D', $disclosure) === 1,
        ];
    }

    private function validDecisionDate(string $value): bool
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');

            return $date !== null
                && $date->format('Y-m-d') === $value
                && $date->lessThanOrEqualTo(CarbonImmutable::now('UTC'));
        } catch (Throwable) {
            return false;
        }
    }

    /** @param list<string> $conditions */
    private function decision(array $conditions): PolicyDecision
    {
        $instant = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'));
        if ($instant->isFailure() || ! $instant->value() instanceof RetrievedAt) {
            return PolicyDecision::Deny;
        }

        $request = CapabilityRequest::create(
            Capability::MonetizedHosting,
            'lootwright.funding.activate',
            self::SOURCE_ID,
            self::SOURCE_VERSION,
            $instant->value(),
            $conditions,
        );
        if ($request->isFailure() || ! $request->value() instanceof CapabilityRequest) {
            return PolicyDecision::Deny;
        }

        $result = $this->policy->authorize($request->value());
        $value = $result->value();

        return $value instanceof CapabilityDecision ? $value->decision : PolicyDecision::Deny;
    }

    /** @return list<FundingCostProjection> */
    private function projections(): array
    {
        $hosting = array_sum(array_map(
            static fn (mixed $value): int => is_int($value) ? max(0, $value) : 0,
            (array) config('funding.costs.hosting_monthly_cents', []),
        ));
        $prices = (array) config('ai.prices_micro_usd_per_million', []);
        $inputPrice = $this->boundedInt($prices['input'] ?? null, 0, 100_000_000);
        $cachedPrice = $this->boundedInt($prices['cached_input'] ?? null, 0, 100_000_000);
        $outputPrice = $this->boundedInt($prices['output'] ?? null, 0, 100_000_000);
        $projections = [];

        foreach ((array) config('funding.costs.scenarios', []) as $scenario => $values) {
            if (! is_string($scenario) || ! is_array($values)) {
                continue;
            }

            $analyses = $this->boundedInt($values['analyses_per_month'] ?? null, 0, 10_000_000);
            $usageRate = $this->boundedInt($values['ai_usage_rate_basis_points'] ?? null, 0, 10_000);
            $callsPerAnalysis = $this->boundedInt($values['ai_calls_per_enabled_analysis'] ?? null, 0, 10);
            $eligible = $this->ceilDivide($analyses * $usageRate, 10_000);
            $calls = $eligible * $callsPerAnalysis;
            $uncachedTokens = $calls * $this->boundedInt($values['uncached_input_tokens_per_call'] ?? null, 0, 100_000);
            $cachedTokens = $calls * $this->boundedInt($values['cached_input_tokens_per_call'] ?? null, 0, 100_000);
            $outputTokens = $calls * $this->boundedInt($values['output_tokens_per_call'] ?? null, 0, 100_000);
            $aiMicroUsd = $this->tokenCost($uncachedTokens, $inputPrice)
                + $this->tokenCost($cachedTokens, $cachedPrice)
                + $this->tokenCost($outputTokens, $outputPrice);
            $aiCents = $this->ceilDivide($aiMicroUsd, 10_000);

            $projections[] = new FundingCostProjection(
                $scenario,
                $analyses,
                $usageRate,
                $calls,
                $hosting,
                $aiCents,
                $hosting + $aiCents,
            );
        }

        return $projections;
    }

    private function tokenCost(int $tokens, int $microUsdPerMillion): int
    {
        return $this->ceilDivide($tokens * $microUsdPerMillion, 1_000_000);
    }

    private function ceilDivide(int $numerator, int $denominator): int
    {
        return $numerator === 0 ? 0 : intdiv($numerator + $denominator - 1, $denominator);
    }

    private function boundedInt(mixed $value, int $minimum, int $maximum): int
    {
        return is_int($value) ? min($maximum, max($minimum, $value)) : $minimum;
    }
}

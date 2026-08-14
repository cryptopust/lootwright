<?php

namespace App\Modules\TradePlanning;

use Carbon\CarbonImmutable;
use Lootwright\Application\TradePlanning\Exception\ManualTradePolicyBlocked;
use Lootwright\Application\TradePlanning\Ports\ManualTradeRecipePolicy;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use RuntimeException;

final readonly class DatabaseManualTradeRecipePolicy implements ManualTradeRecipePolicy
{
    private const SOURCE_ID = 'LOOTWRIGHT-MANUAL-TRADE';

    private const SOURCE_VERSION = '1.0.0';

    public function __construct(private CapabilityPolicy $policy) {}

    public function authorize(GameEdition $edition, RulesetIdentity $ruleset): void
    {
        if ($ruleset->edition !== $edition) {
            throw new RuntimeException('The recipe policy edition and ruleset do not match.');
        }

        foreach ([
            [Capability::DerivativeAnalysis, 'trade.manual_recipe.generate', ['deterministic_input', 'exact_ruleset_resolved', 'manual_actions_only', 'no_market_data']],
            [Capability::LinkOut, 'trade.homepage.link', ['explicit_user_action', 'generic_homepage_only', 'single_link_only']],
        ] as [$capability, $operation, $conditions]) {
            $decision = $this->decide($capability, $operation, $conditions);

            if (! $decision->permitsExecution()) {
                throw new ManualTradePolicyBlocked($decision);
            }
        }
    }

    /** @param list<string> $conditions */
    private function decide(Capability $capability, string $operation, array $conditions): CapabilityDecision
    {
        $timestamp = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'));

        if ($timestamp->isFailure() || ! $timestamp->value() instanceof RetrievedAt) {
            throw new RuntimeException('The manual Trade policy timestamp is invalid.');
        }

        $request = CapabilityRequest::create(
            $capability,
            $operation,
            self::SOURCE_ID,
            self::SOURCE_VERSION,
            $timestamp->value(),
            $conditions,
        );

        if ($request->isFailure() || ! $request->value() instanceof CapabilityRequest) {
            throw new RuntimeException('The manual Trade capability request is invalid.');
        }

        $result = $this->policy->authorize($request->value());

        if ($result->isFailure() || ! $result->value() instanceof CapabilityDecision) {
            throw new RuntimeException('The Policy and Provenance Gate returned an invalid manual Trade decision.');
        }

        return $result->value();
    }
}

<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\ClarificationRequirement;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilitySet;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\PolicyDecisionReason;
use Lootwright\Domain\PolicyProvenance\PolicyVersion;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\TradePlanning\Filter\ExcludedFilter;
use Lootwright\Domain\TradePlanning\Filter\RequiredFilter;
use Lootwright\Domain\TradePlanning\ManualTradeRecipe;
use Lootwright\Domain\UsageFunding\FundingPolicy;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

class IntentPolicyAndTradeTest extends TestCase
{
    public function test_low_confidence_build_intent_requires_clarification(): void
    {
        $goal = DomainFixtures::intent(GameEdition::Poe1)->goal;
        $locale = DomainFixtures::value(Locale::from('en-US'), Locale::class);
        $confidence = DomainFixtures::value(
            Confidence::fromBasisPoints(4_999),
            Confidence::class,
        );

        $withoutClarification = BuildIntent::create($goal, $locale, $confidence, []);
        $clarification = DomainFixtures::value(ClarificationRequirement::create(
            'fixture.question',
            'Which constraint matters most?',
        ), ClarificationRequirement::class);
        $withClarification = BuildIntent::create($goal, $locale, $confidence, [$clarification]);

        self::assertSame(
            DomainErrorCode::ClarificationRequired,
            $withoutClarification->error()->code,
        );
        self::assertTrue($withClarification->isSuccess());
    }

    public function test_capability_set_denies_absent_and_commercially_unknown_capabilities(): void
    {
        $set = DomainFixtures::value(CapabilitySet::create([
            new CapabilityDecision(
                Capability::LiveFetch,
                'OPENAI-API',
                PolicyDecision::RequireReview,
                PolicyDecisionReason::ReviewRequired,
                PolicyVersion::baseline(),
                'Provider review is required.',
            ),
        ]), CapabilitySet::class);

        self::assertTrue($set->decisionFor('OPENAI-API', Capability::LiveFetch)->isDenied());
        self::assertTrue($set->decisionFor('UNKNOWN-SOURCE', Capability::MonetizedHosting)->isDenied());
    }

    public function test_funding_is_structurally_disabled(): void
    {
        $policy = FundingPolicy::disabled();

        self::assertFalse($policy->canAcceptFunds());
        self::assertSame(PermissionStatus::Denied, $policy->permission);
        self::assertSame(CommercialUseStatus::Unknown, $policy->commercialUse);
    }

    public function test_manual_filter_rejects_urls_and_api_payloads(): void
    {
        $url = RequiredFilter::create(
            GameEdition::Poe1,
            'fixture.filter',
            'Open https://example.invalid/search',
        );
        $payload = ExcludedFilter::create(
            GameEdition::Poe1,
            'fixture.exclusion',
            'Submit /api/trade/search with {}',
        );

        self::assertSame(DomainErrorCode::InvalidValue, $url->error()->code);
        self::assertSame(DomainErrorCode::InvalidValue, $payload->error()->code);
    }

    public function test_manual_recipe_rejects_cross_edition_filters(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $recommendation = DomainFixtures::recommendation($build);
        $poe2Filter = DomainFixtures::value(RequiredFilter::create(
            GameEdition::Poe2,
            'fixture.filter',
            'A descriptive fixture requirement.',
        ), RequiredFilter::class);

        $result = ManualTradeRecipe::create(
            $recommendation,
            [$poe2Filter],
            [],
            [],
            DomainFixtures::trace($build),
        );

        self::assertSame(DomainErrorCode::EditionMismatch, $result->error()->code);
    }
}

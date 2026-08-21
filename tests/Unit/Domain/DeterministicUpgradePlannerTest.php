<?php

namespace Tests\Unit\Domain;

use DateTimeImmutable;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\AnalysisStatus;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingCategory;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\BudgetUncertainty;
use Lootwright\Domain\Recommendations\DeterministicUpgradePlanner;
use Lootwright\Domain\Recommendations\MarketDataRequirement;
use Lootwright\Domain\Recommendations\MarketEvidenceFreshness;
use Lootwright\Domain\Recommendations\MarketPriceEvidence;
use Lootwright\Domain\Recommendations\UpgradeCandidate;
use Lootwright\Domain\Recommendations\UpgradeCandidateFactory;
use Lootwright\Domain\Recommendations\UpgradeClassification;
use Lootwright\Domain\Recommendations\UpgradeGraph;
use Lootwright\Domain\Recommendations\UserConstraint;
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use Lootwright\GameAdapters\PoE1\Recommendations\Poe1UpgradeCandidateFactory;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class DeterministicUpgradePlannerTest extends TestCase
{
    public function test_poe1_findings_map_to_a_byte_stable_cross_slot_upgrade_graph(): void
    {
        $analysis = new AnalysisResult(
            GameEdition::Poe1,
            DomainFixtures::ruleset(GameEdition::Poe1),
            'fixture',
            AnalysisStatus::Complete,
            [
                $this->finding('equipment.required_slot.empty', FindingCategory::Equipment, ['body_armour']),
                $this->finding('defence.fire_resistance.below_reported_max', FindingCategory::Defence),
            ],
        );
        $planner = new DeterministicUpgradePlanner([new Poe1UpgradeCandidateFactory]);
        $first = $planner->plan($analysis, DomainFixtures::intent(GameEdition::Poe1), BudgetConstraint::unknown(), new UserConstraints)->value();
        $second = $planner->plan($analysis, DomainFixtures::intent(GameEdition::Poe1), BudgetConstraint::unknown(), new UserConstraints)->value();

        self::assertSame(['upgrade.required_slot.empty', 'upgrade.fire_resistance.below_reported_max'], array_column($first->ordered(), 'id'));
        self::assertSame(['upgrade.required_slot.empty'], $first->ordered()[1]->prerequisites);
        self::assertSame(BudgetUncertainty::BudgetUnknown, $first->ordered()[1]->budgetUncertainty);
        self::assertSame(CanonicalJson::encode($first), CanonicalJson::encode($second));
    }

    public function test_mageblood_preservation_excludes_belt_replacement(): void
    {
        $graph = $this->plan([
            $this->candidate('upgrade.replace_belt', slots: ['slot:belt']),
            $this->candidate('upgrade.fix_reservation', market: false, score: 20_000),
        ], new UserConstraints([UserConstraint::keepItem('Mageblood')]));

        self::assertSame(['upgrade.fix_reservation'], array_column($graph->ordered(), 'id'));
        self::assertSame(['upgrade.replace_belt'], array_column($graph->impossibleCandidates, 'id'));
    }

    public function test_main_skill_preservation_excludes_skill_replacement(): void
    {
        $graph = $this->plan([
            $this->candidate('upgrade.replace_skill', effects: ['main_skill:replace'], market: false),
        ], new UserConstraints([UserConstraint::preserveMainSkill()]));

        self::assertSame([], $graph->candidates);
        self::assertSame('The candidate replaces the preserved main skill.', $graph->impossibleCandidates[0]->impossibleReason);
    }

    public function test_verified_price_over_a_limited_budget_is_impossible_without_inventing_price(): void
    {
        $price = new MarketPriceEvidence(
            $this->budget('60'),
            'POENINJA-ECONOMY-001',
            'fixture-1',
            new DateTimeImmutable('2026-08-21T00:00:00Z'),
            new DateTimeImmutable('2026-08-21T00:20:00Z'),
            str_repeat('a', 64),
            MarketEvidenceFreshness::Fresh,
            true,
        );
        $graph = $this->plan([$this->candidate('upgrade.market', price: $price)], budget: BudgetConstraint::limitedTo($this->budget('50')));

        self::assertSame([], $graph->candidates);
        self::assertSame(BudgetUncertainty::VerifiedOverBudget, $graph->impossibleCandidates[0]->budgetUncertainty);
    }

    public function test_unknown_market_price_remains_visible_as_requires_market_check(): void
    {
        $graph = $this->plan([$this->candidate('upgrade.market')], budget: BudgetConstraint::limitedTo($this->budget('50')));
        $candidate = $graph->ordered()[0];

        self::assertSame(UpgradeClassification::RequiresMarketCheck, $candidate->classification);
        self::assertSame(BudgetUncertainty::MarketPriceUnknown, $candidate->budgetUncertainty);
        self::assertNull($candidate->priceEvidence);
    }

    public function test_higher_scored_conflicting_upgrade_wins_deterministically(): void
    {
        $graph = $this->plan([
            $this->candidate('upgrade.a', score: 30_000),
            $this->candidate('upgrade.b', conflicts: ['upgrade.a'], score: 20_000),
        ]);

        self::assertSame(['upgrade.a'], array_column($graph->ordered(), 'id'));
    }

    public function test_conflicts_are_mutual_even_when_declared_by_the_higher_scored_candidate(): void
    {
        $graph = $this->plan([
            $this->candidate('upgrade.a', conflicts: ['upgrade.b'], score: 30_000),
            $this->candidate('upgrade.b', score: 20_000),
        ]);

        self::assertSame(['upgrade.a'], array_column($graph->ordered(), 'id'));
    }

    public function test_circular_dependencies_fail_closed(): void
    {
        $result = $this->planner([
            $this->candidate('upgrade.a', prerequisites: ['upgrade.b']),
            $this->candidate('upgrade.b', prerequisites: ['upgrade.a']),
        ])->plan($this->analysis(), DomainFixtures::intent(GameEdition::Poe1), BudgetConstraint::unknown(), new UserConstraints);

        self::assertTrue($result->isFailure());
        self::assertSame(DomainErrorCode::CircularDependency, $result->error()->code);
    }

    public function test_cross_slot_prerequisite_precedes_higher_scored_dependent_upgrade(): void
    {
        $graph = $this->plan([
            $this->candidate('upgrade.assign_body', slots: ['slot:body_armour'], market: false, score: 10_000),
            $this->candidate('upgrade.link_skill', prerequisites: ['upgrade.assign_body'], slots: ['slot:body_armour'], score: 40_000),
        ]);

        self::assertSame(['upgrade.assign_body', 'upgrade.link_skill'], array_column($graph->ordered(), 'id'));
        self::assertStringContainsString('explicit prerequisite', $graph->orderingReasons()['upgrade.link_skill']);
    }

    public function test_poe1_factory_cannot_plan_a_poe2_analysis(): void
    {
        $result = $this->planner([], GameEdition::Poe1)->plan(
            $this->analysis(GameEdition::Poe2),
            DomainFixtures::intent(GameEdition::Poe2),
            BudgetConstraint::unknown(),
            new UserConstraints,
        );

        self::assertSame(DomainErrorCode::UnsupportedInput, $result->error()->code);
    }

    /** @param list<UpgradeCandidate> $candidates */
    private function plan(array $candidates, ?UserConstraints $constraints = null, ?BudgetConstraint $budget = null): UpgradeGraph
    {
        $result = $this->planner($candidates)->plan(
            $this->analysis(),
            DomainFixtures::intent(GameEdition::Poe1),
            $budget ?? BudgetConstraint::unknown(),
            $constraints ?? new UserConstraints,
        );
        self::assertTrue($result->isSuccess(), $result->isFailure() ? $result->error()->message : '');
        self::assertInstanceOf(UpgradeGraph::class, $result->value());

        return $result->value();
    }

    /** @param list<UpgradeCandidate> $candidates */
    private function planner(array $candidates, GameEdition $edition = GameEdition::Poe1): DeterministicUpgradePlanner
    {
        return new DeterministicUpgradePlanner([new FixtureCandidateFactory($edition, $candidates)]);
    }

    private function analysis(GameEdition $edition = GameEdition::Poe1): AnalysisResult
    {
        return new AnalysisResult($edition, DomainFixtures::ruleset($edition), 'fixture', AnalysisStatus::Complete);
    }

    /**
     * @param  list<string>  $prerequisites
     * @param  list<string>  $conflicts
     * @param  list<string>  $slots
     * @param  list<string>  $effects
     */
    private function candidate(
        string $id,
        array $prerequisites = [],
        array $conflicts = [],
        array $slots = [],
        array $effects = ['finding:improved'],
        bool $market = true,
        int $score = 10_000,
        ?MarketPriceEvidence $price = null,
    ): UpgradeCandidate {
        return new UpgradeCandidate(
            $id,
            GameEdition::Poe1,
            new RulesetReference(GameEdition::Poe1, DomainFixtures::ruleset(GameEdition::Poe1)->id->value, DomainFixtures::ruleset(GameEdition::Poe1)->version->value, DomainFixtures::ruleset(GameEdition::Poe1)->checksumSha256),
            $market ? UpgradeClassification::RequiresMarketCheck : UpgradeClassification::Structural,
            $id,
            $prerequisites,
            $conflicts,
            $slots,
            ['finding.fixture'],
            $effects,
            $market ? BudgetUncertainty::MarketPriceUnknown : BudgetUncertainty::NotApplicable,
            $market ? MarketDataRequirement::Required : MarketDataRequirement::NotRequired,
            $score,
            priceEvidence: $price,
        );
    }

    private function budget(string $amount): Budget
    {
        return Budget::fromDecimal(CurrencyCode::from('DIVINE')->value(), $amount)->value();
    }

    /** @param list<string> $slots */
    private function finding(string $code, FindingCategory $category, array $slots = []): Finding
    {
        return Finding::deterministic(
            DomainFixtures::analysisId(GameEdition::Poe1),
            DomainFixtures::ruleset(GameEdition::Poe1),
            $code,
            FindingSeverity::Warning,
            $category,
            $code,
            'Deterministic fixture explanation.',
            1,
            2,
            $slots,
            [],
            [],
            ['input:fixture'],
            ['source' => 'fixture'],
        )->value();
    }
}

final readonly class FixtureCandidateFactory implements UpgradeCandidateFactory
{
    /** @param list<UpgradeCandidate> $candidates */
    public function __construct(private GameEdition $gameEdition, private array $candidates) {}

    public function edition(): GameEdition
    {
        return $this->gameEdition;
    }

    public function create(AnalysisResult $analysis, BuildIntent $intent): array
    {
        return $this->candidates;
    }
}

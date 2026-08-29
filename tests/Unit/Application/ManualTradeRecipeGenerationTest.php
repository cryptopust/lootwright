<?php

namespace Tests\Unit\Application;

use Lootwright\Application\TradePlanning\DTO\ApprovedTradeVocabulary;
use Lootwright\Application\TradePlanning\DTO\ItemConstraintDefinition;
use Lootwright\Application\TradePlanning\DTO\ItemTargetDefinition;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\DTO\NumericRange;
use Lootwright\Application\TradePlanning\DTO\RecipeFilterMode;
use Lootwright\Application\TradePlanning\DTO\SlotDependencyPlan;
use Lootwright\Application\TradePlanning\DTO\SlotFilterIntent;
use Lootwright\Application\TradePlanning\DTO\SlotUpgradePlan;
use Lootwright\Application\TradePlanning\DTO\TradeFilterDefinition;
use Lootwright\Application\TradePlanning\Exception\ManualRecipeGenerationFailed;
use Lootwright\Application\TradePlanning\Serialization\ManualTradeRecipeSerializer;
use Lootwright\Application\TradePlanning\Serialization\PlainTextManualTradeRecipeRenderer;
use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;
use Lootwright\Domain\PoeCatalog\Identifier\ModifierId;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use Lootwright\GameAdapters\PoE1\TradePlanning\Poe1ManualTradeRecipeGenerator;
use Lootwright\GameAdapters\PoE2\TradePlanning\Poe2ManualTradeRecipeGenerator;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

class ManualTradeRecipeGenerationTest extends TestCase
{
    public function test_poe1_recipe_serializes_exact_vocabulary_ranges_context_traces_and_relaxation(): void
    {
        $request = $this->poe1Request();
        $generator = new Poe1ManualTradeRecipeGenerator;
        $recipe = $generator->generate($request);

        self::assertSame('poe1', $recipe->gameEdition);
        self::assertSame('pc', $recipe->platformRealm);
        self::assertSame('fixture.league', $recipe->league);
        self::assertSame('fixture.helmet', $recipe->slot);
        self::assertSame(['currency' => 'CHAOS', 'amount' => '10'], $recipe->budget);
        self::assertNotNull($recipe->itemTarget);
        self::assertSame('Fixture Armour', $recipe->itemTarget->exactCategoryLabel);
        self::assertSame('Fixture Defensive Base Family', $recipe->itemTarget->exactBaseFamilyLabel);
        self::assertSame('fixture.finding', $recipe->itemTarget->findingCode);
        self::assertSame('fixture.rule', $recipe->itemTarget->trace->steps[0]->rule?->ruleKey);

        self::assertSame('Exact Fixture Maximum Life', $recipe->strict->required[0]->exactLabel);
        self::assertSame('90', $recipe->strict->required[0]->range?->minimum);
        self::assertSame('70', $recipe->broadFallback->required[0]->range?->minimum);
        self::assertSame('Exact Fixture Resistance', $recipe->strict->weighted[0]->exactLabel);
        self::assertSame(80, $recipe->strict->weighted[0]->weight);
        self::assertSame(50, $recipe->broadFallback->weighted[0]->weight);
        self::assertSame('25', $recipe->broadFallback->weighted[0]->range?->minimum);
        self::assertSame('Exact Conflicting Fixture Modifier', $recipe->strict->excluded[0]->exactLabel);
        self::assertSame('fixture.finding', $recipe->strict->required[0]->findingCode);
        self::assertSame('fixture.rule', $recipe->strict->required[0]->trace->steps[0]->rule?->ruleKey);
        self::assertSame('fixture.filter.life', $recipe->strict->required[0]->ruleKey);

        self::assertSame(['Rarity: Fixture Rare', 'Corrupted: No'], array_map(
            static fn ($constraint): string => $constraint->exactLabel,
            $recipe->constraints,
        ));
        self::assertSame('Has an open prefix', $recipe->affixPreference?->exactLabel);
        self::assertSame('fixture.finding', $recipe->constraints[0]->findingCode);
        self::assertSame('fixture.rule', $recipe->constraints[0]->trace->steps[0]->rule?->ruleKey);
        self::assertSame('fixture.boots', $recipe->dependencies[0]->slot);
        self::assertSame([], $recipe->unresolvedRequirements);
        self::assertSame($request->ruleset->checksumSha256, $recipe->ruleset['checksum_sha256']);
        self::assertSame('LOOTWRIGHT-001', $recipe->ruleset['source_id']);
        self::assertSame(8_500, $recipe->confidenceBasisPoints);
        self::assertSame('https://www.pathofexile.com/trade', $recipe->officialTradeHomepage);

        $first = ManualTradeRecipeSerializer::canonicalJson($recipe);
        $second = ManualTradeRecipeSerializer::canonicalJson($generator->generate($request));
        self::assertSame($first, $second);
        self::assertStringContainsString('"exact_label":"Exact Fixture Maximum Life"', $first);
        self::assertStringContainsString('"minimum":"90"', $first);
        self::assertStringNotContainsString('/api/trade/', $first);
        self::assertStringNotContainsString('?', $recipe->officialTradeHomepage);

        $plainText = PlainTextManualTradeRecipeRenderer::render($recipe);
        self::assertStringContainsString('Exact Fixture Maximum Life — minimum 90', $plainText);
        self::assertStringContainsString('Exact Fixture Resistance — minimum 40, weight 80', $plainText);
        self::assertStringNotContainsString('http', $plainText);
        self::assertStringNotContainsString('/api/', $plainText);
    }

    public function test_unmapped_modifier_is_unresolved_and_requests_clarification_without_guessing(): void
    {
        $request = $this->poe1Request(includeUnknownFilter: true);
        $recipe = (new Poe1ManualTradeRecipeGenerator)->generate($request);

        self::assertCount(1, $recipe->unresolvedRequirements);
        self::assertSame('fixture.modifier.unmapped', $recipe->unresolvedRequirements[0]->canonicalKey);
        self::assertSame('modifier', $recipe->unresolvedRequirements[0]->kind);
        self::assertStringContainsString('Which exact in-game filter label', $recipe->unresolvedRequirements[0]->clarificationQuestion);

        $serialized = ManualTradeRecipeSerializer::canonicalJson($recipe);
        self::assertStringNotContainsString('Guessed', $serialized);
        self::assertStringNotContainsString('fixture.modifier.unmapped"', ManualTradeRecipeSerializer::canonicalJson(
            new ManualTradeRecipe(
                $recipe->gameEdition,
                $recipe->platformRealm,
                $recipe->league,
                $recipe->slot,
                $recipe->budget,
                $recipe->itemTarget,
                $recipe->broadFallback,
                $recipe->strict,
                $recipe->constraints,
                $recipe->affixPreference,
                $recipe->dependencies,
                [],
                $recipe->ruleset,
                $recipe->confidenceBasisPoints,
                $recipe->officialTradeHomepage,
                $recipe->homepageLinkLabel,
            ),
        ));
    }

    public function test_conflicting_positive_filters_fail_closed(): void
    {
        $request = $this->poe1Request(includeConflictingPositive: true);

        try {
            (new Poe1ManualTradeRecipeGenerator)->generate($request);
            self::fail('Expected the conflicting filters to be rejected.');
        } catch (ManualRecipeGenerationFailed $exception) {
            self::assertSame('conflicting_filters', $exception->failureCode);
        }
    }

    public function test_patch_unproven_influence_constraint_is_unresolved_instead_of_emitted(): void
    {
        $recipe = (new Poe1ManualTradeRecipeGenerator)->generate(
            $this->poe1Request(includeUnsupportedConstraint: true),
        );

        self::assertSame(['Rarity: Fixture Rare', 'Corrupted: No'], array_map(
            static fn ($constraint): string => $constraint->exactLabel,
            $recipe->constraints,
        ));
        self::assertCount(1, $recipe->unresolvedRequirements);
        self::assertSame('item_constraint', $recipe->unresolvedRequirements[0]->kind);
        self::assertSame('influence.fixture', $recipe->unresolvedRequirements[0]->canonicalKey);
    }

    public function test_broad_recipe_cannot_tighten_a_range_or_increase_optional_weight(): void
    {
        $request = $this->poe1Request(invalidRelaxation: true);

        try {
            (new Poe1ManualTradeRecipeGenerator)->generate($request);
            self::fail('Expected invalid budget relaxation to be rejected.');
        } catch (ManualRecipeGenerationFailed $exception) {
            self::assertSame('invalid_budget_relaxation', $exception->failureCode);
        }
    }

    public function test_edition_boundaries_reject_poe2_context_in_poe1_and_keep_unknown_poe2_filters_unresolved(): void
    {
        $poe2Request = $this->poe2Request();

        try {
            (new Poe1ManualTradeRecipeGenerator)->generate($poe2Request);
            self::fail('Expected PoE1 recipe generation to reject PoE2 context.');
        } catch (ManualRecipeGenerationFailed $exception) {
            self::assertSame('recipe_context_mismatch', $exception->failureCode);
        }

        $recipe = (new Poe2ManualTradeRecipeGenerator)->generate($poe2Request);
        self::assertSame('poe2', $recipe->gameEdition);
        self::assertSame([], $recipe->strict->required);
        self::assertSame('fixture.modifier.life', $recipe->unresolvedRequirements[0]->canonicalKey);
    }

    private function poe1Request(
        bool $includeUnknownFilter = false,
        bool $includeConflictingPositive = false,
        bool $invalidRelaxation = false,
        bool $includeUnsupportedConstraint = false,
    ): ManualTradeRecipeRequest {
        $edition = GameEdition::Poe1;
        $ruleset = DomainFixtures::ruleset($edition);
        $finding = DomainFixtures::finding(DomainFixtures::canonicalBuild($edition));
        $life = $this->modifier($edition, 'fixture.modifier.life');
        $resistance = $this->modifier($edition, 'fixture.modifier.resistance');
        $conflict = $this->modifier($edition, 'fixture.modifier.conflict');
        $filters = [
            new SlotFilterIntent(
                $life,
                RecipeFilterMode::Required,
                NumericRange::create('90', null),
                null,
                RecipeFilterMode::Required,
                NumericRange::create($invalidRelaxation ? '95' : '70', null),
                null,
                'Addresses the deterministic fixture finding.',
                $finding,
            ),
            new SlotFilterIntent(
                $resistance,
                RecipeFilterMode::Weighted,
                NumericRange::create('40', null),
                80,
                RecipeFilterMode::Weighted,
                NumericRange::create('25', null),
                50,
                'Supports the fixture defensive objective.',
                $finding,
            ),
        ];

        if ($includeUnknownFilter) {
            $filters[] = new SlotFilterIntent(
                $this->modifier($edition, 'fixture.modifier.unmapped'),
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                RecipeFilterMode::Omitted,
                null,
                null,
                'Requires an exact approved mapping.',
                $finding,
            );
        }

        if ($includeConflictingPositive) {
            $filters[] = new SlotFilterIntent(
                $conflict,
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                'Conflicts with the fixture life modifier.',
                $finding,
            );
        }

        $plan = new SlotUpgradePlan(
            DomainFixtures::recommendation(DomainFixtures::canonicalBuild($edition)),
            $this->slot($edition, 'fixture.helmet'),
            'fixture.armour',
            $filters,
            $includeUnsupportedConstraint
                ? ['rarity.fixture_rare', 'state.uncorrupted', 'influence.fixture']
                : ['rarity.fixture_rare', 'state.uncorrupted'],
            'affix.open_prefix',
            [new SlotDependencyPlan(
                $this->slot($edition, 'fixture.boots'),
                'Keep the fixture movement dependency satisfied.',
                $finding,
            )],
            $this->confidence(9_000),
        );

        return new ManualTradeRecipeRequest(
            DomainFixtures::scope($edition),
            $ruleset->league,
            DomainFixtures::value(Budget::fromDecimal(
                DomainFixtures::value(CurrencyCode::from('CHAOS'), CurrencyCode::class),
                '10.0000',
            ), Budget::class),
            $ruleset,
            $this->vocabulary($ruleset, $life, $resistance, $conflict),
            $plan,
        );
    }

    private function poe2Request(): ManualTradeRecipeRequest
    {
        $edition = GameEdition::Poe2;
        $build = DomainFixtures::canonicalBuild($edition);
        $ruleset = $build->ruleset;
        $finding = DomainFixtures::finding($build);
        $modifier = $this->modifier($edition, 'fixture.modifier.life');
        $plan = new SlotUpgradePlan(
            DomainFixtures::recommendation($build),
            $this->slot($edition, 'fixture.helmet'),
            null,
            [new SlotFilterIntent(
                $modifier,
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                RecipeFilterMode::Omitted,
                null,
                null,
                'A fixture-only PoE2 intent.',
                $finding,
            )],
            [],
            null,
            [],
            $this->confidence(9_000),
        );

        return new ManualTradeRecipeRequest(
            DomainFixtures::scope($edition),
            $ruleset->league,
            null,
            $ruleset,
            new ApprovedTradeVocabulary($ruleset, [new TradeFilterDefinition(
                $modifier,
                'Fixture PoE2 Label',
                $this->rule($ruleset, 'fixture.poe2.filter'),
                $this->confidence(9_000),
            )], [], []),
            $plan,
        );
    }

    private function vocabulary(
        RulesetIdentity $ruleset,
        ModifierId $life,
        ModifierId $resistance,
        ModifierId $conflict,
    ): ApprovedTradeVocabulary {
        return new ApprovedTradeVocabulary(
            $ruleset,
            [
                new TradeFilterDefinition(
                    $life,
                    'Exact Fixture Maximum Life',
                    $this->rule($ruleset, 'fixture.filter.life'),
                    $this->confidence(9_000),
                    [$conflict->value],
                ),
                new TradeFilterDefinition(
                    $resistance,
                    'Exact Fixture Resistance',
                    $this->rule($ruleset, 'fixture.filter.resistance'),
                    $this->confidence(8_500),
                ),
                new TradeFilterDefinition(
                    $conflict,
                    'Exact Conflicting Fixture Modifier',
                    $this->rule($ruleset, 'fixture.filter.conflict'),
                    $this->confidence(8_750),
                    [$life->value],
                ),
            ],
            [new ItemTargetDefinition(
                'fixture.armour',
                'Fixture Armour',
                'Fixture Defensive Base Family',
                $this->rule($ruleset, 'fixture.target.armour'),
                $this->confidence(9_000),
            )],
            [
                new ItemConstraintDefinition(
                    'rarity.fixture_rare',
                    'Rarity: Fixture Rare',
                    $this->rule($ruleset, 'fixture.constraint.rarity'),
                    $this->confidence(9_000),
                ),
                new ItemConstraintDefinition(
                    'state.uncorrupted',
                    'Corrupted: No',
                    $this->rule($ruleset, 'fixture.constraint.corruption'),
                    $this->confidence(9_000),
                ),
                new ItemConstraintDefinition(
                    'affix.open_prefix',
                    'Has an open prefix',
                    $this->rule($ruleset, 'fixture.constraint.open-prefix'),
                    $this->confidence(8_750),
                ),
            ],
        );
    }

    private function modifier(GameEdition $edition, string $value): ModifierId
    {
        return DomainFixtures::value(ModifierId::from($edition, $value), ModifierId::class);
    }

    private function slot(GameEdition $edition, string $value): ItemSlotId
    {
        return DomainFixtures::value(ItemSlotId::from($edition, $value), ItemSlotId::class);
    }

    private function confidence(int $basisPoints): Confidence
    {
        return DomainFixtures::value(Confidence::fromBasisPoints($basisPoints), Confidence::class);
    }

    private function rule(RulesetIdentity $ruleset, string $key): RuleReference
    {
        return DomainFixtures::value(RuleReference::create(
            $ruleset->edition,
            $ruleset->id,
            $ruleset->version,
            $key,
        ), RuleReference::class);
    }
}

<?php

namespace App\Modules\Evaluation;

use Lootwright\Application\TradePlanning\DTO\ApprovedTradeVocabulary;
use Lootwright\Application\TradePlanning\DTO\ItemConstraintDefinition;
use Lootwright\Application\TradePlanning\DTO\ItemTargetDefinition;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\DTO\NumericRange;
use Lootwright\Application\TradePlanning\DTO\RecipeFilterMode;
use Lootwright\Application\TradePlanning\DTO\SlotDependencyPlan;
use Lootwright\Application\TradePlanning\DTO\SlotFilterIntent;
use Lootwright\Application\TradePlanning\DTO\SlotUpgradePlan;
use Lootwright\Application\TradePlanning\DTO\TradeFilterDefinition;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;
use Lootwright\Domain\PoeCatalog\Identifier\ModifierId;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\DataProvenance;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Recommendations\RecommendationImpact;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Evidence\TraceStep;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Identity\BuildId;
use Lootwright\Domain\Shared\Identity\RulesetId;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\Domain\Shared\Version\RulesetVersion;
use Lootwright\Domain\Shared\Version\SourceVersion;
use RuntimeException;

final class SyntheticTradeCaseFactory
{
    private const BUILD_ID = '01890f47-0f7d-7a2b-8c3d-1234567890ab';

    private const ANALYSIS_ID = '01890f47-0f7d-7a2b-9c3d-1234567890ab';

    private const RULESET_ID = '01890f47-0f7d-7a2b-ac3d-1234567890ab';

    public function request(string $mode): ManualTradeRecipeRequest
    {
        if (! in_array($mode, ['broadening', 'tightening', 'conflict', 'unresolved'], true)) {
            throw new RuntimeException('Unknown synthetic Trade evaluation mode.');
        }

        $edition = GameEdition::Poe1;
        $ruleset = $this->ruleset();
        $build = $this->build($ruleset);
        $finding = $this->finding($build);
        $life = $this->modifier('fixture.modifier.life');
        $resistance = $this->modifier('fixture.modifier.resistance');
        $conflict = $this->modifier('fixture.modifier.conflict');
        $filters = [
            new SlotFilterIntent(
                $life,
                RecipeFilterMode::Required,
                NumericRange::create('90', null),
                null,
                RecipeFilterMode::Required,
                NumericRange::create($mode === 'tightening' ? '95' : '70', null),
                null,
                'Addresses the reviewed synthetic finding.',
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
                'Supports the synthetic defensive objective.',
                $finding,
            ),
        ];

        if ($mode === 'conflict') {
            $filters[] = new SlotFilterIntent(
                $conflict,
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                'Conflicts with the synthetic life modifier.',
                $finding,
            );
        }

        if ($mode === 'unresolved') {
            $filters[] = new SlotFilterIntent(
                $this->modifier('fixture.modifier.unmapped'),
                RecipeFilterMode::Required,
                NumericRange::create('1', null),
                null,
                RecipeFilterMode::Omitted,
                null,
                null,
                'Requires exact approved vocabulary evidence.',
                $finding,
            );
        }

        $plan = new SlotUpgradePlan(
            $this->recommendation($build),
            $this->slot('fixture.helmet'),
            'fixture.armour',
            $filters,
            ['rarity.fixture_rare', 'state.uncorrupted'],
            'affix.open_prefix',
            [new SlotDependencyPlan(
                $this->slot('fixture.boots'),
                'Keep the synthetic movement dependency satisfied.',
                $finding,
            )],
            $this->confidence(9_000),
        );

        return new ManualTradeRecipeRequest(
            $this->scope(),
            $ruleset->league,
            $this->value(Budget::fromDecimal(
                $this->value(CurrencyCode::from('CHAOS'), CurrencyCode::class),
                '10.0000',
            ), Budget::class),
            $ruleset,
            $this->vocabulary($ruleset, $life, $resistance, $conflict),
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
                    'Exact Synthetic Maximum Life',
                    $this->rule($ruleset, 'fixture.filter.life'),
                    $this->confidence(9_000),
                    [$conflict->value],
                ),
                new TradeFilterDefinition(
                    $resistance,
                    'Exact Synthetic Resistance',
                    $this->rule($ruleset, 'fixture.filter.resistance'),
                    $this->confidence(8_500),
                ),
                new TradeFilterDefinition(
                    $conflict,
                    'Exact Conflicting Synthetic Modifier',
                    $this->rule($ruleset, 'fixture.filter.conflict'),
                    $this->confidence(8_750),
                    [$life->value],
                ),
            ],
            [new ItemTargetDefinition(
                'fixture.armour',
                'Synthetic Armour',
                'Synthetic Defensive Base Family',
                $this->rule($ruleset, 'fixture.target.armour'),
                $this->confidence(9_000),
            )],
            [
                new ItemConstraintDefinition(
                    'rarity.fixture_rare',
                    'Rarity: Synthetic Rare',
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

    private function ruleset(): RulesetIdentity
    {
        $edition = GameEdition::Poe1;
        $provenance = $this->value(DataProvenance::create(
            $edition,
            'LOOTWRIGHT-001',
            $this->value(SourceVersion::from($edition, 'synthetic-eval-1'), SourceVersion::class),
            str_repeat('a', 64),
            PermissionStatus::Allowed,
            CommercialUseStatus::Allowed,
        ), DataProvenance::class);

        return $this->value(RulesetIdentity::create(
            $edition,
            $this->value(RulesetId::from($edition, self::RULESET_ID), RulesetId::class),
            $this->value(RulesetVersion::from($edition, '1.0.0'), RulesetVersion::class),
            $this->value(PatchVersion::from($edition, '1.2.3'), PatchVersion::class),
            $this->value(LeagueId::from($edition, 'fixture.league'), LeagueId::class),
            $this->value(ParserVersion::from($edition, '1.0.0'), ParserVersion::class),
            str_repeat('b', 64),
            $provenance,
        ), RulesetIdentity::class);
    }

    private function build(RulesetIdentity $ruleset): CanonicalBuild
    {
        $edition = GameEdition::Poe1;
        $scope = $this->scope();
        $catalog = $this->value(BuildCatalog::create(
            $edition,
            $this->value(CharacterClassId::from($edition, 'witch'), CharacterClassId::class),
            null,
        ), BuildCatalog::class);
        $snapshot = $this->value(BuildSnapshot::create(
            $this->value(BuildId::from($edition, self::BUILD_ID), BuildId::class),
            $scope,
            $ruleset->patch,
            $ruleset->league,
            $ruleset->parserVersion,
            $this->value(Locale::from('en-US'), Locale::class),
            $catalog,
            str_repeat('c', 64),
        ), BuildSnapshot::class);

        return $this->value(CanonicalBuild::create($snapshot, $ruleset), CanonicalBuild::class);
    }

    private function finding(CanonicalBuild $build): Finding
    {
        $trace = $this->trace($build);

        return $this->value(Finding::create(
            $build,
            $this->analysisId(),
            'fixture.finding',
            FindingSeverity::Opportunity,
            'A reviewed synthetic finding.',
            ['input:fixture'],
            $trace->steps[0]->rule,
            $trace,
        ), Finding::class);
    }

    private function recommendation(CanonicalBuild $build): Recommendation
    {
        return $this->value(Recommendation::create(
            GameEdition::Poe1,
            $this->analysisId(),
            'fixture.recommendation',
            UpgradePriority::Medium,
            $this->value(RecommendationImpact::create(['fixture_dimension' => 1_000]), RecommendationImpact::class),
            [$this->finding($build)],
            [],
            $this->trace($build),
        ), Recommendation::class);
    }

    private function trace(CanonicalBuild $build): ExplanationTrace
    {
        $rule = $this->rule($build->ruleset, 'fixture.rule');
        $step = $this->value(TraceStep::create(
            'fixture.step',
            'A reviewed deterministic synthetic step.',
            ['input:fixture'],
            $rule,
        ), TraceStep::class);

        return $this->value(ExplanationTrace::create(GameEdition::Poe1, [$step]), ExplanationTrace::class);
    }

    private function scope(): GameScope
    {
        return $this->value(GameScope::create(GameEdition::Poe1, PlatformRealm::Pc), GameScope::class);
    }

    private function analysisId(): AnalysisId
    {
        return $this->value(AnalysisId::from(GameEdition::Poe1, self::ANALYSIS_ID), AnalysisId::class);
    }

    private function modifier(string $value): ModifierId
    {
        return $this->value(ModifierId::from(GameEdition::Poe1, $value), ModifierId::class);
    }

    private function slot(string $value): ItemSlotId
    {
        return $this->value(ItemSlotId::from(GameEdition::Poe1, $value), ItemSlotId::class);
    }

    private function confidence(int $basisPoints): Confidence
    {
        return $this->value(Confidence::fromBasisPoints($basisPoints), Confidence::class);
    }

    private function rule(RulesetIdentity $ruleset, string $key): RuleReference
    {
        return $this->value(RuleReference::create(
            GameEdition::Poe1,
            $ruleset->id,
            $ruleset->version,
            $key,
        ), RuleReference::class);
    }

    /**
     * @template TObject of object
     *
     * @param  class-string<TObject>  $expected
     * @return TObject
     */
    private function value(DomainResult $result, string $expected): object
    {
        if ($result->isFailure() || ! $result->value() instanceof $expected) {
            throw new RuntimeException("Unable to construct synthetic evaluation value {$expected}.");
        }

        return $result->value();
    }
}

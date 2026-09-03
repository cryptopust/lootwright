<?php

namespace Tests\Unit\Application;

use InvalidArgumentException;
use Lootwright\Application\TradePlanning\ModifierMatcher;
use Lootwright\Application\TradePlanning\TradeRecipeBuilder;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;
use Lootwright\Domain\PoeCatalog\Canonical\ModifierDefinition;
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Recommendations\BudgetUncertainty;
use Lootwright\Domain\Recommendations\MarketDataRequirement;
use Lootwright\Domain\Recommendations\UpgradeCandidate;
use Lootwright\Domain\Recommendations\UpgradeClassification;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;
use Lootwright\Domain\TradePlanning\TradeRecipe;
use Lootwright\Domain\TradePlanning\TradeVocabularyEntry;
use Lootwright\GameAdapters\PoE1\TradePlanning\Poe1TradeVocabulary;
use Lootwright\GameAdapters\PoE2\TradePlanning\Poe2TradeVocabulary;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class TradeRecipeEngineTest extends TestCase
{
    public function test_recipe_is_immutable_and_ai_modules_have_no_recipe_authority(): void
    {
        self::assertTrue((new \ReflectionClass(TradeRecipe::class))->isReadOnly());

        $root = dirname(__DIR__, 3);
        foreach ([$root.'/src/Application/AIGateway', $root.'/app/Modules/AI'] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $contents = file_get_contents($file->getPathname());
                self::assertIsString($contents);
                self::assertStringNotContainsString('TradeRecipeBuilder', $contents);
                self::assertStringNotContainsString('Domain\\TradePlanning\\TradeRecipe', $contents);
            }
        }
    }

    public function test_builds_deterministic_manual_strict_and_broad_recipes_from_approved_vocabulary(): void
    {
        $ruleset = $this->ruleset(GameEdition::Poe1);
        $recipe = $this->builder($ruleset)->build(
            $this->candidate($ruleset, [
                ['modifier_id' => 'defence.maximum_life', 'mode' => 'required', 'minimum' => '90'],
                ['modifier_id' => 'defence.cold_resistance', 'mode' => 'optional', 'minimum' => '35', 'weight' => 80],
            ]),
            DomainFixtures::snapshot(GameEdition::Poe1),
            $ruleset,
            $this->poe1Vocabulary($ruleset),
        );

        self::assertSame(GameEdition::Poe1, $recipe->gameEdition);
        self::assertSame('helmet', $recipe->slot);
        self::assertSame('Armour > Helmets', $recipe->itemClass);
        self::assertSame('90', $recipe->minimumValues['defence.maximum_life']);
        self::assertSame(80, $recipe->weights['defence.cold_resistance']);
        self::assertStringContainsString('maximum Life (minimum 90)', $recipe->strictRecipe);
        self::assertStringNotContainsString('maximum Life (minimum 90)', $recipe->broadRecipe);
        self::assertStringContainsString('maximum Life', $recipe->broadRecipe);
        self::assertSame('ring', $recipe->dependencies[0]['slot']);
        self::assertSame([], $recipe->unsupportedFilters);
        self::assertStringNotContainsString('/api/trade/', json_encode($recipe, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('POESESSID', json_encode($recipe, JSON_THROW_ON_ERROR));

        $again = $this->builder($ruleset)->build(
            $this->candidate($ruleset, [
                ['modifier_id' => 'defence.maximum_life', 'mode' => 'required', 'minimum' => '90'],
                ['modifier_id' => 'defence.cold_resistance', 'mode' => 'optional', 'minimum' => '35', 'weight' => 80],
            ]),
            DomainFixtures::snapshot(GameEdition::Poe1),
            $ruleset,
            $this->poe1Vocabulary($ruleset),
        );
        self::assertSame(json_encode($recipe, JSON_THROW_ON_ERROR), json_encode($again, JSON_THROW_ON_ERROR));
    }

    public function test_unknown_modifier_remains_an_unsupported_filter_without_a_guessed_label_or_id(): void
    {
        $ruleset = $this->ruleset(GameEdition::Poe1);
        $recipe = $this->builder($ruleset)->build(
            $this->candidate($ruleset, [['modifier_id' => 'unknown.modifier', 'mode' => 'required']]),
            DomainFixtures::snapshot(GameEdition::Poe1),
            $ruleset,
            $this->poe1Vocabulary($ruleset),
        );

        self::assertSame([], $recipe->requiredModifiers);
        self::assertSame('unknown.modifier', $recipe->unsupportedFilters[0]['modifier_id']);
        self::assertStringNotContainsString('unknown.modifier', $recipe->strictRecipe);
        self::assertArrayNotHasKey('trade_stat_id', $recipe->unsupportedFilters[0]);
    }

    public function test_modifier_provenance_mismatch_is_unsupported(): void
    {
        $ruleset = $this->ruleset(GameEdition::Poe1);
        $identity = $ruleset->identity;
        $badProvenance = new SourceProvenanceReference(GameEdition::Poe1, 'OTHER-SOURCE', 'fixture-1', str_repeat('c', 64));
        $vocabulary = new Poe1TradeVocabulary($identity, [
            new TradeVocabularyEntry(GameEdition::Poe1, 'defence.maximum_life', '+# to maximum Life', $badProvenance),
        ]);
        $recipe = $this->builder($ruleset)->build(
            $this->candidate($ruleset, [['modifier_id' => 'defence.maximum_life', 'mode' => 'required']]),
            DomainFixtures::snapshot(GameEdition::Poe1),
            $ruleset,
            $vocabulary,
        );

        self::assertSame([], $recipe->requiredModifiers);
        self::assertSame('defence.maximum_life', $recipe->unsupportedFilters[0]['modifier_id']);
    }

    public function test_duplicate_modifier_requirement_is_rejected(): void
    {
        $ruleset = $this->ruleset(GameEdition::Poe1);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only once');
        $this->builder($ruleset)->build(
            $this->candidate($ruleset, [
                ['modifier_id' => 'defence.maximum_life', 'mode' => 'required'],
                ['modifier_id' => 'defence.maximum_life', 'mode' => 'optional'],
            ]),
            DomainFixtures::snapshot(GameEdition::Poe1),
            $ruleset,
            $this->poe1Vocabulary($ruleset),
        );
    }

    public function test_conflicting_positive_modifiers_are_rejected(): void
    {
        $ruleset = $this->ruleset(GameEdition::Poe1);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('conflicting');
        $this->builder($ruleset)->build(
            $this->candidate($ruleset, [
                ['modifier_id' => 'defence.maximum_life', 'mode' => 'required'],
                ['modifier_id' => 'item.no_life', 'mode' => 'optional'],
            ]),
            DomainFixtures::snapshot(GameEdition::Poe1),
            $ruleset,
            $this->poe1Vocabulary($ruleset),
        );
    }

    public function test_poe1_and_poe2_vocabularies_are_isolated_and_poe2_requires_exact_vocabulary(): void
    {
        $poe1 = $this->ruleset(GameEdition::Poe1);
        $poe2 = $this->ruleset(GameEdition::Poe2);
        $builder = $this->builder($poe1);

        try {
            $builder->build(
                $this->candidate($poe1, [['modifier_id' => 'defence.maximum_life', 'mode' => 'required']]),
                DomainFixtures::snapshot(GameEdition::Poe1),
                $poe1,
                new Poe2TradeVocabulary($poe2->identity),
            );
            self::fail('Expected cross-edition vocabulary rejection.');
        } catch (InvalidArgumentException $exception) {
            self::assertStringContainsString('one game edition', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exact compatible ruleset');
        $this->builder($poe2)->build(
            $this->candidate($poe2, [['modifier_id' => 'defence.maximum_life', 'mode' => 'required']]),
            DomainFixtures::snapshot(GameEdition::Poe2),
            $poe2,
            new Poe2TradeVocabulary($poe2->identity),
        );
    }

    private function ruleset(GameEdition $edition): GameRuleset
    {
        $identity = DomainFixtures::ruleset($edition);

        return new GameRuleset(
            $identity,
            new GameVersion($edition, $identity->patch),
            DatasetClassification::ApprovedImport,
            ProvenanceStatus::Approved,
            RulesetCompatibilityStatus::Compatible,
        );
    }

    /** @param list<array{modifier_id:string,mode:'required'|'optional'|'excluded',minimum?:string,weight?:int}> $requirements */
    private function candidate(GameRuleset $ruleset, array $requirements): UpgradeCandidate
    {
        $identity = $ruleset->identity;

        return new UpgradeCandidate(
            id: 'upgrade.helmet.defence',
            gameEdition: $identity->edition,
            ruleset: new RulesetReference($identity->edition, $identity->id->value, $identity->version->value, $identity->checksumSha256),
            classification: UpgradeClassification::HighImpact,
            title: 'Improve helmet defences',
            prerequisites: [],
            conflicts: [],
            dependentSlots: ['ring'],
            affectedFindings: ['finding.fixture'],
            expectedEffects: ['reported_defence:improved'],
            budgetUncertainty: BudgetUncertainty::MarketPriceUnknown,
            marketDataRequirement: MarketDataRequirement::Required,
            score: 1_000,
            tradeRequirements: $requirements,
            targetSlot: 'helmet',
        );
    }

    private function poe1Vocabulary(GameRuleset $ruleset): Poe1TradeVocabulary
    {
        $identity = $ruleset->identity;
        $provenance = new SourceProvenanceReference(
            GameEdition::Poe1,
            $identity->provenance->sourceId,
            $identity->provenance->sourceVersion->value,
            $identity->provenance->checksumSha256,
        );

        return new Poe1TradeVocabulary($identity, [
            new TradeVocabularyEntry(GameEdition::Poe1, 'defence.maximum_life', '+# to maximum Life', $provenance, conflicts: ['item.no_life']),
            new TradeVocabularyEntry(GameEdition::Poe1, 'defence.cold_resistance', '+#% to Cold Resistance', $provenance),
            new TradeVocabularyEntry(GameEdition::Poe1, 'item.no_life', 'No Life modifiers', $provenance),
        ], ['helmet' => 'Armour > Helmets']);
    }

    private function builder(GameRuleset $ruleset): TradeRecipeBuilder
    {
        $provenance = new SourceProvenanceReference(
            $ruleset->identity->edition,
            $ruleset->identity->provenance->sourceId,
            $ruleset->identity->provenance->sourceVersion->value,
            $ruleset->identity->provenance->checksumSha256,
        );
        $repository = new class($ruleset, $provenance) implements GameDataRepository
        {
            public function __construct(
                private readonly GameRuleset $ruleset,
                private readonly SourceProvenanceReference $provenance,
            ) {}

            public function find(GameEdition $edition, string $rulesetVersionId, CanonicalEntityType $type, string $externalId): ?CanonicalGameEntity
            {
                if ($edition !== $this->ruleset->identity->edition
                    || $rulesetVersionId !== $this->ruleset->identity->id->value
                    || $type !== CanonicalEntityType::ModifierDefinition
                    || ! in_array($externalId, ['defence.maximum_life', 'defence.cold_resistance', 'item.no_life'], true)
                ) {
                    return null;
                }

                return new ModifierDefinition($edition, $rulesetVersionId, $externalId, $externalId, $this->provenance);
            }

            public function listForRuleset(GameEdition $edition, string $rulesetVersionId, ?CanonicalEntityType $type = null): array
            {
                return [];
            }
        };

        return new TradeRecipeBuilder(new ModifierMatcher($repository));
    }
}

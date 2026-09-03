<?php

namespace Tests\Unit\Application;

use Lootwright\Application\Analysis\UseCases\CreatePrioritizedUpgrades;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\Analysis\Ports\BuildAnalyzer;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\Recommendations\BudgetConstraint;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Recommendations\RecommendationImpact;
use Lootwright\Domain\Recommendations\UserConstraints;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\TradePlanning\Filter\RequiredFilter;
use Lootwright\Domain\TradePlanning\ManualTradeRecipe;
use Lootwright\Domain\TradePlanning\Ports\TradeRecipeCompiler;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class CreatePrioritizedUpgradesTest extends TestCase
{
    public function test_products_are_deterministically_prioritized_and_every_recommendation_gets_a_recipe(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $analysisId = DomainFixtures::analysisId(GameEdition::Poe1);
        $intent = DomainFixtures::intent(GameEdition::Poe1);
        $information = $this->finding($build, $analysisId, 'finding.information', FindingSeverity::Information);
        $critical = $this->finding($build, $analysisId, 'finding.critical', FindingSeverity::Critical);
        $analyzer = new FixtureBuildAnalyzer([$information, $critical]);
        $planner = new FixtureUpgradePlanner;
        $compiler = new FixtureRecipeCompiler;

        $products = (new CreatePrioritizedUpgrades($analyzer, $planner, $compiler))->handle(
            $analysisId,
            $build,
            $intent,
        );

        self::assertSame(['finding.critical', 'finding.information'], array_column($products->findings, 'code'));
        self::assertSame(['upgrade.critical', 'upgrade.low'], array_column($products->recommendations, 'code'));
        self::assertSame(['upgrade.critical', 'upgrade.low'], array_column($products->recipes, 'recommendationCode'));
        self::assertSame(['finding.critical', 'finding.information'], $planner->receivedFindingCodes);
        self::assertSame(['upgrade.critical', 'upgrade.low'], $compiler->compiledRecommendationCodes);
    }

    private function finding(
        CanonicalBuild $build,
        AnalysisId $analysisId,
        string $code,
        FindingSeverity $severity,
    ): Finding {
        $trace = DomainFixtures::trace($build);

        return DomainFixtures::value(Finding::create(
            $build,
            $analysisId,
            $code,
            $severity,
            'A deterministic prioritization fixture.',
            ['input:fixture'],
            $trace->steps[0]->rule,
            $trace,
        ), Finding::class);
    }
}

final readonly class FixtureBuildAnalyzer implements BuildAnalyzer
{
    /** @param list<Finding> $findings */
    public function __construct(private array $findings) {}

    public function analyze(AnalysisId $analysisId, CanonicalBuild $build, BuildIntent $intent): DomainResult
    {
        return DomainResult::success($this->findings);
    }
}

final class FixtureUpgradePlanner implements UpgradePlanner
{
    /** @var list<string> */
    public array $receivedFindingCodes = [];

    public function plan(array|AnalysisResult $findings, BuildIntent $intent, ?BudgetConstraint $budget = null, ?UserConstraints $constraints = null): DomainResult
    {
        if (! is_array($findings)) {
            throw new \RuntimeException('The legacy fixture expects finding-list input.');
        }
        $this->receivedFindingCodes = array_column($findings, 'code');
        $impact = DomainFixtures::value(
            RecommendationImpact::create(['fixture_dimension' => 1_000]),
            RecommendationImpact::class,
        );
        $trace = $findings[0]->trace;
        $analysisId = $findings[0]->analysisId;
        $edition = $findings[0]->rule->edition;
        $low = DomainFixtures::value(Recommendation::create(
            $edition,
            $analysisId,
            'upgrade.low',
            UpgradePriority::Low,
            $impact,
            [$findings[1]],
            [],
            $trace,
        ), Recommendation::class);
        $critical = DomainFixtures::value(Recommendation::create(
            $edition,
            $analysisId,
            'upgrade.critical',
            UpgradePriority::Critical,
            $impact,
            [$findings[0]],
            [],
            $trace,
        ), Recommendation::class);

        return DomainResult::success([$low, $critical]);
    }
}

final class FixtureRecipeCompiler implements TradeRecipeCompiler
{
    /** @var list<string> */
    public array $compiledRecommendationCodes = [];

    public function compile(Recommendation $recommendation): DomainResult
    {
        $this->compiledRecommendationCodes[] = $recommendation->code;
        $required = DomainFixtures::value(RequiredFilter::create(
            $recommendation->edition,
            'filter.required',
            'Use an approved deterministic fixture filter.',
        ), RequiredFilter::class);

        return ManualTradeRecipe::create(
            $recommendation,
            [$required],
            [],
            [],
            $recommendation->trace,
        );
    }
}

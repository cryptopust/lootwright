<?php

namespace Lootwright\Application\Analysis\UseCases;

use Lootwright\Application\Analysis\DTO\DeterministicProducts;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\Ports\BuildAnalyzer;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Recommendations\Ports\UpgradePlanner;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\TradePlanning\ManualTradeRecipe;
use Lootwright\Domain\TradePlanning\Ports\TradeRecipeCompiler;
use RuntimeException;

final readonly class CreatePrioritizedUpgrades
{
    public function __construct(
        private BuildAnalyzer $analyzer,
        private UpgradePlanner $planner,
        private TradeRecipeCompiler $recipes,
    ) {}

    public function handle(AnalysisId $analysisId, CanonicalBuild $build, BuildIntent $intent): DeterministicProducts
    {
        $findings = $this->list($this->analyzer->analyze($analysisId, $build, $intent), Finding::class);
        usort($findings, static fn (Finding $left, Finding $right): int => [$right->severity->value, $left->code] <=> [$left->severity->value, $right->code]);
        $recommendations = $this->list($this->planner->plan($findings, $intent), Recommendation::class);
        usort($recommendations, static fn (Recommendation $left, Recommendation $right): int => [$right->priority->value, $left->code] <=> [$left->priority->value, $right->code]);
        $recipes = [];

        foreach ($recommendations as $recommendation) {
            $recipe = $this->recipes->compile($recommendation);

            if ($recipe->isFailure() || ! $recipe->value() instanceof ManualTradeRecipe) {
                throw new RuntimeException('The deterministic recipe compiler returned an invalid result.');
            }

            $recipes[] = $recipe->value();
        }

        return new DeterministicProducts($findings, $recommendations, $recipes);
    }

    /** @template TObject of object
     * @param  class-string<TObject>  $expected
     * @return list<TObject>
     */
    private function list(DomainResult $result, string $expected): array
    {
        if ($result->isFailure()) {
            throw new RuntimeException($result->error()->message);
        }

        $values = $result->value();
        if (! is_array($values) || ! array_is_list($values)) {
            throw new RuntimeException('A deterministic domain port returned a non-list result.');
        }

        foreach ($values as $value) {
            if (! $value instanceof $expected) {
                throw new RuntimeException('A deterministic domain port returned an unexpected value type.');
            }
        }

        return $values;
    }
}

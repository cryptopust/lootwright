<?php

namespace Lootwright\GameAdapters\PoE2\TradePlanning;

use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipe;
use Lootwright\Application\TradePlanning\DTO\ManualTradeRecipeRequest;
use Lootwright\Application\TradePlanning\DTO\RecipeDependency;
use Lootwright\Application\TradePlanning\DTO\RecipeVariant;
use Lootwright\Application\TradePlanning\DTO\UnresolvedRequirement;
use Lootwright\Application\TradePlanning\Exception\ManualRecipeGenerationFailed;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Shared\Game\GameEdition;

final class Poe2ManualTradeRecipeGenerator
{
    public function generate(ManualTradeRecipeRequest $request): ManualTradeRecipe
    {
        $ruleset = $request->ruleset;
        if ($request->scope->edition !== GameEdition::Poe2 || $ruleset->edition !== GameEdition::Poe2 || $request->plan->recommendation->edition !== GameEdition::Poe2 || ! $request->plan->slot->belongsTo(GameEdition::Poe2) || ! $request->vocabulary->ruleset->equals($ruleset) || $ruleset->provenance->permission !== PermissionStatus::Allowed || $ruleset->provenance->commercialUse !== CommercialUseStatus::Allowed) {
            throw new ManualRecipeGenerationFailed('recipe_context_mismatch', 'PoE2 recipe scope, vocabulary, and ruleset must match exactly.');
        }
        if ($request->plan->filters === []) {
            throw new ManualRecipeGenerationFailed('empty_filter_plan', 'A PoE2 slot recipe requires at least one deterministic filter intent.');
        }
        $finding = $request->plan->recommendation->findings[0];
        $unresolved = array_map(fn ($intent): UnresolvedRequirement => new UnresolvedRequirement('modifier', $intent->modifierId->value, 'The active PoE2 ruleset does not prove a Trade filter mapping for this modifier.', 'Which exact in-game PoE2 filter label should represent '.$intent->modifierId->value.' for this patch?', $finding->code, $finding->trace), $request->plan->filters);

        return new ManualTradeRecipe(
            'poe2', $request->scope->realm->value, $request->league?->value, $request->plan->slot->value,
            $request->budget?->jsonSerialize(), null, new RecipeVariant('Broad fallback recipe', [], [], []),
            new RecipeVariant('Stricter recipe', [], [], []), [], null,
            array_map(static fn ($dependency): RecipeDependency => new RecipeDependency($dependency->slot->value, $dependency->reason, $dependency->finding->code, $dependency->finding->trace), $request->plan->dependencies), $unresolved,
            ['id' => $ruleset->id->value, 'version' => $ruleset->version->value, 'checksum_sha256' => $ruleset->checksumSha256, 'patch' => $ruleset->patch->value, 'league' => $ruleset->league?->value, 'parser_version' => $ruleset->parserVersion->value, 'source_id' => $ruleset->provenance->sourceId, 'source_version' => $ruleset->provenance->sourceVersion->value],
            min($request->plan->confidence->basisPoints, 5_000), 'https://www.pathofexile.com/trade', 'Open the official Path of Exile 2 Trade homepage',
        );
    }
}

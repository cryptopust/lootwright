<?php

namespace App\Modules\Rulesets;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\GameData\Ports\SourceAuthorityRegistry;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Shared\Game\GameEdition;

final class DatabaseSourceAuthorityRegistry implements SourceAuthorityRegistry
{
    public function tier(GameEdition $edition, CanonicalEntityType $category, string $sourceCode): ?string
    {
        $tier = DB::table('game_data_source_authorities')
            ->where('game_edition', $edition->value)
            ->where('data_category', $category->value)
            ->where('source_code', $sourceCode)
            ->where('enabled', true)
            ->value('authority_tier');

        return is_string($tier) ? $tier : null;
    }
}

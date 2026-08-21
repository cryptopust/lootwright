<?php

namespace App\Modules\ExternalSources\PoeWiki;

use App\Modules\ExternalSources\DisabledSourceAdapter;
use Lootwright\Application\ExternalSources\Ports\ItemMetadataProvider;
use Lootwright\Application\ExternalSources\Ports\ModifierMetadataProvider;
use Lootwright\Application\ExternalSources\Ports\PoeWikiCompatibleSourceAdapter;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Deliberately has no HTTP client: activation needs a separate licensing and funding review. */
final class DisabledPoeWikiCargoAdapter extends DisabledSourceAdapter implements ItemMetadataProvider, ModifierMetadataProvider, PoeWikiCompatibleSourceAdapter
{
    public function __construct()
    {
        parent::__construct(
            'POEWIKI-CARGO-001',
            'candidate-2026-08-20',
            [GameEdition::Poe1],
            ['cargo_ingestion_contract', 'item_metadata_contract', 'modifier_metadata_contract'],
            'permission_and_underlying_rights_review_required',
        );
    }
}

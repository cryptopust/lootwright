<?php

namespace App\Modules\ExternalSources\PoeWiki;

use Lootwright\Application\ExternalSources\Ports\ItemMetadataProvider;
use Lootwright\Application\ExternalSources\Ports\ModifierMetadataProvider;

/** Deliberately has no HTTP client: activation needs a separate licensing and funding review. */
final class DisabledPoeWikiCargoAdapter implements ItemMetadataProvider, ModifierMetadataProvider {}

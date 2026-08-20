<?php
namespace App\Modules\ExternalSources;
use Lootwright\Application\ExternalSources\Ports\OfficialTradeSearchProvider;
final class DisabledOfficialTradeSearchProvider implements OfficialTradeSearchProvider { public function unavailableReason(): string { return 'No documented Trade Search API is currently available to Lootwright.'; } }

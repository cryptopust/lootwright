<?php

namespace App\Modules\ExternalSources\Ggg;

use App\Modules\ExternalSources\DisabledSourceAdapter;
use Lootwright\Application\ExternalSources\Ports\OfficialGggApiSourceAdapter;
use Lootwright\Domain\Shared\Game\GameEdition;

final class DisabledOfficialGggApiSourceAdapter extends DisabledSourceAdapter implements OfficialGggApiSourceAdapter
{
    public function __construct()
    {
        parent::__construct(
            'GGG-DOCUMENTED-API',
            '2026-08-14',
            [GameEdition::Poe1, GameEdition::Poe2],
            ['documented_api_contract'],
            'application_registration_and_exact_scopes_not_approved',
        );
    }
}

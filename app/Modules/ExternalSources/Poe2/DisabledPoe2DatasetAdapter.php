<?php

namespace App\Modules\ExternalSources\Poe2;

use App\Modules\ExternalSources\DisabledSourceAdapter;
use Lootwright\Application\ExternalSources\Ports\ApprovedPoe2DatasetAdapter;
use Lootwright\Domain\Shared\Game\GameEdition;

final class DisabledPoe2DatasetAdapter extends DisabledSourceAdapter implements ApprovedPoe2DatasetAdapter
{
    public function __construct()
    {
        parent::__construct(
            'POE2-DATASET-CANDIDATE',
            'unavailable',
            [GameEdition::Poe2],
            ['approved_dataset_contract'],
            'no_approved_poe2_canonical_dataset',
        );
    }
}

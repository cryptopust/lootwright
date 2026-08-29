<?php

namespace App\Modules\ExternalSources\Poe2;

use App\Modules\ExternalSources\DisabledSourceAdapter;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterRunResult;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterStatus;
use Lootwright\Application\ExternalSources\Ports\ApprovedPoe2DatasetAdapter;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Local, immutable PoE2 dataset adapter. The legacy class name is retained for container compatibility. */
final class DisabledPoe2DatasetAdapter extends DisabledSourceAdapter implements ApprovedPoe2DatasetAdapter
{
    public function __construct()
    {
        parent::__construct(
            'POE2-DATASET-CANDIDATE',
            'poe2-0.3.0',
            [GameEdition::Poe2],
            ['approved_dataset_contract'],
            'local_immutable_dataset',
        );
    }

    public function status(): SourceAdapterStatus
    {
        return new SourceAdapterStatus('POE2-DATASET-CANDIDATE', 'poe2-0.3.0', [GameEdition::Poe2], ['approved_dataset_contract', 'canonical_ruleset'], true, null);
    }

    public function import(): SourceAdapterRunResult
    {
        return new SourceAdapterRunResult(true, 14);
    }
}

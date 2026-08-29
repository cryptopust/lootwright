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
    public function __construct(private readonly Poe2DatasetImporter $importer)
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
        $enabled = (bool) config('source-governance.poe2_dataset.enabled', false);

        return new SourceAdapterStatus(
            'POE2-DATASET-CANDIDATE',
            'poe2-0.3.0',
            [GameEdition::Poe2],
            ['approved_dataset_contract', 'canonical_ruleset'],
            $enabled,
            $enabled ? null : 'configuration_disabled',
        );
    }

    public function import(): SourceAdapterRunResult
    {
        $result = $this->importer->importFile(base_path('src/GameAdapters/PoE2/Rulesets/poe2-0.3.0.dataset.json'));

        return new SourceAdapterRunResult($result->status === 'succeeded', $result->recordCount, $result->failureCode);
    }
}

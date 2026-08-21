<?php

namespace App\Modules\ExternalSources\PoeNinja;

use App\Modules\ExternalSources\DatabaseSourceRegistry;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterRunResult;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterStatus;
use Lootwright\Application\ExternalSources\Ports\PoeNinjaCompatibleSourceAdapter;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class PoeNinjaSourceAdapter implements PoeNinjaCompatibleSourceAdapter
{
    public const SOURCE_CODE = 'POENINJA-ECONOMY-001';

    public function __construct(
        private PoeNinjaSyncService $sync,
        private DatabaseSourceRegistry $registry,
        private PoeNinjaPolicyGate $fetchPolicy,
        private SourceGovernancePolicy $importPolicy,
    ) {}

    public function status(): SourceAdapterStatus
    {
        $record = $this->registry->find(self::SOURCE_CODE);
        $operational = $record?->enabled === true
            && trim((string) config('external-sources.poe_ninja.contact')) !== ''
            && $this->fetchPolicy->permits('poe_ninja.economy.leagues.fetch')
            && $this->importPolicy->permitsImport(
                self::SOURCE_CODE,
                'economy-v1',
                'poeninja.economy.snapshot.import',
                ['operator_contact_configured', 'exact_endpoint_allowlist', 'normalized_snapshot_only'],
            );

        return new SourceAdapterStatus(
            self::SOURCE_CODE,
            'economy-v1',
            [GameEdition::Poe1],
            ['documented_economy_api', 'normalized_market_context'],
            $operational,
            $operational ? null : ($record?->disabledReason ?: 'policy_contact_or_configuration_denied'),
        );
    }

    public function import(): SourceAdapterRunResult
    {
        $status = $this->status();
        if (! $status->operational) {
            return new SourceAdapterRunResult(false, 0, $status->disabledReason ?? 'source_disabled');
        }

        $result = $this->sync->sync();

        return new SourceAdapterRunResult($result->success, $result->quoteCount, $result->failureCode);
    }
}

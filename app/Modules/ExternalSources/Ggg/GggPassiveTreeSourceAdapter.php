<?php

namespace App\Modules\ExternalSources\Ggg;

use App\Modules\ExternalSources\DatabaseSourceRegistry;
use App\Modules\Rulesets\PassiveTree\GggPassiveTreeImporter;
use App\Modules\Rulesets\PassiveTree\GggPassiveTreeUrl;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterRunResult;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterStatus;
use Lootwright\Application\ExternalSources\Ports\ApprovedPoe1DatasetAdapter;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class GggPassiveTreeSourceAdapter implements ApprovedPoe1DatasetAdapter
{
    public const SOURCE_CODE = 'GGG-POE1-SKILLTREE-001';

    public function __construct(
        private GggPassiveTreeImporter $importer,
        private DatabaseSourceRegistry $registry,
        private SourceGovernancePolicy $policy,
    ) {}

    public function status(): SourceAdapterStatus
    {
        $record = $this->registry->find(self::SOURCE_CODE);
        $revision = array_key_first((array) config('source-governance.ggg_passive_tree.approved_revisions'));
        $conditions = ['checksum_verified', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope'];
        $operational = $record?->enabled === true
            && is_string($revision)
            && trim((string) config('source-governance.ggg_passive_tree.contact')) !== ''
            && $this->policy->permitsImport(self::SOURCE_CODE, $revision, 'ggg.poe1.skilltree.snapshot.import', $conditions);

        return new SourceAdapterStatus(
            self::SOURCE_CODE,
            is_string($revision) ? $revision : 'unavailable',
            [GameEdition::Poe1],
            ['operator_import', 'immutable_snapshot', 'canonical_ruleset'],
            $operational,
            $operational ? null : ($record?->disabledReason ?: 'policy_contact_or_revision_not_approved'),
        );
    }

    public function import(): SourceAdapterRunResult
    {
        $status = $this->status();
        if (! $status->operational) {
            return new SourceAdapterRunResult(false, 0, $status->disabledReason ?? 'source_disabled');
        }

        $result = $this->importer->importUrl(GggPassiveTreeUrl::forRevision($status->sourceVersion), false, false);

        return new SourceAdapterRunResult(
            in_array($result->status, ['succeeded', 'validated'], true),
            $result->classCount + $result->nodeCount,
            $result->failureCode,
        );
    }
}

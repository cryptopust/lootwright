<?php

namespace App\Modules\ExternalSources;

use App\Modules\ExternalSources\Ggg\DisabledOfficialGggApiSourceAdapter;
use App\Modules\ExternalSources\Ggg\GggPassiveTreeSourceAdapter;
use App\Modules\ExternalSources\Poe2\DisabledPoe2DatasetAdapter;
use App\Modules\ExternalSources\PoeNinja\PoeNinjaSourceAdapter;
use App\Modules\ExternalSources\PoeWiki\DisabledPoeWikiCargoAdapter;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapter;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class FixedExternalSourceAdapterCatalog implements ExternalSourceAdapterCatalog
{
    /** @var list<ExternalSourceAdapter> */
    private array $adapters;

    public function __construct(
        GggPassiveTreeSourceAdapter $passiveTree,
        PoeNinjaSourceAdapter $poeNinja,
        DisabledOfficialGggApiSourceAdapter $gggApi,
        DisabledPoeWikiCargoAdapter $poeWiki,
        DisabledPoe2DatasetAdapter $poe2,
    ) {
        $this->adapters = [
            $passiveTree,
            $poeNinja,
            $gggApi,
            $poeWiki,
            $poe2,
            new DisabledSourceAdapter('GGG-POE1-ATLASTREE-001', '1.0.0', [GameEdition::Poe1], ['approved_dataset_contract'], 'outside_poe1_mvp'),
            new DisabledSourceAdapter('REPOE-CANDIDATE', 'unreviewed-2026-08-14', [GameEdition::Poe1], ['dataset_contract'], 'policy_prohibited'),
            new DisabledSourceAdapter('POE-DB-CANDIDATE', 'candidate-2026-08-25', [GameEdition::Poe1, GameEdition::Poe2], ['reference_only'], 'permission_and_redistribution_review_required'),
            new DisabledSourceAdapter('CRAFT-OF-EXILE-CANDIDATE', 'candidate-2026-08-25', [GameEdition::Poe1, GameEdition::Poe2], ['reference_only'], 'scraping_and_redistribution_prohibited'),
            new DisabledSourceAdapter('POE-TRADE-VOCABULARY-CANDIDATE', '2026-08-25', [GameEdition::Poe1, GameEdition::Poe2], ['vocabulary_candidate'], 'undocumented_trade_paths_prohibited'),
        ];
    }

    public function all(): array
    {
        return $this->adapters;
    }

    public function find(string $sourceCode): ?ExternalSourceAdapter
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->status()->sourceCode === $sourceCode) {
                return $adapter;
            }
        }

        return null;
    }
}

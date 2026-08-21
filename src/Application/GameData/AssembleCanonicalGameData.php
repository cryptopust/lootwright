<?php

namespace Lootwright\Application\GameData;

use DomainException;
use Lootwright\Application\GameData\DTO\CanonicalDataConflict;
use Lootwright\Application\GameData\DTO\NormalizedGameDataRecord;
use Lootwright\Application\GameData\DTO\NormalizedGameDataset;
use Lootwright\Application\GameData\DTO\SourceAuthorityCandidate;
use Lootwright\Application\GameData\Ports\CanonicalDataConflictRecorder;
use Lootwright\Application\GameData\Ports\SourceAuthorityRegistry;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class AssembleCanonicalGameData
{
    public function __construct(
        private SourceAuthorityResolver $resolver,
        private SourceAuthorityRegistry $authorities,
        private CanonicalDataConflictRecorder $conflicts,
    ) {}

    /**
     * @param  list<NormalizedGameDataset>  $datasets
     * @return list<NormalizedGameDataRecord>
     */
    public function assemble(GameEdition $edition, array $datasets, ?string $rulesetVersionId = null): array
    {
        $groups = [];
        foreach ($datasets as $dataset) {
            if ($dataset->edition !== $edition) {
                throw new DomainException('Canonical assembly rejected a cross-edition dataset.');
            }
            foreach ($dataset->records as $record) {
                $tier = $this->authorities->tier($edition, $record->category, $record->provenance->sourceCode);
                if ($tier === null) {
                    throw new DomainException('Canonical assembly rejected a source without category authority.');
                }
                $groups[$record->category->value."\0".$record->externalId][] = new SourceAuthorityCandidate($record, $tier);
            }
        }

        ksort($groups, SORT_STRING);
        $selected = [];
        foreach ($groups as $candidates) {
            $resolution = $this->resolver->resolve($candidates);
            if ($resolution->conflict) {
                $left = $resolution->orderedCandidates[0];
                foreach (array_slice($resolution->orderedCandidates, 1) as $right) {
                    if (! hash_equals($left->record->checksumSha256, $right->record->checksumSha256)) {
                        $this->conflicts->record(new CanonicalDataConflict(
                            $edition,
                            $left->record->category,
                            $left->record->externalId,
                            $left,
                            $right,
                            $resolution->reason,
                        ), $rulesetVersionId);
                    }
                }

                continue;
            }
            if ($resolution->selected !== null) {
                $selected[] = $resolution->selected;
            }
        }

        return $selected;
    }
}

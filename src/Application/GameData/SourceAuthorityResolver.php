<?php

namespace Lootwright\Application\GameData;

use InvalidArgumentException;
use Lootwright\Application\GameData\DTO\SourceAuthorityCandidate;
use Lootwright\Application\GameData\DTO\SourceAuthorityResolution;

final readonly class SourceAuthorityResolver
{
    /** @param array<string, list<string>> $precedenceByCategory */
    public function __construct(private array $precedenceByCategory) {}

    /** @param list<SourceAuthorityCandidate> $candidates */
    public function resolve(array $candidates): SourceAuthorityResolution
    {
        if ($candidates === []) {
            return new SourceAuthorityResolution(null, [], false, 'no_candidates');
        }
        $first = $candidates[0]->record;
        foreach ($candidates as $candidate) {
            if ($candidate->record->edition !== $first->edition
                || $candidate->record->category !== $first->category
                || $candidate->record->externalId !== $first->externalId
            ) {
                throw new InvalidArgumentException('Authority candidates must describe one edition-scoped canonical identity.');
            }
        }
        $precedence = $this->precedenceByCategory[$first->category->value]
            ?? ['official_structured', 'approved_upstream', 'trusted_community', 'derived', 'heuristic'];
        usort($candidates, static function (SourceAuthorityCandidate $left, SourceAuthorityCandidate $right) use ($precedence): int {
            $leftRank = array_search($left->authorityTier, $precedence, true);
            $rightRank = array_search($right->authorityTier, $precedence, true);

            return [is_int($leftRank) ? $leftRank : PHP_INT_MAX, $left->record->provenance->sourceCode]
                <=> [is_int($rightRank) ? $rightRank : PHP_INT_MAX, $right->record->provenance->sourceCode];
        });
        $checksums = array_values(array_unique(array_map(
            static fn (SourceAuthorityCandidate $candidate): string => $candidate->record->factChecksumSha256(),
            $candidates,
        )));
        if (count($checksums) > 1) {
            return new SourceAuthorityResolution(null, $candidates, true, 'contradictory_source_facts');
        }

        return new SourceAuthorityResolution($candidates[0]->record, $candidates, false, 'highest_authority_consensus');
    }
}

<?php

namespace Lootwright\Application\Market;

use DateTimeImmutable;
use Lootwright\Domain\Recommendations\MarketEvidenceFreshness;
use Lootwright\Domain\Recommendations\MarketPriceEvidence;
use Lootwright\Domain\Recommendations\UpgradeCandidate;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

/**
 * Adapts an approved local market provider into optional recommendation
 * evidence. It never creates or changes deterministic findings.
 */
final readonly class RepositoryMarketEvidenceResolver implements MarketEvidenceResolver
{
    public function __construct(private CachedMarketProvider $provider) {}

    public function resolve(UpgradeCandidate $candidate): ?MarketPriceEvidence
    {
        if ($candidate->targetSlot === null || $candidate->tradeRequirements === []) {
            return null;
        }

        $externalIds = array_values(array_filter(array_map(
            static fn (array $requirement): ?string => is_string($requirement['market_external_id'] ?? null) ? $requirement['market_external_id'] : null,
            $candidate->tradeRequirements,
        ), static fn (?string $id): bool => $id !== null));
        $league = null;
        $category = null;
        foreach ($candidate->tradeRequirements as $requirement) {
            if (is_string($requirement['market_league'] ?? null)) {
                $league = $requirement['market_league'];
            }
            if (is_string($requirement['market_category'] ?? null)) {
                $category = $requirement['market_category'];
            }
        }
        if ($externalIds === [] || $league === null || $category === null) {
            return null;
        }

        $request = new TradeSearchRequest($candidate->gameEdition, $league, [
            'economy_category' => $category,
            'external_ids' => $externalIds,
        ]);
        $estimate = $this->provider->estimate($request, $candidate->id, new DateTimeImmutable('now'));
        if (! $estimate->isCurrent() || $estimate->observation === null) {
            return null;
        }
        $observation = $estimate->observation;

        return new MarketPriceEvidence(
            $observation->median,
            $observation->source,
            $observation->sourceVersion,
            $observation->observedAt,
            $observation->expiresAt,
            hash('sha256', CanonicalJson::encode($observation)),
            MarketEvidenceFreshness::Fresh,
            true,
            $observation->edition,
            $observation->league,
            $observation->sampleSize,
            $observation->listingCount,
            $observation->confidenceBasisPoints,
            $observation->liquidityBasisPoints,
            $observation->p25,
            $observation->p75,
            $observation->p90,
            $observation->outliersRejected,
        );
    }
}

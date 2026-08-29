<?php

namespace App\Modules\Market;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\Market\Ports\MarketObservationRepository;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Reads only approved, fresh normalized poe.ninja snapshot rows. */
final class PostgresPoeNinjaObservationRepository implements MarketObservationRepository
{
    /** @param array<string,mixed> $filters
     *  @return list<array{price:string,currency:string,source:string,source_version:string,observed_at:\DateTimeImmutable,expires_at:\DateTimeImmutable,listing_count:int}> */
    public function prices(GameEdition $edition, string $league, array $filters): array
    {
        if ($edition !== GameEdition::Poe1 || ! (bool) config('external-sources.poe_ninja.enabled')) {
            return [];
        }
        $category = $filters['economy_category'] ?? null;
        $externalIds = $filters['external_ids'] ?? [];
        if (! is_string($category) || ! is_array($externalIds) || $externalIds === [] || count($externalIds) > 50) {
            return [];
        }
        foreach ($externalIds as $externalId) {
            if (! is_string($externalId) || trim($externalId) === '' || mb_strlen($externalId) > 255) {
                return [];
            }
        }
        $now = CarbonImmutable::now('UTC');
        $rows = DB::table('economy_quotes as q')
            ->join('source_import_reports as r', 'r.import_run_id', '=', 'q.source_sync_run_id')
            ->where('q.source_key', 'POENINJA-ECONOMY-001')
            ->where('q.game_edition', GameEdition::Poe1->value)
            ->where('q.league', $league)
            ->where('q.category', $category)
            ->where('r.status', 'approved')
            ->where('q.expires_at', '>', $now)
            ->whereIn('q.external_id', array_values($externalIds))
            ->orderBy('q.normalized_value')
            ->limit(200)
            ->get(['q.normalized_value', 'q.primary_currency', 'q.source_key', 'q.source_version', 'q.fetched_at', 'q.expires_at', 'q.confidence_metadata']);

        return array_values($rows->map(static function (object $row): array {
            $metadata = is_string($row->confidence_metadata) ? json_decode($row->confidence_metadata, true) : (array) $row->confidence_metadata;

            return [
                'price' => rtrim(rtrim(number_format((float) $row->normalized_value, 4, '.', ''), '0'), '.'),
                'currency' => self::currency((string) $row->primary_currency),
                'source' => (string) $row->source_key,
                'source_version' => (string) $row->source_version,
                'observed_at' => new \DateTimeImmutable((string) $row->fetched_at),
                'expires_at' => new \DateTimeImmutable((string) $row->expires_at),
                'listing_count' => max(0, (int) ($metadata['listing_count'] ?? $metadata['count'] ?? 0)),
            ];
        })->all());
    }

    private static function currency(string $currency): string
    {
        return match (strtolower(trim($currency))) {
            'chaos orb' => 'CHAOS',
            'divine orb' => 'DIVINE',
            default => 'UNKNOWN',
        };
    }
}

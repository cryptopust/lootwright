<?php

namespace App\Modules\Rulesets;

use Illuminate\Support\Facades\DB;
use Lootwright\Application\GameData\DTO\DataCoverageEntry;
use Lootwright\Application\GameData\Ports\DataCoverageReporter;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Shared\Game\GameEdition;

final class PostgresDataCoverageReporter implements DataCoverageReporter
{
    public function forEdition(GameEdition $edition): array
    {
        $ruleset = DB::table('ruleset_activations as activations')
            ->join('ruleset_versions as rulesets', 'rulesets.id', '=', 'activations.ruleset_version_id')
            ->where('activations.game_edition', $edition->value)
            ->orderByDesc('activations.activated_at')
            ->first(['rulesets.id', 'rulesets.version', 'rulesets.canonical_payload']);
        $categories = (array) config('game-data.coverage_categories', []);
        if ($ruleset === null) {
            return array_values(array_map(static fn (string $category): DataCoverageEntry => new DataCoverageEntry(
                $edition,
                $category,
                null,
                0,
                null,
                null,
                'unavailable', null, null, null, null, 0, null, 'unknown',
            ), $categories));
        }

        $payload = json_decode((string) $ruleset->canonical_payload, true, flags: JSON_THROW_ON_ERROR);
        $expectations = is_array($payload['data_quality']['expected_counts'] ?? null)
            ? $payload['data_quality']['expected_counts']
            : [];
        $counts = DB::table('canonical_game_data')
            ->where('ruleset_version_id', $ruleset->id)
            ->select(['entity_type', DB::raw('count(*) as aggregate')])
            ->groupBy('entity_type')
            ->pluck('aggregate', 'entity_type');

        return array_values(array_map(function (string $category) use ($counts, $edition, $expectations, $ruleset): DataCoverageEntry {
            $observed = $this->observed($category, $counts->all());
            $expected = is_int($expectations[$category] ?? null) && $expectations[$category] > 0
                ? $expectations[$category]
                : null;
            $basisPoints = $expected === null ? null : min(10_000, intdiv($observed * 10_000, $expected));
            $status = match (true) {
                $observed === 0 => 'missing',
                $expected === null => 'unknown_completeness',
                $observed < $expected => 'partial',
                default => 'complete',
            };

            $active = $observed;
            $known = is_int($expected) ? $expected : null;

            return new DataCoverageEntry(
                $edition,
                $category,
                (string) $ruleset->version,
                $observed,
                $expected,
                $basisPoints,
                $status,
                $known,
                $observed,
                $observed,
                $observed,
                $active,
                $known === null ? null : max(0, $known - $active),
                $known === null ? 'unknown' : ($observed >= $known ? 'known_complete' : 'known_partial'),
            );
        }, $categories));
    }

    /** @param array<string, int|string> $counts */
    private function observed(string $category, array $counts): int
    {
        $value = static fn (CanonicalEntityType $type): int => (int) ($counts[$type->value] ?? 0);

        return match ($category) {
            CanonicalEntityType::ItemBase->value => $value(CanonicalEntityType::ItemBase) + $value(CanonicalEntityType::UniqueItem),
            CanonicalEntityType::PassiveNode->value => $value(CanonicalEntityType::PassiveNode) + $value(CanonicalEntityType::Keystone),
            default => (int) ($counts[$category] ?? 0),
        };
    }
}

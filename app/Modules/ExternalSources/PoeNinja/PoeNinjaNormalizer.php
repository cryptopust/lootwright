<?php

namespace App\Modules\ExternalSources\PoeNinja;

use Carbon\CarbonImmutable;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;
use Lootwright\Application\ExternalSources\DTO\EconomyLeague;
use Lootwright\Application\ExternalSources\DTO\EconomyQuote;
use Lootwright\Application\ExternalSources\DTO\EconomySourceVersion;
use Lootwright\Application\ExternalSources\DTO\PriceEvidence;
use Lootwright\Application\ExternalSources\DTO\SourceFreshness;
use Lootwright\Domain\Shared\Game\GameEdition;

final class PoeNinjaNormalizer
{
    /** @return list<EconomyLeague> */
    public function leagues(string $body): array
    {
        $decoded = $this->json($body);
        $rows = is_array($decoded['leagues'] ?? null) ? $decoded['leagues'] : $decoded;
        if (! array_is_list($rows)) {
            throw new PoeNinjaFailure('unexpected_leagues_schema', false);
        }
        $result = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['name'] ?? null) || trim($row['name']) === '') {
                throw new PoeNinjaFailure('unexpected_leagues_schema', false);
            }
            $result[] = new EconomyLeague(trim($row['name']), (bool) ($row['isActive'] ?? true));
        }

        return $result;
    }

    /** @return list<EconomyQuote> */
    public function quotes(string $body, string $league, EconomyCategory $category, CarbonImmutable $fetchedAt, CarbonImmutable $expiresAt): array
    {
        $decoded = $this->json($body);
        $lines = $decoded['lines'] ?? null;
        if (! is_array($lines) || ! array_is_list($lines)) {
            throw new PoeNinjaFailure('unexpected_overview_schema', false);
        }
        $checksum = hash('sha256', $body);
        $quotes = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                throw new PoeNinjaFailure('unexpected_overview_schema', false);
            }
            $name = $line['currencyTypeName'] ?? $line['name'] ?? null;
            $externalId = $line['detailsId'] ?? $line['currencyTypeName'] ?? $line['name'] ?? null;
            $value = $line['chaosValue'] ?? $line['chaosEquivalent'] ?? null;
            if (! is_string($name) || trim($name) === '' || ! is_string($externalId) || trim($externalId) === '' || (! is_int($value) && ! is_float($value))) {
                throw new PoeNinjaFailure('unexpected_overview_schema', false);
            }
            $quotes[] = new EconomyQuote(new PriceEvidence('POENINJA-ECONOMY-001', new EconomySourceVersion('POENINJA-ECONOMY-001', EconomySourceVersion::POE_NINJA), GameEdition::Poe1, $league, $category, trim($externalId), trim($name), number_format((float) $value, 6, '.', ''), 'Chaos Orb', isset($line['divineValue']) ? 'Divine Orb' : null, $fetchedAt, $expiresAt, SourceFreshness::Fresh, $checksum), ['reported_divine_value' => $line['divineValue'] ?? null]);
        }

        return $quotes;
    }

    /** @return array<string|int, mixed> */
    private function json(string $body): array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new PoeNinjaFailure('malformed_json', false);
        }
        if (! is_array($decoded)) {
            throw new PoeNinjaFailure('unexpected_schema', false);
        }

        return $decoded;
    }
}

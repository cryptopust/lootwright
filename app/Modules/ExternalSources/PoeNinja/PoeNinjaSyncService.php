<?php

namespace App\Modules\ExternalSources\PoeNinja;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;
use Lootwright\Application\ExternalSources\DTO\SourceSyncResult;

final readonly class PoeNinjaSyncService
{
    public function __construct(private PoeNinjaEconomyClient $client, private PoeNinjaNormalizer $normalizer, private PoeNinjaPolicyGate $policy) {}

    public function sync(?string $requestedLeague = null): SourceSyncResult
    {
        if (! (bool) config('external-sources.poe_ninja.enabled')) {
            return new SourceSyncResult(false, 0, 'source_disabled');
        }
        if (! $this->policy->permits('poe_ninja.economy.leagues.fetch')) {
            return new SourceSyncResult(false, 0, 'policy_denied');
        }
        $lock = Cache::lock('external-source:poe-ninja-sync', 900);
        if (! $lock->get()) {
            return new SourceSyncResult(false, 0, 'overlapping_sync');
        }
        try {
            $leaguesResponse = $this->client->getLeagues();
            if ($leaguesResponse['status'] === 304) {
                return new SourceSyncResult(false, 0, 'league_snapshot_missing');
            }
            $leagues = $this->normalizer->leagues($leaguesResponse['body']);
            $league = $requestedLeague ?? ($leagues[0]->name ?? null);
            if (! is_string($league) || ! in_array($league, array_map(static fn ($item): string => $item->name, $leagues), true)) {
                return new SourceSyncResult(false, 0, 'invalid_league');
            }
            $count = 0;
            foreach ([...EconomyCategory::exchange(), ...EconomyCategory::stash()] as $category) {
                $operation = $category->isExchange() ? 'poe_ninja.economy.exchange.fetch' : 'poe_ninja.economy.stash_item.fetch';
                if (! $this->policy->permits($operation)) {
                    return new SourceSyncResult(false, $count, 'policy_denied');
                }
                $count += $this->syncCategory($league, $category, $operation);
            }

            return new SourceSyncResult(true, $count);
        } catch (PoeNinjaFailure $failure) {
            return new SourceSyncResult(false, 0, $failure->failureCode);
        } finally {
            $lock->release();
        }
    }

    private function syncCategory(string $league, EconomyCategory $category, string $operation): int
    {
        $previous = DB::table('external_source_sync_runs')->where(['source_key' => 'POENINJA-ECONOMY-001', 'operation' => $operation, 'league' => $league, 'category' => $category->value, 'status' => 'success'])->latest('completed_at')->first(['etag', 'last_modified']);
        $started = CarbonImmutable::now('UTC');
        $response = $this->client->getOverview($league, $category, $previous?->etag, $previous?->last_modified);
        if ($response['status'] === 304) {
            return 0;
        } // preserves current valid snapshot
        $expires = $this->expiresAt($response['headers'], $started);
        $quotes = $this->normalizer->quotes($response['body'], $league, $category, $started, $expires);
        DB::transaction(function () use ($league, $category, $operation, $response, $started, $expires, $quotes): void {
            $runId = (string) Str::uuid7();
            DB::table('external_source_sync_runs')->insert(['id' => $runId, 'source_key' => 'POENINJA-ECONOMY-001', 'source_version' => 'economy-v1', 'operation' => $operation, 'game_edition' => 'poe1', 'league' => $league, 'category' => $category->value, 'status' => 'success', 'http_status' => $response['status'], 'etag' => $response['headers']['etag'] ?: null, 'last_modified' => $response['headers']['last_modified'] ?: null, 'response_checksum_sha256' => hash('sha256', $response['body']), 'started_at' => $started, 'completed_at' => $started, 'fetched_at' => $started, 'expires_at' => $expires, 'created_at' => $started, 'updated_at' => $started]);
            foreach ($quotes as $quote) {
                $evidence = $quote->evidence;
                DB::table('economy_quotes')->updateOrInsert(['source_key' => $evidence->sourceKey, 'source_version' => $evidence->sourceVersion->value, 'game_edition' => $evidence->gameEdition->value, 'league' => $evidence->league, 'category' => $evidence->category->value, 'external_id' => $evidence->externalId], ['id' => (string) Str::uuid7(), 'source_sync_run_id' => $runId, 'normalized_name' => $evidence->normalizedName, 'primary_currency' => $evidence->primaryCurrency, 'secondary_currency' => $evidence->secondaryCurrency, 'normalized_value' => $evidence->normalizedValue, 'confidence_metadata' => json_encode($quote->confidenceMetadata, JSON_THROW_ON_ERROR), 'fetched_at' => $evidence->fetchedAt, 'expires_at' => $evidence->expiresAt, 'created_at' => $started, 'updated_at' => $started]);
            }
        });

        return count($quotes);
    }

    /** @param array<string, string> $headers */
    private function expiresAt(array $headers, CarbonImmutable $fetchedAt): CarbonImmutable
    {
        if (preg_match('/max-age=(\d+)/i', $headers['cache_control'], $matches) === 1) {
            return $fetchedAt->addSeconds(max(300, min((int) $matches[1], (int) config('external-sources.poe_ninja.refresh_seconds'))));
        }

        return $fetchedAt->addSeconds((int) config('external-sources.poe_ninja.refresh_seconds'));
    }
}

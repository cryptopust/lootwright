<?php

namespace App\Modules\ExternalSources\PoeNinja;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;
use Lootwright\Application\ExternalSources\DTO\EconomyQuote;
use Lootwright\Application\ExternalSources\DTO\SourceSyncResult;
use Lootwright\Application\ExternalSources\DTO\StagedSourceRecord;
use Lootwright\Application\ExternalSources\Ports\SourceImportStaging;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\Services\GovernedRulesetLifecycle;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class PoeNinjaSyncService
{
    private const SOURCE_CODE = 'POENINJA-ECONOMY-001';

    private const SOURCE_VERSION = 'economy-v1';

    private const IMPORT_OPERATION = 'poeninja.economy.snapshot.import';

    private const IMPORT_CONDITIONS = ['operator_contact_configured', 'exact_endpoint_allowlist', 'normalized_snapshot_only'];

    public function __construct(
        private PoeNinjaEconomyClient $client,
        private PoeNinjaNormalizer $normalizer,
        private PoeNinjaPolicyGate $policy,
        private SourceImportStaging $staging,
        private GovernedRulesetLifecycle $lifecycle,
    ) {}

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
        } catch (DomainException) {
            return new SourceSyncResult(false, 0, 'governed_import_failed');
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
        $sourceChecksum = hash('sha256', $response['body']);
        $normalizedQuotes = array_map(fn ($quote): array => $this->normalizedQuote($quote), $quotes);
        $payload = ['game_edition' => 'poe1', 'league' => $league, 'category' => $category->value, 'quotes' => $normalizedQuotes];
        $normalizedChecksum = hash('sha256', CanonicalJson::encode($payload));
        $records = array_map(static function (array $quote): StagedSourceRecord {
            $encoded = CanonicalJson::encode($quote);

            return new StagedSourceRecord((string) $quote['external_id'], hash('sha256', $encoded), 'staged', null, $quote);
        }, $normalizedQuotes);
        $report = $this->staging->stage(self::SOURCE_CODE, self::SOURCE_VERSION, self::IMPORT_OPERATION, GameEdition::Poe1, $sourceChecksum, $normalizedChecksum, $league.':'.$category->value, $records, self::IMPORT_CONDITIONS);

        if ($report->status === 'staged') {
            $snapshot = $this->lifecycle->import(new SourceSnapshotImport(
                self::SOURCE_CODE,
                self::SOURCE_VERSION,
                GameEdition::Poe1,
                self::IMPORT_OPERATION,
                PoeNinjaEndpoint::overview($league, $category),
                null,
                new DateTimeImmutable($started->toIso8601String()),
                $normalizedChecksum,
                'application/json',
                'LicenseRef-poe-ninja-public-economy-api',
                'economy-normalized-1.0.0',
                $payload,
                $sourceChecksum,
                $report->importRunId,
            ), self::IMPORT_CONDITIONS);
            if ($snapshot->snapshotId === null || $snapshot->status !== 'succeeded') {
                $this->staging->reject($report->id, 'snapshot_import_failed');
                throw new DomainException('The normalized poe.ninja snapshot could not be approved.');
            }
            $this->staging->approve($report->id, $snapshot->snapshotId);
        } elseif ($report->status !== 'approved') {
            throw new DomainException('The normalized poe.ninja snapshot is not eligible for approval.');
        }

        DB::transaction(function () use ($league, $category, $operation, $response, $started, $expires, $quotes, $sourceChecksum, $report): void {
            $runId = $report->importRunId;
            DB::table('external_source_sync_runs')->where('id', $runId)->update(['operation' => $operation, 'league' => $league, 'category' => $category->value, 'status' => 'success', 'http_status' => $response['status'], 'etag' => $response['headers']['etag'] ?: null, 'last_modified' => $response['headers']['last_modified'] ?: null, 'response_checksum_sha256' => $sourceChecksum, 'completed_at' => $started, 'fetched_at' => $started, 'expires_at' => $expires, 'updated_at' => $started]);
            foreach ($quotes as $quote) {
                $evidence = $quote->evidence;
                DB::table('economy_quotes')->updateOrInsert(['source_key' => $evidence->sourceKey, 'source_version' => $evidence->sourceVersion->value, 'game_edition' => $evidence->gameEdition->value, 'league' => $evidence->league, 'category' => $evidence->category->value, 'external_id' => $evidence->externalId], ['id' => (string) Str::uuid7(), 'source_sync_run_id' => $runId, 'normalized_name' => $evidence->normalizedName, 'primary_currency' => $evidence->primaryCurrency, 'secondary_currency' => $evidence->secondaryCurrency, 'normalized_value' => $evidence->normalizedValue, 'confidence_metadata' => json_encode($quote->confidenceMetadata, JSON_THROW_ON_ERROR), 'fetched_at' => $evidence->fetchedAt, 'expires_at' => $evidence->expiresAt, 'created_at' => $started, 'updated_at' => $started]);
            }
        });

        return count($quotes);
    }

    /** @return array<string, mixed> */
    private function normalizedQuote(EconomyQuote $quote): array
    {
        $evidence = $quote->evidence;

        return [
            'source_key' => $evidence->sourceKey,
            'source_version' => $evidence->sourceVersion->value,
            'game_edition' => $evidence->gameEdition->value,
            'league' => $evidence->league,
            'category' => $evidence->category->value,
            'external_id' => $evidence->externalId,
            'normalized_name' => $evidence->normalizedName,
            'normalized_value' => $evidence->normalizedValue,
            'primary_currency' => $evidence->primaryCurrency,
            'secondary_currency' => $evidence->secondaryCurrency,
            'evidence_checksum' => $evidence->checksum,
            'confidence' => $quote->confidenceMetadata,
        ];
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

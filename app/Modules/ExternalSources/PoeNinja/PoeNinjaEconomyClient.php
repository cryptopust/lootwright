<?php

namespace App\Modules\ExternalSources\PoeNinja;

use App\Security\OutboundRequestGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Lootwright\Application\ExternalSources\DTO\EconomyCategory;

final readonly class PoeNinjaEconomyClient
{
    private const MAX_RESPONSE_BYTES = 2_000_000;

    public function __construct(private OutboundRequestGuard $outbound) {}

    /** @return array{status: int, body: string, headers: array<string, string>} */
    public function getLeagues(?string $etag = null, ?string $lastModified = null): array
    {
        return $this->get('poe_ninja.economy.leagues.fetch', PoeNinjaEndpoint::leagues(), $etag, $lastModified);
    }

    /** @return array{status: int, body: string, headers: array<string, string>} */
    public function getOverview(string $league, EconomyCategory $category, ?string $etag = null, ?string $lastModified = null): array
    {
        $operation = $category->isExchange() ? 'poe_ninja.economy.exchange.fetch' : 'poe_ninja.economy.stash_item.fetch';

        return $this->get($operation, PoeNinjaEndpoint::overview($league, $category), $etag, $lastModified);
    }

    /** @return array{status: int, body: string, headers: array<string, string>} */
    private function get(string $operation, string $url, ?string $etag, ?string $lastModified): array
    {
        PoeNinjaEndpoint::assertAllowed($url);
        if (! (bool) config('external-sources.poe_ninja.enabled') || ! is_string(config('external-sources.poe_ninja.contact')) || trim((string) config('external-sources.poe_ninja.contact')) === '') {
            throw new PoeNinjaFailure('source_disabled_or_contact_missing', false);
        }

        // Independently verifies public DNS resolution after endpoint construction.
        $this->outbound->assertAllowed($operation, $url);

        $headers = ['User-Agent' => 'Lootwright/'.config('external-sources.poe_ninja.user_agent_version').' (contact: '.trim((string) config('external-sources.poe_ninja.contact')).')', 'Accept' => 'application/json'];
        if ($etag !== null) { $headers['If-None-Match'] = $etag; }
        if ($lastModified !== null) { $headers['If-Modified-Since'] = $lastModified; }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $response = Http::withHeaders($headers)->connectTimeout((int) config('external-sources.poe_ninja.connect_timeout_seconds'))->timeout((int) config('external-sources.poe_ninja.request_timeout_seconds'))->withOptions(['allow_redirects' => false])->get($url);
            } catch (ConnectionException) {
                if ($attempt === 2) { throw new PoeNinjaFailure('connection_or_timeout', true); }
                usleep(random_int(50_000, 150_000) * ($attempt + 1)); continue;
            }

            if ($response->redirect()) { throw new PoeNinjaFailure('redirect_denied', false); }
            if (($response->status() === 429 || $response->serverError()) && $attempt < 2) {
                $retryAfter = max(0, min(5, (int) $response->header('Retry-After', '0')));
                usleep(($retryAfter > 0 ? $retryAfter * 1_000_000 : random_int(50_000, 150_000) * ($attempt + 1))); continue;
            }

            $body = $response->body();
            if (strlen($body) > self::MAX_RESPONSE_BYTES) { throw new PoeNinjaFailure('response_too_large', false); }
            if (! $response->successful() && $response->status() !== 304) { throw new PoeNinjaFailure('http_'.$response->status(), $response->serverError()); }

            return ['status' => $response->status(), 'body' => $body, 'headers' => ['etag' => (string) $response->header('ETag', ''), 'last_modified' => (string) $response->header('Last-Modified', ''), 'cache_control' => (string) $response->header('Cache-Control', ''), 'expires' => (string) $response->header('Expires', '')]];
        }

        throw new PoeNinjaFailure('retry_exhausted', true);
    }
}

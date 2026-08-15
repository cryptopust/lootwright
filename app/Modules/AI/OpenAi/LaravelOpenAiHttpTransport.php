<?php

namespace App\Modules\AI\OpenAi;

use App\Security\OutboundRequestDenied;
use App\Security\OutboundRequestGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Lootwright\Application\AIGateway\Exception\AiProviderFailure;

final readonly class LaravelOpenAiHttpTransport implements OpenAiHttpTransport
{
    public function __construct(
        private string $apiKey,
        private int $timeoutSeconds,
        private int $connectTimeoutSeconds,
        private OutboundRequestGuard $outbound,
    ) {}

    public function postResponses(array $payload): OpenAiHttpResponse
    {
        $started = hrtime(true);
        $url = 'https://api.openai.com/v1/responses';

        try {
            $this->outbound->assertAllowed('openai.responses', $url);
            $response = Http::baseUrl('https://api.openai.com/v1')
                ->withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->withOptions(['allow_redirects' => false])
                ->post('/responses', $payload);
        } catch (OutboundRequestDenied) {
            throw new AiProviderFailure('egress_denied', false);
        } catch (ConnectionException) {
            throw new AiProviderFailure('connection_or_timeout', true);
        }

        $body = $response->json();

        return new OpenAiHttpResponse(
            $response->status(),
            is_array($body) ? $body : [],
            array_map(static fn (array $values): string => implode(',', $values), $response->headers()),
            (int) ceil((hrtime(true) - $started) / 1_000_000),
        );
    }
}

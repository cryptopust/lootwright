<?php

namespace App\Modules\AI\OpenAi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Lootwright\Application\AIGateway\Exception\AiProviderFailure;

final readonly class LaravelOpenAiHttpTransport implements OpenAiHttpTransport
{
    public function __construct(
        private string $apiKey,
        private int $timeoutSeconds,
        private int $connectTimeoutSeconds,
    ) {}

    public function postResponses(array $payload): OpenAiHttpResponse
    {
        $started = hrtime(true);

        try {
            $response = Http::baseUrl('https://api.openai.com/v1')
                ->withToken($this->apiKey)
                ->acceptJson()
                ->asJson()
                ->connectTimeout($this->connectTimeoutSeconds)
                ->timeout($this->timeoutSeconds)
                ->post('/responses', $payload);
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

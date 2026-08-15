<?php

namespace App\Modules\AI\OpenAi;

use Lootwright\Application\AIGateway\DTO\StructuredAiRequest;
use Lootwright\Application\AIGateway\DTO\StructuredAiResponse;
use Lootwright\Application\AIGateway\Exception\AiProviderFailure;
use Lootwright\Application\AIGateway\Ports\StructuredAiProvider;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class OpenAiResponsesProvider implements StructuredAiProvider
{
    public function __construct(
        private OpenAiHttpTransport $transport,
        private int $maxRetries,
        private int $baseDelayMs,
        private int $maxDelayMs,
    ) {}

    public function respond(StructuredAiRequest $request): StructuredAiResponse
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $this->transport->postResponses($this->payload($request));
            } catch (AiProviderFailure $failure) {
                if (! $failure->transient || $attempt >= $this->maxRetries) {
                    throw $failure;
                }

                $this->delay($attempt++, $failure->retryAfterSeconds);

                continue;
            }

            if ($response->status >= 200 && $response->status < 300) {
                return $this->success($response, $request->model);
            }

            $code = $this->errorCode($response);
            $transient = in_array($response->status, [429, 500, 502, 503, 504], true)
                && ! in_array($code, [
                    'organization_spend_limit_exceeded',
                    'project_spend_limit_exceeded',
                    'organization_usage_limit_exceeded',
                    'credit_balance_exhausted',
                ], true);

            if (! $transient || $attempt >= $this->maxRetries) {
                throw new AiProviderFailure($code, $transient, $this->retryAfter($response));
            }

            $this->delay($attempt++, $this->retryAfter($response));
        }
    }

    /** @return array<string, mixed> */
    private function payload(StructuredAiRequest $request): array
    {
        return [
            'model' => $request->model,
            'instructions' => $request->instructions,
            'input' => CanonicalJson::encode($request->input),
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $request->schemaName,
                    'strict' => true,
                    'schema' => $request->schema,
                ],
            ],
            'max_output_tokens' => $request->maxOutputTokens,
            'store' => false,
            'truncation' => 'disabled',
            'safety_identifier' => $request->safetyIdentifier,
            'prompt_cache_key' => $request->promptCacheKey,
        ];
    }

    private function success(OpenAiHttpResponse $response, string $requestedModel): StructuredAiResponse
    {
        $outputText = null;
        $refused = false;

        foreach ($response->body['output'] ?? [] as $item) {
            if (! is_array($item) || ($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (! is_array($content)) {
                    continue;
                }
                if (($content['type'] ?? null) === 'refusal') {
                    $refused = true;
                }
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    $outputText = $content['text'];
                }
            }
        }

        $usage = is_array($response->body['usage'] ?? null) ? $response->body['usage'] : [];
        $details = is_array($usage['input_tokens_details'] ?? null) ? $usage['input_tokens_details'] : [];
        $cached = is_int($details['cached_tokens'] ?? null) ? $details['cached_tokens'] : 0;

        return new StructuredAiResponse(
            'openai',
            is_string($response->body['model'] ?? null) ? $response->body['model'] : $requestedModel,
            $outputText,
            $refused,
            is_int($usage['input_tokens'] ?? null) ? $usage['input_tokens'] : 0,
            $cached,
            is_int($usage['output_tokens'] ?? null) ? $usage['output_tokens'] : 0,
            $response->latencyMs,
            $cached > 0,
        );
    }

    private function errorCode(OpenAiHttpResponse $response): string
    {
        $error = $response->body['error'] ?? null;

        return is_array($error) && is_string($error['code'] ?? null)
            ? $error['code']
            : 'http_'.$response->status;
    }

    private function retryAfter(OpenAiHttpResponse $response): ?int
    {
        foreach ($response->headers as $name => $value) {
            if (strtolower($name) === 'retry-after' && ctype_digit($value)) {
                return min(60, (int) $value);
            }
        }

        return null;
    }

    private function delay(int $attempt, ?int $retryAfterSeconds): void
    {
        if ($retryAfterSeconds !== null) {
            $milliseconds = $retryAfterSeconds * 1000;
        } else {
            $ceiling = min($this->maxDelayMs, $this->baseDelayMs * (2 ** $attempt));
            $milliseconds = $ceiling > 0 ? random_int(0, $ceiling) : 0;
        }

        if ($milliseconds > 0) {
            usleep($milliseconds * 1000);
        }
    }
}

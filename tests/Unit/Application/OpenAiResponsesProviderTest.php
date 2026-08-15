<?php

namespace Tests\Unit\Application;

use App\Modules\AI\OpenAi\OpenAiHttpResponse;
use App\Modules\AI\OpenAi\OpenAiHttpTransport;
use App\Modules\AI\OpenAi\OpenAiResponsesProvider;
use Lootwright\Application\AIGateway\DTO\StructuredAiRequest;
use Lootwright\Application\AIGateway\Exception\AiProviderFailure;
use PHPUnit\Framework\TestCase;

final class OpenAiResponsesProviderTest extends TestCase
{
    public function test_responses_payload_is_stateless_strict_bounded_and_tool_free(): void
    {
        $transport = new RecordingOpenAiTransport([$this->success()]);
        $response = (new OpenAiResponsesProvider($transport, 0, 0, 0))->respond($this->request());
        $payload = $transport->payloads[0];

        self::assertFalse($payload['store']);
        self::assertSame('disabled', $payload['truncation']);
        self::assertSame(120, $payload['max_output_tokens']);
        self::assertTrue($payload['text']['format']['strict']);
        self::assertFalse($payload['text']['format']['schema']['additionalProperties']);
        self::assertArrayNotHasKey('tools', $payload);
        self::assertSame('{"ok":true}', $response->outputJson);
        self::assertSame(4, $response->cachedInputTokens);
    }

    public function test_temporary_rate_limit_retries_but_spend_limit_does_not(): void
    {
        $temporary = new RecordingOpenAiTransport([
            new OpenAiHttpResponse(429, ['error' => ['code' => 'rate_limit_exceeded']], [], 1),
            $this->success(),
        ]);
        (new OpenAiResponsesProvider($temporary, 1, 0, 0))->respond($this->request());
        self::assertCount(2, $temporary->payloads);

        $spend = new RecordingOpenAiTransport([
            new OpenAiHttpResponse(429, ['error' => ['code' => 'project_spend_limit_exceeded']], [], 1),
        ]);
        try {
            (new OpenAiResponsesProvider($spend, 2, 0, 0))->respond($this->request());
            self::fail('Expected the spend limit to be terminal.');
        } catch (AiProviderFailure $failure) {
            self::assertFalse($failure->transient);
            self::assertCount(1, $spend->payloads);
        }
    }

    private function request(): StructuredAiRequest
    {
        return new StructuredAiRequest(
            'intent', 'gpt-5.4-nano', 'test-v1', 'Return the fixture.', ['fixture' => true], 'fixture',
            ['type' => 'object', 'properties' => ['ok' => ['type' => 'boolean']], 'required' => ['ok'], 'additionalProperties' => false],
            120, str_repeat('a', 64), str_repeat('b', 64),
        );
    }

    private function success(): OpenAiHttpResponse
    {
        return new OpenAiHttpResponse(200, [
            'model' => 'gpt-5.4-nano-2026-03-17',
            'output' => [[
                'type' => 'message',
                'content' => [['type' => 'output_text', 'text' => '{"ok":true}']],
            ]],
            'usage' => ['input_tokens' => 10, 'input_tokens_details' => ['cached_tokens' => 4], 'output_tokens' => 3],
        ], [], 7);
    }
}

final class RecordingOpenAiTransport implements OpenAiHttpTransport
{
    /** @var list<array<string, mixed>> */
    public array $payloads = [];

    /** @param list<OpenAiHttpResponse> $responses */
    public function __construct(private array $responses) {}

    public function postResponses(array $payload): OpenAiHttpResponse
    {
        $this->payloads[] = $payload;
        $response = array_shift($this->responses);

        if (! $response instanceof OpenAiHttpResponse) {
            throw new AiProviderFailure('fake_exhausted', false);
        }

        return $response;
    }
}

<?php

namespace App\Modules\AI\OpenAi;

final readonly class OpenAiHttpResponse
{
    /** @param array<string, mixed> $body
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public int $status,
        public array $body,
        public array $headers,
        public int $latencyMs,
    ) {}
}

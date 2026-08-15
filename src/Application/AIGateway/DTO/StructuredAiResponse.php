<?php

namespace Lootwright\Application\AIGateway\DTO;

final readonly class StructuredAiResponse
{
    public function __construct(
        public string $provider,
        public string $model,
        public ?string $outputJson,
        public bool $refused,
        public int $inputTokens,
        public int $cachedInputTokens,
        public int $outputTokens,
        public int $latencyMs,
        public bool $providerCacheHit,
    ) {}
}

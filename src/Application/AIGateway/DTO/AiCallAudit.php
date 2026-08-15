<?php

namespace Lootwright\Application\AIGateway\DTO;

final readonly class AiCallAudit
{
    public function __construct(
        public string $requestHash,
        public string $userHash,
        public string $promptTemplateVersion,
        public string $provider,
        public string $model,
        public string $task,
        public int $inputTokens,
        public int $cachedInputTokens,
        public int $outputTokens,
        public int $latencyMs,
        public string $cacheStatus,
        public string $validationOutcome,
        public int $repairAttempts,
        public int $costMicroUsd,
    ) {}
}

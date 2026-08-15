<?php

namespace Lootwright\Application\AIGateway\DTO;

use InvalidArgumentException;

final readonly class AiGatewayConfiguration
{
    public function __construct(
        public bool $enabled,
        public string $intentModel,
        public string $explanationModel,
        public int $maxInputTokens,
        public int $intentMaxOutputTokens,
        public int $explanationMaxOutputTokens,
        public int $clarificationThresholdBasisPoints,
        public string $promptTemplateVersion,
        public int $inputPriceMicroUsdPerMillion,
        public int $cachedInputPriceMicroUsdPerMillion,
        public int $outputPriceMicroUsdPerMillion,
        public string $cacheHmacKey,
    ) {
        if ($intentModel === '' || $explanationModel === '' || $maxInputTokens < 1
            || $intentMaxOutputTokens < 1 || $explanationMaxOutputTokens < 1
            || $clarificationThresholdBasisPoints < 0 || $clarificationThresholdBasisPoints > 10_000
            || $promptTemplateVersion === '' || $inputPriceMicroUsdPerMillion < 0
            || $cachedInputPriceMicroUsdPerMillion < 0 || $outputPriceMicroUsdPerMillion < 0
            || strlen($cacheHmacKey) < 32
        ) {
            throw new InvalidArgumentException('AI Gateway configuration is incomplete or unsafe.');
        }
    }
}

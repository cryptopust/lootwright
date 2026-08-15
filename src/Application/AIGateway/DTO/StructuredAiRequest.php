<?php

namespace Lootwright\Application\AIGateway\DTO;

final readonly class StructuredAiRequest
{
    /** @param array<string, mixed> $input
     * @param  array<string, mixed>  $schema
     */
    public function __construct(
        public string $task,
        public string $model,
        public string $promptTemplateVersion,
        public string $instructions,
        public array $input,
        public string $schemaName,
        public array $schema,
        public int $maxOutputTokens,
        public string $safetyIdentifier,
        public string $promptCacheKey,
        public bool $repair = false,
    ) {}
}

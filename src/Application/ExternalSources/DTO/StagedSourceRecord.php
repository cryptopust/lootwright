<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class StagedSourceRecord
{
    /** @param array<string, mixed>|null $normalizedPayload */
    public function __construct(
        public string $recordKey,
        public string $checksumSha256,
        public string $status,
        public ?string $rejectionCode,
        public ?array $normalizedPayload,
    ) {}
}

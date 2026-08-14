<?php

namespace Lootwright\Domain\BuildIntake\Import;

use JsonSerializable;

final readonly class ImportProvenance implements JsonSerializable
{
    public function __construct(
        public string $sourceId,
        public string $sourceCommit,
        public string $licenseSha256,
        public string $parserVersion,
    ) {}

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'source_id' => $this->sourceId,
            'source_commit' => $this->sourceCommit,
            'license_sha256' => $this->licenseSha256,
            'parser_version' => $this->parserVersion,
        ];
    }
}

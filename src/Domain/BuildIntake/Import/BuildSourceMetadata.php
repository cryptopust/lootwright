<?php

namespace Lootwright\Domain\BuildIntake\Import;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class BuildSourceMetadata implements JsonSerializable
{
    public function __construct(
        public string $sourceId,
        public BuildInputType $inputType,
        public GameEdition $detectedEdition,
        public string $editionEvidence,
        public string $inputChecksumSha256,
        public string $parserVersion,
    ) {}

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'source_id' => $this->sourceId,
            'input_type' => $this->inputType->value,
            'detected_edition' => $this->detectedEdition->value,
            'edition_evidence' => $this->editionEvidence,
            'input_checksum_sha256' => $this->inputChecksumSha256,
            'parser_version' => $this->parserVersion,
        ];
    }
}

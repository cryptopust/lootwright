<?php

namespace Lootwright\Domain\BuildIntake\Import;

use JsonSerializable;

final readonly class PobImportResult implements JsonSerializable
{
    /**
     * @param  list<ImportWarning>  $warnings
     * @param  list<UnsupportedFeature>  $unsupportedFeatures
     */
    public function __construct(
        public CanonicalImportedBuild $canonicalBuild,
        public array $warnings,
        public array $unsupportedFeatures,
        public string $parserVersion,
        public string $inputChecksumSha256,
        public ImportProvenance $provenance,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'canonical_build' => $this->canonicalBuild,
            'warnings' => $this->warnings,
            'unsupported_features' => $this->unsupportedFeatures,
            'parser_version' => $this->parserVersion,
            'input_checksum_sha256' => $this->inputChecksumSha256,
            'provenance' => $this->provenance,
        ];
    }
}

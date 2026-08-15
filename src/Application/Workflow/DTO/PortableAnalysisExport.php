<?php

namespace Lootwright\Application\Workflow\DTO;

final readonly class PortableAnalysisExport
{
    public function __construct(
        public PortableAnalysisDocument $document,
        public string $canonicalJson,
        public string $sha256,
    ) {}
}

<?php

namespace Lootwright\Domain\BuildIntake\Import;

final readonly class ImportLimits
{
    public function __construct(
        public int $inputBytes = 1_048_576,
        public int $compressedBytes = 786_432,
        public int $xmlBytes = 4_194_304,
        public int $expansionRatio = 64,
        public int $xmlDepth = 32,
        public int $xmlElements = 20_000,
        public int $attributesPerElement = 64,
        public int $passiveNodes = 4_096,
        public int $skills = 256,
        public int $gems = 2_048,
        public int $items = 512,
        public int $textBytes = 65_536,
        public int $processingMilliseconds = 2_000,
    ) {}
}

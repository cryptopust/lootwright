<?php

namespace Lootwright\GameAdapters\Shared\Pob;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;

final readonly class DecodedPobInput
{
    public function __construct(
        public string $xml,
        public string $checksumSha256,
        public BuildInputType $inputType,
    ) {}
}

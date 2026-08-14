<?php

namespace Lootwright\GameAdapters\Shared\Pob;

final readonly class DecodedPobInput
{
    public function __construct(
        public string $xml,
        public string $checksumSha256,
    ) {}
}

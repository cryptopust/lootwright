<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class EconomySourceVersion
{
    public const POE_NINJA = 'economy-v1';

    public function __construct(public string $sourceKey, public string $value) {}
}

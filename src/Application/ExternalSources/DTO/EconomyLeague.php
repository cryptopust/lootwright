<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class EconomyLeague
{
    public function __construct(public string $name, public bool $isActive) {}
}

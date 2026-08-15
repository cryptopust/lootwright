<?php

namespace Lootwright\Application\AIGateway\DTO;

final readonly class AiBudgetReservation
{
    public function __construct(public string $id, public int $reservedMicroUsd) {}
}

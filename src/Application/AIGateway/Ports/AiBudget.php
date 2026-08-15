<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\AiBudgetReservation;
use Lootwright\Application\AIGateway\DTO\AiRequestContext;

interface AiBudget
{
    public function reserve(AiRequestContext $context, int $maximumMicroUsd): ?AiBudgetReservation;

    public function settle(AiBudgetReservation $reservation, int $actualMicroUsd): void;

    public function cancel(AiBudgetReservation $reservation): void;
}

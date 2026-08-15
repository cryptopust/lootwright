<?php

namespace Lootwright\Application\AIGateway\Ports;

use Lootwright\Application\AIGateway\DTO\AiCallAudit;

interface AiTelemetry
{
    public function record(AiCallAudit $audit): void;
}

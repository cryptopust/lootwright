<?php

namespace Lootwright\Application\ExternalSources\Ports;

use Lootwright\Application\ExternalSources\DTO\SourceAdapterRunResult;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterStatus;

interface ExternalSourceAdapter
{
    public function status(): SourceAdapterStatus;

    public function import(): SourceAdapterRunResult;
}

<?php

namespace App\Modules\ExternalSources;

use DomainException;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterRunResult;
use Lootwright\Application\ExternalSources\DTO\SourceAdapterStatus;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapter;
use Lootwright\Domain\Shared\Game\GameEdition;

/** Deliberately has no HTTP dependency. */
class DisabledSourceAdapter implements ExternalSourceAdapter
{
    /**
     * @param  list<GameEdition>  $editions
     * @param  list<string>  $capabilities
     */
    public function __construct(
        private readonly string $sourceCode,
        private readonly string $sourceVersion,
        private readonly array $editions,
        private readonly array $capabilities,
        private readonly string $reason,
    ) {}

    public function status(): SourceAdapterStatus
    {
        return new SourceAdapterStatus(
            $this->sourceCode,
            $this->sourceVersion,
            $this->editions,
            $this->capabilities,
            false,
            $this->reason,
        );
    }

    public function import(): SourceAdapterRunResult
    {
        throw new DomainException('Source adapter disabled: '.$this->reason);
    }
}

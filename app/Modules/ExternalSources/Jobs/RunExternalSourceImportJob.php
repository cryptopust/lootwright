<?php

namespace App\Modules\ExternalSources\Jobs;

use DomainException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;

final class RunExternalSourceImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public readonly string $sourceCode)
    {
        $this->onQueue('source-imports');
    }

    public function handle(ExternalSourceAdapterCatalog $catalog): void
    {
        $adapter = $catalog->find($this->sourceCode);
        if ($adapter === null) {
            throw new DomainException('The fixed source adapter is not registered.');
        }
        $status = $adapter->status();
        if (! $status->operational) {
            throw new DomainException('The fixed source adapter is disabled: '.($status->disabledReason ?? 'policy_denied'));
        }

        $lock = Cache::lock('external-source:manual-import:'.$this->sourceCode, 900);
        if (! $lock->get()) {
            throw new DomainException('A source import is already running.');
        }

        try {
            $result = $adapter->import();
            if (! $result->success) {
                throw new DomainException('The source import failed: '.($result->failureCode ?? 'unknown_failure'));
            }
        } finally {
            $lock->release();
        }
    }
}

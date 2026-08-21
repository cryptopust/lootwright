<?php

namespace App\Modules\ExternalSources\Jobs;

use DomainException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;

final class RunExternalSourceImportJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public int $uniqueFor = 900;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $sourceCode)
    {
        $this->onQueue('source-imports');
    }

    public function handle(ExternalSourceAdapterCatalog $catalog): void
    {
        $correlationId = Context::get('correlation_id');
        $correlationId = is_string($correlationId) && $correlationId !== '' ? $correlationId : (string) Str::uuid7();

        if (preg_match('/^[A-Z][A-Z0-9-]{2,63}$/D', $this->sourceCode) !== 1) {
            Context::scope(fn () => Log::warning('external_source_import_invalid_job_identity', [
                'source_code_hash' => hash('sha256', $this->sourceCode),
            ]), ['correlation_id' => $correlationId, 'workflow_stage' => 'external_source_import']);

            return;
        }

        Context::scope(fn () => $this->handleWithContext($catalog), [
            'correlation_id' => $correlationId,
            'source_code' => $this->sourceCode,
            'workflow_stage' => 'external_source_import',
        ]);
    }

    public function uniqueId(): string
    {
        return 'source-import:'.hash('sha256', $this->sourceCode);
    }

    private function handleWithContext(ExternalSourceAdapterCatalog $catalog): void
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
            Log::notice('external_source_import_duplicate_skipped');

            return;
        }

        try {
            Log::info('external_source_import_started');
            $result = $adapter->import();
            if (! $result->success) {
                $failureCode = $result->failureCode ?? 'unknown_failure';
                $failureCode = preg_match('/^[a-z][a-z0-9_]{1,95}$/D', $failureCode) === 1 ? $failureCode : 'invalid_failure_code';
                Log::warning('external_source_import_failed', ['failure_code' => $failureCode]);

                throw new DomainException('The source import failed.');
            }
            Log::info('external_source_import_finished', ['records_imported' => $result->recordsImported]);
        } finally {
            $lock->release();
        }
    }
}

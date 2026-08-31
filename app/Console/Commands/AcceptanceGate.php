<?php

namespace App\Console\Commands;

use App\Modules\Analysis\Infrastructure\ProductionEditionDeterministicAnalysisEngine;
use App\Modules\ExternalSources\FixedExternalSourceAdapterCatalog;
use App\Modules\AI\OpenAi\OpenAiResponsesProvider;
use App\Modules\Release\RuntimeMarker;
use Illuminate\Console\Command;
use Lootwright\Application\AIGateway\Ports\StructuredAiProvider;
use Lootwright\Application\ExternalSources\Ports\ExternalSourceAdapterCatalog;
use Lootwright\Application\Workflow\Ports\DeterministicAnalysisEngine;

/** Fail-closed entry point for operator-run live acceptance. */
final class AcceptanceGate extends Command
{
    protected $signature = 'acceptance:gate {--edition=all} {--report= : Output report path}';

    protected $description = 'Validate that a live acceptance run is safe to execute';

    public function handle(): int
    {
        try {
            RuntimeMarker::assertCanonical();
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (app()->environment(['local', 'testing'])) {
            $this->error('Live acceptance cannot run against local/testing environments. Use a dedicated Cloud staging environment.');

            return self::FAILURE;
        }

        $bindings = [
            DeterministicAnalysisEngine::class => ProductionEditionDeterministicAnalysisEngine::class,
            ExternalSourceAdapterCatalog::class => FixedExternalSourceAdapterCatalog::class,
            StructuredAiProvider::class => OpenAiResponsesProvider::class,
        ];
        foreach ($bindings as $port => $canonical) {
            if (! $this->app->bound($port) || ! $this->app->make($port) instanceof $canonical) {
                $this->error("{$port} is not bound to the canonical production implementation.");

                return self::FAILURE;
            }
        }

        $edition = strtolower((string) $this->option('edition'));
        if (! in_array($edition, ['all', 'poe1', 'poe2'], true)) {
            $this->error('Edition must be all, poe1, or poe2.');

            return self::FAILURE;
        }

        $this->info('Acceptance gate armed for '.$edition.' with '.RuntimeMarker::current().' runtime.');
        $this->line('Use the operator runbook in docs/release/live-acceptance.md; no fixtures or destructive commands are permitted.');

        return self::SUCCESS;
    }
}

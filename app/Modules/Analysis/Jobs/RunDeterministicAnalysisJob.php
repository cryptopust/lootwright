<?php

namespace App\Modules\Analysis\Jobs;

use App\Modules\Analysis\Infrastructure\ProductionPoe2DeterministicAnalysisEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Application\Workflow\UseCases\RunDeterministicAnalysis;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\PoE1\Analysis\Poe1DeterministicAnalysisEngine;
use PDOException;
use RedisException;
use Throwable;

final class RunDeterministicAnalysisJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Deterministic analysis is bounded and safe to retry on managed queues. */
    public int $timeout = 300;

    public bool $failOnTimeout = true;

    public int $maxExceptions = 3;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly string $analysisId,
        public readonly ?GameEdition $edition = null,
        public readonly ?string $rulesetChecksumSha256 = null,
    ) {
        $this->onQueue('deterministic-analysis');
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function uniqueId(): string
    {
        return 'analyze:'.$this->analysisId;
    }

    public function handle(RunDeterministicAnalysis $useCase, WorkflowRepository $repository): void
    {
        $correlationId = Context::get('correlation_id');
        $correlationId = is_string($correlationId) && $correlationId !== '' ? $correlationId : (string) Str::uuid7();

        Context::scope(fn () => $this->handleWithContext($useCase, $repository), [
            'correlation_id' => $correlationId,
            'analysis_id' => $this->analysisId,
            'game_edition' => $this->edition?->value,
            'ruleset_checksum_sha256' => $this->rulesetChecksumSha256,
            'engine_version' => $this->edition === GameEdition::Poe1
                ? Poe1DeterministicAnalysisEngine::ENGINE_VERSION
                : ProductionPoe2DeterministicAnalysisEngine::ENGINE_VERSION,
            'workflow_stage' => 'deterministic_analysis',
        ]);
    }

    private function handleWithContext(RunDeterministicAnalysis $useCase, WorkflowRepository $repository): void
    {
        if (! $this->validUuid7($this->analysisId) || ! $this->edition instanceof GameEdition
            || ! is_string($this->rulesetChecksumSha256)
            || preg_match('/^[0-9a-f]{64}$/D', $this->rulesetChecksumSha256) !== 1
        ) {
            if ($this->validUuid7($this->analysisId)) {
                $repository->transitionAnalysis($this->analysisId, AnalysisState::Failed, failureCode: 'queued_analysis_identity_missing');
            }

            Log::warning('analysis_run_invalid_job_identity', [
                'analysis_id_hash' => hash('sha256', $this->analysisId),
            ]);

            return;
        }

        if (! (bool) config('security.emergency.rulesets')) {
            $repository->transitionAnalysis($this->analysisId, AnalysisState::PolicyBlocked, failureCode: 'emergency_rulesets_disabled');

            return;
        }

        $analysis = $repository->analysis($this->analysisId);
        $persistedChecksum = $analysis === null ? null : $this->rulesetChecksum($analysis->parametersSnapshot);
        $persistedChecksum ??= $analysis?->rulesetChecksumSha256;

        if ($analysis !== null && ($analysis->edition !== $this->edition
            || $persistedChecksum === null
            || ! hash_equals($this->rulesetChecksumSha256, $persistedChecksum))
        ) {
            $repository->transitionAnalysis($this->analysisId, AnalysisState::Failed, failureCode: 'queued_analysis_identity_mismatch');

            return;
        }

        try {
            Log::info('analysis_run_started');
            $useCase->handle($this->analysisId);
            Log::info('analysis_run_finished');
        } catch (TransientWorkflowFailure|QueryException|PDOException|RedisException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('analysis_run_unexpected_terminal_failure', [
                'analysis_id' => $this->analysisId,
                'exception_type' => $exception::class,
            ]);
            $repository->transitionAnalysis(
                $this->analysisId,
                AnalysisState::Failed,
                failureCode: 'unexpected_terminal_failure',
            );
        }
    }

    private function rulesetChecksum(string $parametersSnapshot): ?string
    {
        $parameters = json_decode($parametersSnapshot, true);
        $checksum = is_array($parameters) && is_array($parameters['selection'] ?? null)
            ? ($parameters['selection']['ruleset_checksum_sha256'] ?? null)
            : null;

        return is_string($checksum) ? $checksum : null;
    }

    public function failed(?Throwable $exception): void
    {
        app(WorkflowRepository::class)->transitionAnalysis(
            $this->analysisId,
            AnalysisState::Failed,
            failureCode: 'transient_retries_exhausted',
        );
    }

    /** @return list<string> */
    public function tags(): array
    {
        return array_values(array_filter([
            'workflow:analysis',
            'analysis:'.$this->analysisId,
            $this->edition === null ? null : 'edition:'.$this->edition->value,
            $this->rulesetChecksumSha256 === null ? null : 'ruleset:'.$this->rulesetChecksumSha256,
        ]));
    }

    private function validUuid7(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value) === 1;
    }
}

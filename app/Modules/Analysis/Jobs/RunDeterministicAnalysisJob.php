<?php

namespace App\Modules\Analysis\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lootwright\Application\Workflow\AnalysisState;
use Lootwright\Application\Workflow\Exception\TransientWorkflowFailure;
use Lootwright\Application\Workflow\Ports\WorkflowRepository;
use Lootwright\Application\Workflow\UseCases\RunDeterministicAnalysis;
use PDOException;
use RedisException;
use Throwable;

final class RunDeterministicAnalysisJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public readonly string $analysisId) {}

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
        try {
            $useCase->handle($this->analysisId);
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
        return ['workflow:analysis', 'analysis:'.$this->analysisId];
    }
}

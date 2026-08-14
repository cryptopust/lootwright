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
use Lootwright\Application\Workflow\UseCases\ParseAndNormalizeBuild;
use PDOException;
use RedisException;
use Throwable;

final class ParseBuildArtifactJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public readonly string $artifactId) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function uniqueId(): string
    {
        return 'parse:'.$this->artifactId;
    }

    public function handle(ParseAndNormalizeBuild $useCase, WorkflowRepository $repository): void
    {
        try {
            $useCase->handle($this->artifactId);
        } catch (TransientWorkflowFailure|QueryException|PDOException|RedisException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('analysis_parse_unexpected_terminal_failure', [
                'artifact_id' => $this->artifactId,
                'exception_type' => $exception::class,
            ]);
            $repository->failArtifact(
                $this->artifactId,
                AnalysisState::Failed,
                'unexpected_terminal_failure',
            );
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(WorkflowRepository::class)->failArtifact(
            $this->artifactId,
            AnalysisState::Failed,
            'transient_retries_exhausted',
        );
    }

    /** @return list<string> */
    public function tags(): array
    {
        return ['workflow:parse', 'artifact:'.$this->artifactId];
    }
}

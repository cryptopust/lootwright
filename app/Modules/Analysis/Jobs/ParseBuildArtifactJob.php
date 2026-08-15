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
use Lootwright\Domain\Shared\Game\GameEdition;
use PDOException;
use RedisException;
use Throwable;

final class ParseBuildArtifactJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly string $artifactId,
        public readonly ?GameEdition $edition = null,
    ) {}

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
        if (! $this->validUuid7($this->artifactId) || ! $this->edition instanceof GameEdition) {
            Log::warning('analysis_parse_invalid_job_identity', [
                'artifact_id_hash' => hash('sha256', $this->artifactId),
            ]);

            return;
        }

        if (! (bool) config('security.emergency.imports')) {
            $repository->failArtifact($this->artifactId, AnalysisState::PolicyBlocked, 'emergency_imports_disabled');

            return;
        }

        $artifact = $repository->artifact($this->artifactId);

        if ($artifact !== null && $artifact->edition !== $this->edition) {
            $repository->failArtifact($this->artifactId, AnalysisState::Failed, 'queued_edition_mismatch');

            return;
        }

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
        $tags = [
            'workflow:parse',
            'artifact:'.$this->artifactId,
        ];

        if ($this->edition !== null) {
            $tags[] = 'edition:'.$this->edition->value;
        }

        return $tags;
    }

    private function validUuid7(string $value): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $value) === 1;
    }
}

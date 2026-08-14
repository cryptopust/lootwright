<?php

namespace Lootwright\Domain\Recommendations;

use JsonSerializable;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;

final readonly class Recommendation implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $findings
     * @param  array<array-key, mixed>  $alternatives
     */
    private function __construct(
        public GameEdition $edition,
        public AnalysisId $analysisId,
        public string $code,
        public UpgradePriority $priority,
        public RecommendationImpact $impact,
        public array $findings,
        public array $alternatives,
        public ExplanationTrace $trace,
    ) {}

    /**
     * @param  array<array-key, mixed>  $findings
     * @param  array<array-key, mixed>  $alternatives
     */
    public static function create(
        GameEdition $edition,
        AnalysisId $analysisId,
        string $code,
        UpgradePriority $priority,
        RecommendationImpact $impact,
        array $findings,
        array $alternatives,
        ExplanationTrace $trace,
    ): DomainResult {
        if (! $analysisId->belongsTo($edition) || $trace->edition !== $edition) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'A recommendation, analysis, and explanation trace must share an edition.',
            ));
        }

        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $code) !== 1 || $findings === []) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A recommendation requires a canonical code and at least one finding.',
            ));
        }

        $validatedFindings = [];

        foreach ($findings as $finding) {
            if (! $finding instanceof Finding || ! $finding->analysisId->belongsTo($edition)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::EditionMismatch,
                    'Recommendation findings must belong to the recommendation edition.',
                ));
            }

            if (! $finding->analysisId->equals($analysisId)) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::AnalysisMismatch,
                    'Recommendation findings must belong to the same analysis.',
                ));
            }

            $validatedFindings[] = $finding;
        }

        $validatedAlternatives = [];

        foreach ($alternatives as $alternative) {
            if (! $alternative instanceof AlternativePath) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'Recommendation alternatives must be AlternativePath values.',
                ));
            }

            $validatedAlternatives[] = $alternative;
        }

        return DomainResult::success(new self(
            $edition,
            $analysisId,
            $code,
            $priority,
            $impact,
            $validatedFindings,
            $validatedAlternatives,
            $trace,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'analysis_id' => $this->analysisId,
            'code' => $this->code,
            'priority' => $this->priority->value,
            'impact' => $this->impact,
            'findings' => $this->findings,
            'alternatives' => $this->alternatives,
            'trace' => $this->trace,
        ];
    }
}

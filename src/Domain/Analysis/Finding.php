<?php

namespace Lootwright\Domain\Analysis;

use JsonSerializable;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Identity\AnalysisId;

final readonly class Finding implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $inputEvidence
     */
    private function __construct(
        public AnalysisId $analysisId,
        public string $code,
        public FindingSeverity $severity,
        public string $summary,
        public array $inputEvidence,
        public RuleReference $rule,
        public ExplanationTrace $trace,
    ) {}

    /**
     * @param  array<array-key, mixed>  $inputEvidence
     */
    public static function create(
        CanonicalBuild $build,
        AnalysisId $analysisId,
        string $code,
        FindingSeverity $severity,
        string $summary,
        array $inputEvidence,
        RuleReference $rule,
        ExplanationTrace $trace,
    ): DomainResult {
        $edition = $build->snapshot->scope->edition;
        $code = trim($code);
        $summary = trim($summary);

        if (! $analysisId->belongsTo($edition)
            || $rule->edition !== $edition
            || $trace->edition !== $edition
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'A finding, trace, rule, analysis, and build must share a game edition.',
            ));
        }

        if (! $rule->rulesetId->equals($build->ruleset->id)
            || ! $rule->rulesetVersion->equals($build->ruleset->version)
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::RulesetMismatch,
                'A finding rule must reference the canonical build ruleset.',
            ));
        }

        if (preg_match('/^[a-z][a-z0-9._-]{1,127}$/D', $code) !== 1
            || $summary === ''
            || mb_strlen($summary) > 500
            || $inputEvidence === []
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'A finding requires a canonical code, bounded summary, and input evidence.',
            ));
        }

        $validatedEvidence = [];

        foreach ($inputEvidence as $evidence) {
            if (! is_string($evidence)
                || preg_match('/^[a-z][a-z0-9._:-]{1,127}$/D', $evidence) !== 1
            ) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidIdentifier,
                    'Finding evidence keys must be canonical.',
                ));
            }

            $validatedEvidence[] = $evidence;
        }

        if (count($validatedEvidence) !== count(array_unique($validatedEvidence))) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::DuplicateValue,
                'Finding evidence keys cannot be duplicated.',
            ));
        }

        return DomainResult::success(new self(
            $analysisId,
            $code,
            $severity,
            $summary,
            $validatedEvidence,
            $rule,
            $trace,
        ));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'analysis_id' => $this->analysisId,
            'code' => $this->code,
            'severity' => $this->severity->value,
            'summary' => $this->summary,
            'input_evidence' => $this->inputEvidence,
            'rule' => $this->rule,
            'trace' => $this->trace,
        ];
    }
}

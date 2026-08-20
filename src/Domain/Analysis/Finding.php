<?php

namespace Lootwright\Domain\Analysis;

use JsonSerializable;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Evidence\TraceStep;
use Lootwright\Domain\Shared\Identity\AnalysisId;

final readonly class Finding implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $inputEvidence
     * @param  list<string>  $affectedSlots
     * @param  list<string>  $affectedGems
     * @param  list<string>  $affectedNodes
     * @param  array<string, mixed>  $sourceProvenance
     */
    private function __construct(
        public AnalysisId $analysisId,
        public string $code,
        public FindingSeverity $severity,
        public string $summary,
        public array $inputEvidence,
        public RuleReference $rule,
        public ExplanationTrace $trace,
        public FindingCategory $category,
        public string $title,
        public string $explanation,
        public mixed $observedValue,
        public mixed $expectedValue,
        public array $affectedSlots,
        public array $affectedGems,
        public array $affectedNodes,
        public array $sourceProvenance,
        public int $confidenceBasisPoints,
    ) {}

    /**
     * @param  array<array-key, mixed>  $inputEvidence
     * @param  list<string>  $affectedSlots
     * @param  list<string>  $affectedGems
     * @param  list<string>  $affectedNodes
     * @param  array<string, mixed>  $sourceProvenance
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
        FindingCategory $category = FindingCategory::DataQuality,
        ?string $title = null,
        ?string $explanation = null,
        mixed $observedValue = null,
        mixed $expectedValue = null,
        array $affectedSlots = [],
        array $affectedGems = [],
        array $affectedNodes = [],
        array $sourceProvenance = [],
        int $confidenceBasisPoints = 10_000,
    ): DomainResult {
        $edition = $build->snapshot->scope->edition;
        $code = trim($code);
        $summary = trim($summary);
        $title = trim($title ?? $summary);
        $explanation = trim($explanation ?? $summary);

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
            || $title === ''
            || mb_strlen($title) > 200
            || $explanation === ''
            || mb_strlen($explanation) > 1_000
            || $confidenceBasisPoints < 0
            || $confidenceBasisPoints > 10_000
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
            $category,
            $title,
            $explanation,
            $observedValue,
            $expectedValue,
            self::canonicalIdentifiers($affectedSlots),
            self::canonicalIdentifiers($affectedGems),
            self::canonicalIdentifiers($affectedNodes),
            $sourceProvenance,
            $confidenceBasisPoints,
        ));
    }

    /**
     * Creates a finding directly from a normalized adapter product. This path
     * is intentionally independent of BuildCatalog so data-quality findings
     * can describe a missing class or Ascendancy without inventing one.
     *
     * @param  list<string>  $inputEvidence
     * @param  list<string>  $affectedSlots
     * @param  list<string>  $affectedGems
     * @param  list<string>  $affectedNodes
     * @param  array<string, mixed>  $sourceProvenance
     */
    public static function deterministic(
        AnalysisId $analysisId,
        RulesetIdentity $ruleset,
        string $code,
        FindingSeverity $severity,
        FindingCategory $category,
        string $title,
        string $explanation,
        mixed $observedValue,
        mixed $expectedValue,
        array $affectedSlots,
        array $affectedGems,
        array $affectedNodes,
        array $inputEvidence,
        array $sourceProvenance,
        int $confidenceBasisPoints = 10_000,
    ): DomainResult {
        $rule = RuleReference::create($ruleset->edition, $ruleset->id, $ruleset->version, $code);
        if ($rule->isFailure()) {
            return $rule;
        }
        $step = TraceStep::create(
            $code.'.evaluate',
            $explanation,
            $inputEvidence,
            $rule->value(),
        );
        if ($step->isFailure()) {
            return $step;
        }
        $trace = ExplanationTrace::create($ruleset->edition, [$step->value()]);
        if ($trace->isFailure()) {
            return $trace;
        }

        return DomainResult::success(new self(
            $analysisId,
            $code,
            $severity,
            $title,
            $inputEvidence,
            $rule->value(),
            $trace->value(),
            $category,
            $title,
            $explanation,
            $observedValue,
            $expectedValue,
            self::canonicalIdentifiers($affectedSlots),
            self::canonicalIdentifiers($affectedGems),
            self::canonicalIdentifiers($affectedNodes),
            $sourceProvenance,
            $confidenceBasisPoints,
        ));
    }

    /** @param array<array-key, mixed> $values
     * @return list<string>
     */
    private static function canonicalIdentifiers(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                $result[] = mb_substr(trim($value), 0, 160);
            }
        }
        $result = array_values(array_unique($result));
        sort($result, SORT_STRING);

        return $result;
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
            'category' => $this->category->value,
            'title' => $this->title,
            'deterministic_explanation' => $this->explanation,
            'observed_value' => $this->observedValue,
            'expected_value' => $this->expectedValue,
            'affected_slots' => $this->affectedSlots,
            'affected_gems' => $this->affectedGems,
            'affected_nodes' => $this->affectedNodes,
            'source_provenance' => $this->sourceProvenance,
            'confidence_basis_points' => $this->confidenceBasisPoints,
        ];
    }
}

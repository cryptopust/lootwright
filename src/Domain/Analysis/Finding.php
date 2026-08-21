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
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Version\RulesetVersion;

final readonly class Finding implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $inputEvidence
     * @param  list<string>  $affectedSlots
     * @param  list<string>  $affectedGems
     * @param  list<string>  $affectedNodes
     * @param  array<string, mixed>  $sourceProvenance
     * @param  list<string>  $unsupportedData
     * @param  list<string>  $dependencies
     */
    private function __construct(
        public string $findingId,
        public GameEdition $gameEdition,
        public RulesetVersion $rulesetVersion,
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
        /** @var list<string> */
        public array $unsupportedData,
        /** @var list<string> */
        public array $dependencies,
        /** @var array{slots:list<string>,gems:list<string>,nodes:list<string>} */
        public array $affectedEntity,
        /** @var list<string> */
        public array $evidence,
        public string $ruleId,
        public int $confidence,
        public ExplanationTrace $explanationTrace,
    ) {}

    /**
     * @param  array<array-key, mixed>  $inputEvidence
     * @param  list<string>  $affectedSlots
     * @param  list<string>  $affectedGems
     * @param  list<string>  $affectedNodes
     * @param  array<string, mixed>  $sourceProvenance
     * @param  list<string>  $unsupportedData
     * @param  list<string>  $dependencies
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
        array $unsupportedData = [],
        array $dependencies = [],
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
            self::findingId($rule, $code, $affectedSlots, $affectedGems, $affectedNodes),
            $edition,
            $rule->rulesetVersion,
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
            self::canonicalIdentifiers($unsupportedData),
            self::canonicalIdentifiers($dependencies === [] ? $validatedEvidence : $dependencies),
            self::affectedEntity($affectedSlots, $affectedGems, $affectedNodes),
            $validatedEvidence,
            $rule->ruleKey,
            $confidenceBasisPoints,
            $trace,
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
     * @param  list<string>  $unsupportedData
     * @param  list<string>  $dependencies
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
        array $unsupportedData = [],
        array $dependencies = [],
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
            self::findingId($rule->value(), $code, $affectedSlots, $affectedGems, $affectedNodes),
            $ruleset->edition,
            $ruleset->version,
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
            self::canonicalIdentifiers($unsupportedData),
            self::canonicalIdentifiers($dependencies === [] ? $inputEvidence : $dependencies),
            self::affectedEntity($affectedSlots, $affectedGems, $affectedNodes),
            $inputEvidence,
            $rule->value()->ruleKey,
            $confidenceBasisPoints,
            $trace->value(),
        ));
    }

    /**
     * @param  list<string>  $slots
     * @param  list<string>  $gems
     * @param  list<string>  $nodes
     */
    private static function findingId(RuleReference $rule, string $code, array $slots, array $gems, array $nodes): string
    {
        $entities = [...self::canonicalIdentifiers($slots), ...self::canonicalIdentifiers($gems), ...self::canonicalIdentifiers($nodes)];

        return 'finding.'.substr(hash('sha256', implode("\0", [
            $rule->edition->value,
            $rule->rulesetId->value,
            $rule->rulesetVersion->value,
            $code,
            ...$entities,
        ])), 0, 32);
    }

    /**
     * @param  list<string>  $slots
     * @param  list<string>  $gems
     * @param  list<string>  $nodes
     * @return array{slots:list<string>,gems:list<string>,nodes:list<string>}
     */
    private static function affectedEntity(array $slots, array $gems, array $nodes): array
    {
        return [
            'slots' => self::canonicalIdentifiers($slots),
            'gems' => self::canonicalIdentifiers($gems),
            'nodes' => self::canonicalIdentifiers($nodes),
        ];
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

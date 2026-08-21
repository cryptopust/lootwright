<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

use Lootwright\Domain\Analysis\AnalysisContext;
use Lootwright\Domain\Analysis\AnalysisEngine;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\AnalysisStatus;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

/** Pure PoE1 orchestration over the reviewed PoE1 rule implementation. */
final readonly class Poe1AnalysisEngine implements AnalysisEngine
{
    /** @param array<int|string, true> $knownPassiveNodeIds */
    public function __construct(
        private Poe1DeterministicAnalysisEngine $rules,
        private Poe1AnalysisRuleset $configuration,
        private array $knownPassiveNodeIds = [],
        private string $engineVersion = Poe1DeterministicAnalysisEngine::ENGINE_VERSION,
        /** @var array<string,mixed> */
        private array $sourceProvenance = [],
    ) {}

    public function analyze(
        BuildSnapshot|CanonicalImportedBuild|CanonicalBuild $build,
        BuildIntent $intent,
        GameRuleset $ruleset,
    ): DomainResult {
        $edition = $build instanceof BuildSnapshot
            ? $build->scope->edition
            : ($build instanceof CanonicalBuild ? $build->snapshot->scope->edition : $build->edition);

        if ($edition !== $ruleset->identity->edition || $edition->value !== 'poe1' || $intent->goal->edition !== $edition) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::EditionMismatch,
                'The PoE1 analysis engine accepts only matching PoE1 inputs.',
            ));
        }

        if (! $ruleset->approvedForProduction()) {
            return DomainResult::success(new AnalysisResult($edition, $ruleset->identity, $this->engineVersion, AnalysisStatus::Unavailable, unsupportedData: ['ruleset_not_approved']));
        }

        if (! $build instanceof CanonicalImportedBuild) {
            return DomainResult::success(new AnalysisResult($edition, $ruleset->identity, $this->engineVersion, AnalysisStatus::Unsupported, unsupportedData: ['canonical_build_facts_missing']));
        }

        return DomainResult::success($this->analyzeFor($this->analysisId($build, $ruleset), $build, $intent, $ruleset));
    }

    public function analyzeFor(
        AnalysisId $analysisId,
        CanonicalImportedBuild $build,
        BuildIntent $intent,
        GameRuleset $ruleset,
    ): AnalysisResult {
        if (! $analysisId->belongsTo($ruleset->identity->edition)
            || $build->edition !== $ruleset->identity->edition
            || $intent->goal->edition !== $build->edition
        ) {
            throw new \InvalidArgumentException('The analysis, build, intent, and ruleset must share one edition.');
        }
        if (! $ruleset->approvedForProduction()) {
            return new AnalysisResult($build->edition, $ruleset->identity, $this->engineVersion, AnalysisStatus::Unavailable, unsupportedData: ['ruleset_not_approved']);
        }

        $context = new AnalysisContext(
            $build,
            $intent,
            $ruleset,
            $analysisId,
            $this->knownPassiveNodeIds,
            $this->sourceProvenance === []
                ? ['input' => $build->sourceMetadata, 'ruleset' => $ruleset->identity]
                : $this->sourceProvenance,
        );
        $registry = new Poe1RuleRegistry($this->rules, $this->configuration, $this->knownPassiveNodeIds, $ruleset->identity->version);
        $findings = [];
        foreach ($registry->rules() as $rule) {
            array_push($findings, ...$rule->evaluate($context));
        }

        return new AnalysisResult(
            $build->edition,
            $ruleset->identity,
            $this->engineVersion,
            AnalysisStatus::Complete,
            $findings,
            unsupportedData: $this->unsupportedData($build),
        );
    }

    private function analysisId(CanonicalImportedBuild $build, GameRuleset $ruleset): AnalysisId
    {
        $seed = hash('sha256', CanonicalJson::encode([$build, $ruleset->identity]));
        $hex = substr($seed, 0, 32);
        $hex[12] = '7';
        $hex[16] = in_array($hex[16], ['8', '9', 'a', 'b'], true) ? $hex[16] : '8';
        $uuid = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);

        return AnalysisId::from($ruleset->identity->edition, $uuid)->value();
    }

    /** @return list<string> */
    private function unsupportedData(CanonicalImportedBuild $build): array
    {
        $unsupported = [];
        foreach ($build->propertySupport as $property => $status) {
            if ($status->value === 'unsupported' || $status->value === 'unknown') {
                $unsupported[] = 'property:'.$property.':'.$status->value;
            }
        }
        foreach ($build->unsupportedFields as $feature) {
            $unsupported[] = 'input:'.$feature->path.':'.$feature->element;
        }
        $unsupported = array_values(array_unique($unsupported));
        sort($unsupported, SORT_STRING);

        return $unsupported;
    }
}

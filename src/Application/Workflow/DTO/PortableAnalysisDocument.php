<?php

namespace Lootwright\Application\Workflow\DTO;

use JsonSerializable;

final readonly class PortableAnalysisDocument implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $build
     * @param  array<string, mixed>  $selection
     * @param  array<string, mixed>  $ruleset
     * @param  array<string, mixed>  $analysisInput
     * @param  array<string, mixed>  $analysisOutput
     * @param  list<array<string, mixed>>  $provenance
     * @param  list<array<string, mixed>>  $policyDecisions
     * @param  list<array<string, mixed>>  $findings
     * @param  list<array<string, mixed>>  $recommendations
     * @param  list<array<string, mixed>>  $recipes
     * @param  array<string, mixed>|null  $explanation
     */
    public function __construct(
        public string $schemaVersion,
        public string $analysisId,
        public int $analysisVersion,
        public string $gameEdition,
        public array $build,
        public array $selection,
        public array $ruleset,
        public array $analysisInput,
        public array $analysisOutput,
        public array $provenance,
        public array $policyDecisions,
        public array $findings,
        public array $recommendations,
        public array $recipes,
        public ?array $explanation,
        public string $inputHashSha256,
        public string $outputHashSha256,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'analysis_id' => $this->analysisId,
            'analysis_input' => $this->analysisInput,
            'analysis_output' => $this->analysisOutput,
            'analysis_version' => $this->analysisVersion,
            'build' => $this->build,
            'explanation' => $this->explanation,
            'findings' => $this->findings,
            'game_edition' => $this->gameEdition,
            'input_hash_sha256' => $this->inputHashSha256,
            'output_hash_sha256' => $this->outputHashSha256,
            'policy_decisions' => $this->policyDecisions,
            'provenance' => $this->provenance,
            'recipes' => $this->recipes,
            'recommendations' => $this->recommendations,
            'ruleset' => $this->ruleset,
            'schema_version' => $this->schemaVersion,
            'selection' => $this->selection,
        ];
    }
}

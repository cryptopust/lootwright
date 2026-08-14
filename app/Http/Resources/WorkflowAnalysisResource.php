<?php

namespace App\Http\Resources;

use Lootwright\Application\Workflow\DTO\AnalysisRecord;

final class WorkflowAnalysisResource
{
    private function __construct() {}

    /** @return array<string, mixed> */
    public static function make(AnalysisRecord $analysis): array
    {
        return [
            'id' => $analysis->id,
            'artifact_id' => $analysis->artifactId,
            'game' => $analysis->edition->value,
            'version' => $analysis->version,
            'state' => $analysis->state->value,
            'parent_analysis_id' => $analysis->parentAnalysisId,
            'adapter' => $analysis->adapterKey,
            'parser_version' => $analysis->parserVersion,
            'ruleset' => $analysis->rulesetId === null ? null : [
                'id' => $analysis->rulesetId,
                'version' => $analysis->rulesetVersion,
                'checksum_sha256' => $analysis->rulesetChecksumSha256,
            ],
            'parameters_hash_sha256' => $analysis->parametersHashSha256,
            'input_hash_sha256' => $analysis->inputHashSha256,
            'output_hash_sha256' => $analysis->outputHashSha256,
            'output' => self::decode($analysis->outputSnapshot),
            'clarification' => self::decode($analysis->clarificationSnapshot),
            'failure_code' => $analysis->failureCode,
        ];
    }

    private static function decode(?string $json): mixed
    {
        return $json === null ? null : json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }
}

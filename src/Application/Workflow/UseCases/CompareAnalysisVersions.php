<?php

namespace Lootwright\Application\Workflow\UseCases;

use Lootwright\Application\Workflow\DTO\AnalysisComparison;
use Lootwright\Application\Workflow\Exception\InvalidWorkflowInput;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class CompareAnalysisVersions
{
    public function __construct(private RetrieveAnalysis $retrieve) {}

    public function handle(string $ownerId, string $leftAnalysisId, string $rightAnalysisId): AnalysisComparison
    {
        $left = $this->retrieve->handle($ownerId, $leftAnalysisId);
        $right = $this->retrieve->handle($ownerId, $rightAnalysisId);

        if ($left->artifactId !== $right->artifactId || $left->edition !== $right->edition) {
            throw new InvalidWorkflowInput('Only versions of the same game-scoped artifact can be compared.');
        }

        [$added, $resolved, $unchanged] = $this->findingDiff($left->outputSnapshot, $right->outputSnapshot);

        return new AnalysisComparison(
            $left->id,
            $right->id,
            $left->inputHashSha256 !== $right->inputHashSha256,
            $left->outputHashSha256 !== $right->outputHashSha256,
            $left->rulesetId !== $right->rulesetId
                || $left->rulesetVersion !== $right->rulesetVersion
                || $left->rulesetChecksumSha256 !== $right->rulesetChecksumSha256,
            $left->outputHashSha256,
            $right->outputHashSha256,
            $added,
            $resolved,
            $unchanged,
        );
    }

    /** @return array{list<string>, list<string>, list<string>} */
    private function findingDiff(?string $leftSnapshot, ?string $rightSnapshot): array
    {
        $left = $this->findings($leftSnapshot);
        $right = $this->findings($rightSnapshot);
        $added = [];
        $resolved = [];
        $unchanged = [];
        foreach ($right as $code => $payload) {
            if (! isset($left[$code])) {
                $added[] = $code;
            } elseif (hash_equals($left[$code], $payload)) {
                $unchanged[] = $code;
            } else {
                $added[] = $code;
                $resolved[] = $code;
            }
        }
        foreach ($left as $code => $_payload) {
            if (! isset($right[$code])) {
                $resolved[] = $code;
            }
        }
        foreach ([&$added, &$resolved, &$unchanged] as &$codes) {
            $codes = array_values(array_unique($codes));
            sort($codes, SORT_STRING);
        }

        return [$added, $resolved, $unchanged];
    }

    /** @return array<string, string> */
    private function findings(?string $snapshot): array
    {
        if ($snapshot === null) {
            return [];
        }
        $document = json_decode($snapshot, true);
        if (! is_array($document) || ! is_array($document['findings'] ?? null)) {
            return [];
        }
        $findings = [];
        foreach ($document['findings'] as $finding) {
            if (is_array($finding) && is_string($finding['code'] ?? null)) {
                unset($finding['analysis_id']);
                $findings[$finding['code']] = CanonicalJson::encode($finding);
            }
        }
        ksort($findings, SORT_STRING);

        return $findings;
    }
}

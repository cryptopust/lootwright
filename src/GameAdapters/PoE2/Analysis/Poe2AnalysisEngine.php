<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use InvalidArgumentException;
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
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

/** Pure PoE2 orchestration over the independently versioned PoE2 rules. */
final readonly class Poe2AnalysisEngine implements AnalysisEngine
{
    public function analyze(BuildSnapshot|CanonicalImportedBuild|CanonicalBuild $build, BuildIntent $intent, GameRuleset $ruleset): DomainResult
    {
        $edition = $build instanceof BuildSnapshot ? $build->scope->edition : ($build instanceof CanonicalBuild ? $build->snapshot->scope->edition : $build->edition);
        if ($edition !== GameEdition::Poe2 || $ruleset->identity->edition !== $edition || $intent->goal->edition !== $edition) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::EditionMismatch, 'The PoE2 engine requires matching PoE2 inputs.'));
        }

        if (! $ruleset->approvedForProduction()) {
            return DomainResult::success(new AnalysisResult($edition, $ruleset->identity, Poe2DeterministicAnalysisEngine::ENGINE_VERSION, AnalysisStatus::Unavailable, unsupportedData: ['poe2_ruleset_unavailable', 'poe2_rules_not_approved']));
        }
        if (! $build instanceof CanonicalImportedBuild) {
            return DomainResult::success(new AnalysisResult($edition, $ruleset->identity, Poe2DeterministicAnalysisEngine::ENGINE_VERSION, AnalysisStatus::Unsupported, unsupportedData: ['poe2_canonical_build_facts_missing']));
        }
        $seed = hash('sha256', CanonicalJson::encode([$build, $ruleset->identity]));
        $hex = substr($seed, 0, 32);
        $hex[12] = '7';
        $hex[16] = in_array($hex[16], ['8', '9', 'a', 'b'], true) ? $hex[16] : '8';
        $uuid = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
        $analysisId = AnalysisId::from(GameEdition::Poe2, $uuid)->value();

        try {
            return DomainResult::success((new Poe2DeterministicAnalysisEngine)->analyze($build, $analysisId, $ruleset->identity, Poe2AnalysisRuleset::publishedV1(), ['input' => $build->sourceMetadata, 'ruleset' => $ruleset->identity]));
        } catch (InvalidArgumentException $exception) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::InvalidValue, $exception->getMessage()));
        }
    }
}

<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

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

/** Fail-closed PoE2 adapter until an approved PoE2 ruleset is available. */
final readonly class Poe2AnalysisEngine implements AnalysisEngine
{
    public function analyze(BuildSnapshot|CanonicalImportedBuild|CanonicalBuild $build, BuildIntent $intent, GameRuleset $ruleset): DomainResult
    {
        $edition = $build instanceof BuildSnapshot ? $build->scope->edition : ($build instanceof CanonicalBuild ? $build->snapshot->scope->edition : $build->edition);
        if ($edition !== GameEdition::Poe2 || $ruleset->identity->edition !== $edition || $intent->goal->edition !== $edition) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::EditionMismatch, 'The PoE2 engine requires matching PoE2 inputs.'));
        }

        return DomainResult::success(new AnalysisResult($edition, $ruleset->identity, '0.0.0', AnalysisStatus::Unavailable, unsupportedData: ['poe2_ruleset_unavailable', 'poe2_rules_not_approved']));
    }
}

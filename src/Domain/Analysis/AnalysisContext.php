<?php

namespace Lootwright\Domain\Analysis;

use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;

/** Framework-independent, immutable input presented to a rule. */
final readonly class AnalysisContext
{
    /** @param array<int|string, true> $knownPassiveNodeIds */
    public function __construct(
        public CanonicalImportedBuild|BuildSnapshot $build,
        public BuildIntent $intent,
        public GameRuleset $ruleset,
        public AnalysisId $analysisId,
        public array $knownPassiveNodeIds = [],
        /** @var array<string,mixed> */
        public array $sourceProvenance = [],
    ) {
        $edition = $build instanceof BuildSnapshot ? $build->scope->edition : $build->edition;
        if ($edition !== $ruleset->identity->edition || $intent->goal->edition !== $edition || ! $analysisId->belongsTo($edition)) {
            throw new \InvalidArgumentException('Analysis context values must share one game edition.');
        }
    }

    public function edition(): GameEdition
    {
        return $this->build instanceof BuildSnapshot ? $this->build->scope->edition : $this->build->edition;
    }
}

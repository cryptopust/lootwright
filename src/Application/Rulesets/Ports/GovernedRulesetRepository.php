<?php

namespace Lootwright\Application\Rulesets\Ports;

use Lootwright\Application\Rulesets\DTO\RulesetActivation;
use Lootwright\Application\Rulesets\DTO\RulesetPublication;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotRecord;

interface GovernedRulesetRepository
{
    public function importSnapshot(SourceSnapshotImport $snapshot): SourceSnapshotRecord;

    public function publish(RulesetPublication $ruleset): void;

    public function activate(string $rulesetVersionId, string $actorType = 'operator'): RulesetActivation;
}

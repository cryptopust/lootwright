<?php

namespace Lootwright\Application\Rulesets\Services;

use DomainException;
use Lootwright\Application\Rulesets\DTO\RulesetActivation;
use Lootwright\Application\Rulesets\DTO\RulesetPublication;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotImport;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotQuarantine;
use Lootwright\Application\Rulesets\DTO\SourceSnapshotRecord;
use Lootwright\Application\Rulesets\Ports\GovernedRulesetRepository;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;

final readonly class GovernedRulesetLifecycle
{
    public function __construct(
        private GovernedRulesetRepository $repository,
        private SourceGovernancePolicy $policy,
    ) {}

    /** @param list<string> $conditions */
    public function import(SourceSnapshotImport $snapshot, array $conditions = []): SourceSnapshotRecord
    {
        if (! $this->policy->permitsImport($snapshot->sourceCode, $snapshot->sourceVersion, $snapshot->operation, $conditions)) {
            throw new DomainException('The source policy gate denied this import.');
        }

        return $this->repository->importSnapshot($snapshot);
    }

    /** @param list<string> $conditions */
    public function quarantine(SourceSnapshotQuarantine $snapshot, array $conditions = []): SourceSnapshotRecord
    {
        if (! $this->policy->permitsImport($snapshot->sourceCode, $snapshot->sourceVersion, $snapshot->operation, $conditions)) {
            throw new DomainException('The source policy gate denied this import.');
        }

        return $this->repository->quarantineSnapshot($snapshot);
    }

    public function publish(RulesetPublication $ruleset): string
    {
        return $this->repository->publish($ruleset);
    }

    public function activate(string $rulesetVersionId, string $actorType = 'operator'): RulesetActivation
    {
        return $this->repository->activate($rulesetVersionId, $actorType);
    }
}

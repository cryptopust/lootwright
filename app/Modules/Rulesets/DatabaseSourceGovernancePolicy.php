<?php

namespace App\Modules\Rulesets;

use Carbon\CarbonImmutable;
use Lootwright\Application\Rulesets\Ports\SourceGovernancePolicy;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityDecision;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;

final readonly class DatabaseSourceGovernancePolicy implements SourceGovernancePolicy
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function permitsImport(string $sourceCode, string $sourceVersion, string $operation, array $conditions = []): bool
    {
        return $this->permits(Capability::Import, $sourceCode, $sourceVersion, $operation, $conditions);
    }

    public function permitsFetch(string $sourceCode, string $sourceVersion, string $operation, array $conditions = []): bool
    {
        return $this->permits(Capability::LiveFetch, $sourceCode, $sourceVersion, $operation, $conditions);
    }

    /** @param list<string> $conditions */
    private function permits(Capability $capability, string $sourceCode, string $sourceVersion, string $operation, array $conditions): bool
    {
        if (! $this->configurationPermits($sourceCode, $sourceVersion) || ! (bool) config('security.emergency.imports', true)) {
            return false;
        }

        $evaluatedAt = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'))->value();

        if (! $evaluatedAt instanceof RetrievedAt) {
            return false;
        }

        $request = CapabilityRequest::create(
            $capability,
            $operation,
            $sourceCode,
            $sourceVersion,
            $evaluatedAt,
            $conditions,
        )->value();

        if (! $request instanceof CapabilityRequest) {
            return false;
        }

        $decision = $this->policy->authorize($request)->value();

        return $decision instanceof CapabilityDecision && $decision->permitsExecution();
    }

    public function permitsActivation(string $sourceCode, string $sourceVersion): bool
    {
        if (! $this->configurationPermits($sourceCode, $sourceVersion) || ! (bool) config('security.emergency.rulesets', true)) {
            return false;
        }

        $conditions = $sourceCode === 'GGG-POE1-SKILLTREE-001'
            ? ['checksum_verified', 'immutable_snapshot', 'official_repository', 'operator_workflow', 'pinned_commit', 'poe1_scope']
            : ['checksum_verified', 'immutable_snapshot', 'poe1_scope'];

        return $this->permitsImport(
            $sourceCode,
            $sourceVersion,
            'ruleset.source.activate',
            $conditions,
        );
    }

    private function configurationPermits(string $sourceCode, string $sourceVersion): bool
    {
        return match ($sourceCode) {
            'GGG-POE1-SKILLTREE-001' => (bool) config('source-governance.ggg_passive_tree.enabled', false)
                && array_key_exists($sourceVersion, (array) config('source-governance.ggg_passive_tree.approved_revisions', [])),
            'POEWIKI-CARGO-001' => (bool) config('source-governance.poewiki_import_enabled', false),
            'POENINJA-ECONOMY-001' => (bool) config('source-governance.poeninja_economy_enabled', false),
            'REPOE-CANDIDATE' => false,
            'POE2-DATASET-CANDIDATE' => false,
            'GGG-POE1-ATLASTREE-001' => false,
            default => true,
        };
    }
}

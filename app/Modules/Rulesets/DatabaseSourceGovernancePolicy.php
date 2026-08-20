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
        if (! $this->configurationPermits($sourceCode)) {
            return false;
        }

        $evaluatedAt = RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'))->value();

        if (! $evaluatedAt instanceof RetrievedAt) {
            return false;
        }

        $request = CapabilityRequest::create(
            Capability::Import,
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
        if (! $this->configurationPermits($sourceCode)) {
            return false;
        }

        return $this->permitsImport(
            $sourceCode,
            $sourceVersion,
            'ruleset.source.activate',
            ['checksum_verified', 'immutable_snapshot', 'poe1_scope'],
        );
    }

    private function configurationPermits(string $sourceCode): bool
    {
        return match ($sourceCode) {
            'POEWIKI-CARGO-001' => (bool) config('source-governance.poewiki_import_enabled', false),
            'POENINJA-ECONOMY-001' => (bool) config('source-governance.poeninja_economy_enabled', false),
            'REPOE-CANDIDATE' => false,
            'GGG-POE1-ATLASTREE-001' => false,
            default => true,
        };
    }
}

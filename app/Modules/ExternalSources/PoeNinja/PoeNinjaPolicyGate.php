<?php

namespace App\Modules\ExternalSources\PoeNinja;

use Carbon\CarbonImmutable;
use Lootwright\Application\ExternalSources\Ports\SourcePolicyGate;
use Lootwright\Domain\PolicyProvenance\Capability;
use Lootwright\Domain\PolicyProvenance\CapabilityRequest;
use Lootwright\Domain\PolicyProvenance\PolicyDecision;
use Lootwright\Domain\PolicyProvenance\Ports\CapabilityPolicy;
use Lootwright\Domain\PolicyProvenance\RetrievedAt;

final readonly class PoeNinjaPolicyGate implements SourcePolicyGate
{
    public function __construct(private CapabilityPolicy $policy) {}

    public function permits(string $operation): bool
    {
        $request = CapabilityRequest::create(Capability::LiveFetch, $operation, 'POENINJA-ECONOMY-001', 'economy-v1', RetrievedAt::from(CarbonImmutable::now('UTC')->format('Y-m-d\\TH:i:s\\Z'))->value(), ['operator_contact_configured', 'https_only', 'exact_endpoint_allowlist']);
        $decision = $request->isFailure() ? null : $this->policy->authorize($request->value())->value();

        return $decision?->decision === PolicyDecision::Allow;
    }
}

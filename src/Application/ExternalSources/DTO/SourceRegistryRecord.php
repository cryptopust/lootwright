<?php

namespace Lootwright\Application\ExternalSources\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class SourceRegistryRecord implements JsonSerializable
{
    /**
     * @param  list<GameEdition>  $editions
     * @param  list<string>  $allowedCapabilities
     * @param  list<string>  $forbiddenCapabilities
     * @param  list<array<string, mixed>>  $policyEvidence
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $sourceType,
        public array $editions,
        public ?string $referenceUrl,
        public ?string $documentationUrl,
        public ?string $termsUrl,
        public array $allowedCapabilities,
        public array $forbiddenCapabilities,
        public string $redistributionStatus,
        public string $commercialUseStatus,
        public string $cacheStorageStatus,
        public ?string $lastPolicyReviewAt,
        public array $policyEvidence,
        public bool $enabled,
        public bool $emergencyKillSwitch,
        public string $governanceStatus,
        public string $disabledReason,
        public string $technicalAccess,
        public string $licenseIdentifier,
        public string $rateLimitStatus,
        public string $authRequirements,
        public string $dataQualityStatus,
        public string $patchVersioningStatus,
        public string $updateFrequency,
        public string $provenanceStatus,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'source_type' => $this->sourceType,
            'editions' => array_map(static fn (GameEdition $edition): string => $edition->value, $this->editions),
            'reference_url' => $this->referenceUrl,
            'documentation_url' => $this->documentationUrl,
            'terms_url' => $this->termsUrl,
            'allowed_capabilities' => $this->allowedCapabilities,
            'forbidden_capabilities' => $this->forbiddenCapabilities,
            'redistribution_status' => $this->redistributionStatus,
            'commercial_use_status' => $this->commercialUseStatus,
            'cache_storage_status' => $this->cacheStorageStatus,
            'last_policy_review_at' => $this->lastPolicyReviewAt,
            'policy_evidence' => $this->policyEvidence,
            'enabled' => $this->enabled,
            'emergency_kill_switch' => $this->emergencyKillSwitch,
            'governance_status' => $this->governanceStatus,
            'disabled_reason' => $this->disabledReason,
            'technical_access' => $this->technicalAccess,
            'license_identifier' => $this->licenseIdentifier,
            'rate_limit_status' => $this->rateLimitStatus,
            'auth_requirements' => $this->authRequirements,
            'data_quality_status' => $this->dataQualityStatus,
            'patch_versioning_status' => $this->patchVersioningStatus,
            'update_frequency' => $this->updateFrequency,
            'provenance_status' => $this->provenanceStatus,
        ];
    }
}

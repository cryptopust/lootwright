<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class PermissionEvidence implements JsonSerializable
{
    private function __construct(
        public string $id,
        public string $sourceId,
        public string $sourceVersion,
        public EvidenceUrl $url,
        public RetrievedAt $retrievedAt,
        public EffectivePeriod $effectivePeriod,
        public PermissionStatus $status,
        public AttributionRequirement $attribution,
        public string $summary,
    ) {}

    public static function create(
        string $id,
        DataSourceVersion $sourceVersion,
        EvidenceUrl $url,
        RetrievedAt $retrievedAt,
        EffectivePeriod $effectivePeriod,
        PermissionStatus $status,
        AttributionRequirement $attribution,
        string $summary,
    ): DomainResult {
        $id = trim($id);
        $summary = trim($summary);

        if (preg_match('/^[A-Z][A-Z0-9-]{2,95}$/D', $id) !== 1
            || $summary === ''
            || mb_strlen($summary) > 2000
            || ! $effectivePeriod->contains($retrievedAt)
        ) {
            return DomainResult::failure(DomainError::because(
                DomainErrorCode::InvalidValue,
                'Permission evidence requires canonical identity, bounded summary, and a retrieval time inside its effective period.',
            ));
        }

        return DomainResult::success(new self(
            $id,
            $sourceVersion->sourceId,
            $sourceVersion->version,
            $url,
            $retrievedAt,
            $effectivePeriod,
            $status,
            $attribution,
            $summary,
        ));
    }

    public function isEffectiveAt(RetrievedAt $instant): bool
    {
        return $this->effectivePeriod->contains($instant)
            && $this->status === PermissionStatus::Allowed;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'source_id' => $this->sourceId,
            'source_version' => $this->sourceVersion,
            'url' => $this->url,
            'retrieved_at' => $this->retrievedAt,
            'effective_period' => $this->effectivePeriod,
            'status' => $this->status->value,
            'attribution' => $this->attribution,
            'summary' => $this->summary,
        ];
    }
}

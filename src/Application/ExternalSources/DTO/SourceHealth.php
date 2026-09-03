<?php

namespace Lootwright\Application\ExternalSources\DTO;

final readonly class SourceHealth
{
    /** @param list<string> $cachedCategories */
    public function __construct(public string $sourceKey, public bool $enabled, public string $policyDecision, public ?\DateTimeImmutable $lastSuccess, public ?\DateTimeImmutable $lastAttempt, public ?string $currentLeague, public array $cachedCategories, public ?\DateTimeImmutable $nextEligibleRefresh, public SourceFreshness $freshness, public ?string $failureCode) {}
}

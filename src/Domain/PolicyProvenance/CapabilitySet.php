<?php

namespace Lootwright\Domain\PolicyProvenance;

use JsonSerializable;
use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;

final readonly class CapabilitySet implements JsonSerializable
{
    /**
     * @param  array<string, CapabilityDecision>  $decisions
     */
    private function __construct(private array $decisions) {}

    /**
     * @param  array<array-key, mixed>  $decisions
     */
    public static function create(array $decisions): DomainResult
    {
        $indexed = [];

        foreach ($decisions as $decision) {
            if (! $decision instanceof CapabilityDecision) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::InvalidValue,
                    'A capability set accepts only capability decisions.',
                ));
            }

            $key = $decision->sourceId.':'.$decision->capability->value;

            if (isset($indexed[$key])) {
                return DomainResult::failure(DomainError::because(
                    DomainErrorCode::DuplicateValue,
                    'A capability set cannot decide the same capability twice.',
                ));
            }

            $indexed[$key] = $decision;
        }

        ksort($indexed, SORT_STRING);

        return DomainResult::success(new self($indexed));
    }

    public function decisionFor(string $sourceId, Capability $capability): CapabilityDecision
    {
        return $this->decisions[$sourceId.':'.$capability->value] ?? new CapabilityDecision(
            $capability,
            $sourceId,
            PolicyDecision::Deny,
            PolicyDecisionReason::MissingRule,
            PolicyVersion::baseline(),
            'No active policy rule exists for this source and capability.',
        );
    }

    /** @return list<CapabilityDecision> */
    public function jsonSerialize(): array
    {
        return array_values($this->decisions);
    }
}

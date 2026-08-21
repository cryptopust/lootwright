<?php

namespace Lootwright\Domain\Recommendations;

use Lootwright\Domain\Shared\Error\DomainError;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Throwable;

final readonly class DependencyResolver
{
    /** @param list<UpgradeCandidate> $candidates */
    public function resolve(array $candidates): DomainResult
    {
        if ($candidates === []) {
            return DomainResult::success([]);
        }
        usort($candidates, static fn (UpgradeCandidate $left, UpgradeCandidate $right): int => [$right->score, $left->id] <=> [$left->score, $right->id]);
        $accepted = [];
        $acceptedIds = [];
        foreach ($candidates as $candidate) {
            $conflictsWithAccepted = array_filter($candidate->conflicts, static fn (string $id): bool => isset($acceptedIds[$id])) !== [];
            $acceptedConflictsWithCandidate = array_filter(
                $accepted,
                static fn (UpgradeCandidate $acceptedCandidate): bool => in_array($candidate->id, $acceptedCandidate->conflicts, true),
            ) !== [];
            if ($conflictsWithAccepted || $acceptedConflictsWithCandidate) {
                continue;
            }
            $accepted[] = $candidate;
            $acceptedIds[$candidate->id] = true;
        }
        do {
            $before = count($accepted);
            $ids = array_fill_keys(array_map(static fn (UpgradeCandidate $candidate): string => $candidate->id, $accepted), true);
            $accepted = array_values(array_filter($accepted, static fn (UpgradeCandidate $candidate): bool => array_filter($candidate->prerequisites, static fn (string $id): bool => ! isset($ids[$id])) === []));
        } while (count($accepted) !== $before);

        if ($accepted === []) {
            return DomainResult::success([]);
        }

        try {
            return DomainResult::success((new UpgradeGraph($accepted[0]->gameEdition, $accepted[0]->ruleset, $accepted))->ordered());
        } catch (Throwable) {
            return DomainResult::failure(DomainError::because(DomainErrorCode::CircularDependency, 'Upgrade dependencies contain a cycle.'));
        }
    }
}

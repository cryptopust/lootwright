<?php

namespace Lootwright\Domain\Recommendations;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class UpgradeGraph implements JsonSerializable
{
    /** @param list<UpgradeCandidate> $candidates
     * @param  list<UpgradeCandidate>  $impossibleCandidates
     */
    public function __construct(
        public GameEdition $gameEdition,
        public RulesetReference $ruleset,
        public array $candidates,
        public array $impossibleCandidates = [],
    ) {
        if ($gameEdition !== $ruleset->edition) {
            throw new InvalidArgumentException('An upgrade graph must be edition-scoped.');
        }
        $ids = [];
        foreach ([...$candidates, ...$impossibleCandidates] as $candidate) {
            if ($candidate->gameEdition !== $gameEdition || isset($ids[$candidate->id])) {
                throw new InvalidArgumentException('Upgrade graph candidates must be typed, edition-scoped, and unique.');
            }
            $ids[$candidate->id] = true;
        }
        foreach ($candidates as $candidate) {
            foreach ($candidate->prerequisites as $relation) {
                if (! isset($ids[$relation])) {
                    throw new InvalidArgumentException('Upgrade graph relation references an unknown candidate.');
                }
            }
        }
    }

    /** @return list<UpgradeCandidate> */
    public function ordered(): array
    {
        $remaining = array_combine(array_map(static fn (UpgradeCandidate $candidate): string => $candidate->id, $this->candidates), $this->candidates) ?: [];
        $ordered = [];
        while ($remaining !== []) {
            $ready = array_filter($remaining, static function (UpgradeCandidate $candidate) use ($ordered): bool {
                $done = array_fill_keys(array_map(static fn (UpgradeCandidate $item): string => $item->id, $ordered), true);

                return count(array_filter($candidate->prerequisites, static fn (string $id): bool => ! isset($done[$id]))) === 0;
            });
            if ($ready === []) {
                throw new InvalidArgumentException('Upgrade graph contains a circular dependency.');
            }
            usort($ready, static fn (UpgradeCandidate $left, UpgradeCandidate $right): int => [$right->score, $left->id] <=> [$left->score, $right->id]);
            $next = $ready[0];
            $ordered[] = $next;
            unset($remaining[$next->id]);
        }

        return $ordered;
    }

    /** @return array<string,string> */
    public function orderingReasons(): array
    {
        $ordered = $this->ordered();
        $reasons = [];
        foreach ($ordered as $index => $candidate) {
            if ($index === 0) {
                $reasons[$candidate->id] = 'First available node after prerequisites, with the highest deterministic score; canonical ID breaks ties.';

                continue;
            }
            $earlier = $ordered[$index - 1];
            $reasons[$candidate->id] = in_array($earlier->id, $candidate->prerequisites, true)
                ? $earlier->id.' precedes this node because it is an explicit prerequisite.'
                : $earlier->id.' precedes this node by deterministic score, then canonical ID tie-break.';
        }

        return $reasons;
    }

    /** @return array<string,mixed> */
    public function jsonSerialize(): array
    {
        return [
            'game_edition' => $this->gameEdition->value,
            'ruleset_version' => $this->ruleset->version,
            'candidates' => $this->ordered(),
            'ordering_reasons' => $this->orderingReasons(),
            'impossible_candidates' => $this->impossibleCandidates,
        ];
    }
}

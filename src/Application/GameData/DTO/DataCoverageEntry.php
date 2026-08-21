<?php

namespace Lootwright\Application\GameData\DTO;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class DataCoverageEntry implements JsonSerializable
{
    public function __construct(
        public GameEdition $edition,
        public string $category,
        public ?string $rulesetVersion,
        public int $observedRecords,
        public ?int $expectedRecords,
        public ?int $coverageBasisPoints,
        public string $status,
    ) {}

    /** @return array<string, float|int|string|null> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'category' => $this->category,
            'ruleset_version' => $this->rulesetVersion,
            'observed_records' => $this->observedRecords,
            'expected_records' => $this->expectedRecords,
            'coverage_basis_points' => $this->coverageBasisPoints,
            'coverage_percent' => $this->coverageBasisPoints === null ? null : round($this->coverageBasisPoints / 100, 2),
            'status' => $this->status,
        ];
    }
}

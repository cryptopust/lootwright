<?php

namespace Lootwright\Domain\BuildIntake\Import;

use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;

final readonly class CanonicalImportedBuild implements JsonSerializable
{
    /**
     * @param  array<string, string>  $choices
     * @param  list<string>  $passiveNodeIds
     * @param  list<array<string, mixed>>  $skills
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, bool|int|string>  $configuration
     * @param  array<string, int|string>  $summaryValues
     */
    public function __construct(
        public GameEdition $edition,
        public ?string $buildVersion,
        public ?int $characterLevel,
        public ?string $characterClassId,
        public ?string $ascendancyId,
        public array $choices,
        public array $passiveNodeIds,
        public array $skills,
        public array $items,
        public array $configuration,
        public array $summaryValues,
        public string $notes,
        public bool $beta,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'edition' => $this->edition->value,
            'build_version' => $this->buildVersion,
            'character_level' => $this->characterLevel,
            'character_class_id' => $this->characterClassId,
            'ascendancy_id' => $this->ascendancyId,
            'choices' => $this->choices,
            'passive_node_ids' => $this->passiveNodeIds,
            'skills' => $this->skills,
            'items' => $this->items,
            'configuration' => $this->configuration,
            'summary_values' => $this->summaryValues,
            'notes_untrusted_text' => $this->notes,
            'beta' => $this->beta,
        ];
    }
}

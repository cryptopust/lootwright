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
     * @param  array<string, int|string>  $attributes
     * @param  array<string, int|string>  $resistances
     * @param  list<array<string, mixed>>  $supports
     * @param  list<array<string, mixed>>  $auras
     * @param  list<array<string, mixed>>  $itemModifiers
     * @param  list<string>  $keystones
     * @param  list<array<string, mixed>>  $jewels
     * @param  list<array<string, mixed>>  $clusters
     * @param  array<string, PropertySupportStatus>  $propertySupport
     * @param  list<UnsupportedFeature>  $unsupportedFields
     * @param  list<ImportWarning>  $warnings
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
        public array $attributes = [],
        public int|string|null $life = null,
        public int|string|null $energyShield = null,
        public int|string|null $mana = null,
        public int|string|null $armour = null,
        public int|string|null $evasion = null,
        public array $resistances = [],
        public array $supports = [],
        public array $auras = [],
        public array $itemModifiers = [],
        public array $keystones = [],
        public array $jewels = [],
        public array $clusters = [],
        public array $propertySupport = [],
        public array $unsupportedFields = [],
        public array $warnings = [],
        public ?BuildSourceMetadata $sourceMetadata = null,
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
            'attributes' => $this->attributes,
            'life' => $this->life,
            'energy_shield' => $this->energyShield,
            'mana' => $this->mana,
            'armour' => $this->armour,
            'evasion' => $this->evasion,
            'resistances' => $this->resistances,
            'supports' => $this->supports,
            'auras' => $this->auras,
            'item_modifiers' => $this->itemModifiers,
            'keystones' => $this->keystones,
            'jewels' => $this->jewels,
            'clusters' => $this->clusters,
            'property_support' => array_map(
                static fn (PropertySupportStatus $status): string => $status->value,
                $this->propertySupport,
            ),
            'unsupported_fields' => $this->unsupportedFields,
            'warnings' => $this->warnings,
            'source_metadata' => $this->sourceMetadata,
        ];
    }
}

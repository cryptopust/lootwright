<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;

final readonly class GenericCanonicalEntity extends CanonicalGameEntity
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        GameEdition $edition,
        string $rulesetVersionId,
        string $externalId,
        ?string $displayName,
        SourceProvenanceReference $provenance,
        private CanonicalEntityType $entityType,
        array $attributes = [],
    ) {
        if (in_array($entityType, [
            CanonicalEntityType::CharacterClass,
            CanonicalEntityType::Ascendancy,
            CanonicalEntityType::PassiveNode,
            CanonicalEntityType::Keystone,
            CanonicalEntityType::SkillGem,
            CanonicalEntityType::SupportGem,
            CanonicalEntityType::ItemBase,
            CanonicalEntityType::UniqueItem,
            CanonicalEntityType::ModifierDefinition,
            CanonicalEntityType::StatDefinition,
            CanonicalEntityType::ContentGoalDefinition,
        ], true)) {
            throw new InvalidArgumentException('A typed canonical entity must use its dedicated class.');
        }

        parent::__construct($edition, $rulesetVersionId, $externalId, $displayName, $provenance, $attributes);
    }

    public function type(): CanonicalEntityType
    {
        return $this->entityType;
    }
}

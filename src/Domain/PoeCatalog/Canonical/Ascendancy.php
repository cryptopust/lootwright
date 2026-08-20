<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

use InvalidArgumentException;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;

final readonly class Ascendancy extends CanonicalGameEntity
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        GameEdition $edition,
        string $rulesetVersionId,
        string $externalId,
        ?string $displayName,
        SourceProvenanceReference $provenance,
        public string $characterClassExternalId,
        public string $progressionType = 'regular',
        public ?string $baseAscendancyExternalId = null,
        array $attributes = [],
    ) {
        parent::__construct($edition, $rulesetVersionId, $externalId, $displayName, $provenance, $attributes);

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $characterClassExternalId) !== 1
            || ! in_array($progressionType, ['regular', 'alternate', 'secondary'], true)
            || ($progressionType === 'alternate' && $baseAscendancyExternalId === null)
            || ($progressionType !== 'alternate' && $baseAscendancyExternalId !== null)
            || ($baseAscendancyExternalId !== null && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $baseAscendancyExternalId) !== 1)
        ) {
            throw new InvalidArgumentException('Canonical Ascendancy relationships are invalid.');
        }
    }

    public function type(): CanonicalEntityType
    {
        return CanonicalEntityType::Ascendancy;
    }

    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'character_class_external_id' => $this->characterClassExternalId,
            'progression_type' => $this->progressionType,
            'base_ascendancy_external_id' => $this->baseAscendancyExternalId,
        ];
    }
}

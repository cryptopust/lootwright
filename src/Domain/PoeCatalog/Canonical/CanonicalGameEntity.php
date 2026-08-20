<?php

namespace Lootwright\Domain\PoeCatalog\Canonical;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;

abstract readonly class CanonicalGameEntity implements JsonSerializable
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        public GameEdition $edition,
        public string $rulesetVersionId,
        public string $externalId,
        public ?string $displayName,
        public SourceProvenanceReference $provenance,
        public array $attributes = [],
    ) {
        if ($provenance->edition !== $edition
            || preg_match('/^[0-9a-f-]{36}$/D', $rulesetVersionId) !== 1
            || preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $externalId) !== 1
            || ($displayName !== null && (trim($displayName) === '' || mb_strlen($displayName) > 255))
        ) {
            throw new InvalidArgumentException('Canonical game entity identity or provenance is invalid.');
        }
    }

    abstract public function type(): CanonicalEntityType;

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'entity_type' => $this->type()->value,
            'edition' => $this->edition->value,
            'ruleset_version_id' => $this->rulesetVersionId,
            'external_id' => $this->externalId,
            'display_name' => $this->displayName,
            'attributes' => $this->attributes,
            'provenance' => $this->provenance,
        ];
    }
}

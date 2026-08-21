<?php

namespace Lootwright\Application\GameData\DTO;

use InvalidArgumentException;
use JsonSerializable;
use Lootwright\Domain\PoeCatalog\Canonical\Ascendancy;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalGameEntity;
use Lootwright\Domain\PoeCatalog\Canonical\CharacterClass;
use Lootwright\Domain\PoeCatalog\Canonical\ContentGoalDefinition;
use Lootwright\Domain\PoeCatalog\Canonical\GenericCanonicalEntity;
use Lootwright\Domain\PoeCatalog\Canonical\ItemBase;
use Lootwright\Domain\PoeCatalog\Canonical\Keystone;
use Lootwright\Domain\PoeCatalog\Canonical\ModifierDefinition;
use Lootwright\Domain\PoeCatalog\Canonical\PassiveNode;
use Lootwright\Domain\PoeCatalog\Canonical\SkillGem;
use Lootwright\Domain\PoeCatalog\Canonical\StatDefinition;
use Lootwright\Domain\PoeCatalog\Canonical\SupportGem;
use Lootwright\Domain\PoeCatalog\Canonical\UniqueItem;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

final readonly class NormalizedGameDataRecord implements JsonSerializable
{
    /** @param array<string, mixed> $attributes */
    public function __construct(
        public GameEdition $edition,
        public CanonicalEntityType $category,
        public string $externalId,
        public ?string $displayName,
        public SourceProvenanceReference $provenance,
        public array $attributes,
        public string $checksumSha256,
    ) {
        if ($provenance->edition !== $edition
            || preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,190}$/D', $externalId) !== 1
            || ($displayName !== null && (trim($displayName) === '' || mb_strlen($displayName) > 255))
            || preg_match('/^[0-9a-f]{64}$/D', $checksumSha256) !== 1
            || ! hash_equals($checksumSha256, hash('sha256', CanonicalJson::encode($this->canonicalPayload())))
        ) {
            throw new InvalidArgumentException('Normalized game-data record is invalid.');
        }
    }

    public function toEntity(string $rulesetVersionId): CanonicalGameEntity
    {
        $arguments = [$this->edition, $rulesetVersionId, $this->externalId, $this->displayName, $this->provenance];

        return match ($this->category) {
            CanonicalEntityType::Ascendancy => new Ascendancy(
                ...$arguments,
                characterClassExternalId: $this->requiredAttribute('character_class_external_id'),
                progressionType: $this->stringAttribute('progression_type') ?? 'regular',
                baseAscendancyExternalId: $this->stringAttribute('base_ascendancy_external_id'),
                attributes: $this->attributes,
            ),
            CanonicalEntityType::CharacterClass => new CharacterClass(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::PassiveNode => new PassiveNode(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::Keystone => new Keystone(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::SkillGem => new SkillGem(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::SupportGem => new SupportGem(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::ItemBase => new ItemBase(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::UniqueItem => new UniqueItem(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::ModifierDefinition => new ModifierDefinition(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::StatDefinition => new StatDefinition(...$arguments, attributes: $this->attributes),
            CanonicalEntityType::ContentGoalDefinition => new ContentGoalDefinition(...$arguments, attributes: $this->attributes),
            default => new GenericCanonicalEntity(...$arguments, entityType: $this->category, attributes: $this->attributes),
        };
    }

    /** @return array<string, mixed> */
    public function canonicalPayload(): array
    {
        return [
            'attributes' => $this->attributes,
            'category' => $this->category->value,
            'display_name' => $this->displayName,
            'edition' => $this->edition->value,
            'external_id' => $this->externalId,
            'provenance' => $this->provenance,
        ];
    }

    public function factChecksumSha256(): string
    {
        return hash('sha256', CanonicalJson::encode([
            'attributes' => $this->attributes,
            'category' => $this->category->value,
            'display_name' => $this->displayName,
            'edition' => $this->edition->value,
            'external_id' => $this->externalId,
        ]));
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [...$this->canonicalPayload(), 'checksum_sha256' => $this->checksumSha256];
    }

    private function requiredAttribute(string $key): string
    {
        return $this->stringAttribute($key)
            ?? throw new InvalidArgumentException("Normalized {$this->category->value} is missing {$key}.");
    }

    private function stringAttribute(string $key): ?string
    {
        $value = $this->attributes[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }
}

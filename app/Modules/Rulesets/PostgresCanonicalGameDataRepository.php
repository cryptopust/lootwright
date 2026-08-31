<?php

namespace App\Modules\Rulesets;

use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\DB;
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
use Lootwright\Domain\PoeCatalog\Ports\GameDataRepository;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use RuntimeException;

final class PostgresCanonicalGameDataRepository implements GameDataRepository
{
    public function __construct(private readonly CacheRepository $cache) {}

    public function find(GameEdition $edition, string $rulesetVersionId, CanonicalEntityType $type, string $externalId): ?CanonicalGameEntity
    {
        $key = 'canonical-entity:v1:'.hash('sha256', implode('|', [$edition->value, $rulesetVersionId, $type->value, $externalId]));

        return $this->cache->remember($key, now()->addSeconds((int) config('performance.canonical_cache_seconds', 3600)), function () use ($edition, $rulesetVersionId, $type, $externalId): ?CanonicalGameEntity {
            $row = $this->query($edition, $rulesetVersionId)
                ->where('data.entity_type', $type->value)
                ->where('data.external_id', $externalId)
                ->first();

            return $row === null ? null : $this->hydrate($row);
        });
    }

    public function listForRuleset(GameEdition $edition, string $rulesetVersionId, ?CanonicalEntityType $type = null): array
    {
        $query = $this->query($edition, $rulesetVersionId);
        if ($type !== null) {
            $query->where('data.entity_type', $type->value);
        }

        return array_values($query->orderBy('data.entity_type')->orderBy('data.external_id')->get()
            ->map(fn (object $row): CanonicalGameEntity => $this->hydrate($row))->all());
    }

    private function query(GameEdition $edition, string $rulesetVersionId): Builder
    {
        return DB::table('canonical_game_data as data')
            ->join('source_snapshots as snapshots', 'snapshots.id', '=', 'data.source_snapshot_id')
            ->join('policy_data_source_versions as versions', 'versions.id', '=', 'snapshots.source_version_id')
            ->where('data.game_edition', $edition->value)
            ->where('data.ruleset_version_id', $rulesetVersionId)
            ->select(['data.*', 'snapshots.id as source_snapshot_id', 'snapshots.source_code', 'snapshots.checksum_sha256 as source_checksum', 'snapshots.retrieved_at as source_imported_at', 'versions.version as source_version']);
    }

    private function hydrate(object $row): CanonicalGameEntity
    {
        $data = get_object_vars($row);
        $payload = json_decode($this->string($data, 'payload'), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($payload)
            || ! hash_equals($this->string($data, 'payload_checksum_sha256'), hash('sha256', CanonicalJson::encode($payload)))
        ) {
            throw new RuntimeException('Canonical data payload checksum verification failed.');
        }
        $edition = GameEdition::from($this->string($data, 'game_edition'));
        $provenance = new SourceProvenanceReference(
            $edition,
            $this->string($data, 'source_code'),
            $this->string($data, 'source_version'),
            $this->string($data, 'source_checksum'),
            $this->string($data, 'source_snapshot_id'),
            new \DateTimeImmutable($this->string($data, 'source_imported_at')),
        );
        $arguments = [$edition, $this->string($data, 'ruleset_version_id'), $this->string($data, 'external_id'), ($data['display_name'] ?? null) === null ? null : $this->string($data, 'display_name'), $provenance];
        $attributes = is_array($payload['attributes'] ?? null) ? $payload['attributes'] : [];

        return match (CanonicalEntityType::from($this->string($data, 'entity_type'))) {
            CanonicalEntityType::Ascendancy => new Ascendancy(...$arguments, characterClassExternalId: (string) $payload['character_class_external_id'], progressionType: (string) ($payload['progression_type'] ?? 'regular'), baseAscendancyExternalId: is_string($payload['base_ascendancy_external_id'] ?? null) ? $payload['base_ascendancy_external_id'] : null, attributes: $attributes),
            CanonicalEntityType::CharacterClass => new CharacterClass(...$arguments, attributes: $attributes),
            CanonicalEntityType::PassiveNode => new PassiveNode(...$arguments, attributes: $attributes),
            CanonicalEntityType::Keystone => new Keystone(...$arguments, attributes: $attributes),
            CanonicalEntityType::SkillGem => new SkillGem(...$arguments, attributes: $attributes),
            CanonicalEntityType::SupportGem => new SupportGem(...$arguments, attributes: $attributes),
            CanonicalEntityType::ItemBase => new ItemBase(...$arguments, attributes: $attributes),
            CanonicalEntityType::UniqueItem => new UniqueItem(...$arguments, attributes: $attributes),
            CanonicalEntityType::ModifierDefinition => new ModifierDefinition(...$arguments, attributes: $attributes),
            CanonicalEntityType::StatDefinition => new StatDefinition(...$arguments, attributes: $attributes),
            CanonicalEntityType::ContentGoalDefinition => new ContentGoalDefinition(...$arguments, attributes: $attributes),
            default => new GenericCanonicalEntity(...$arguments, entityType: CanonicalEntityType::from($this->string($data, 'entity_type')), attributes: $attributes),
        };
    }

    /** @param array<string, mixed> $data */
    private function string(array $data, string $key): string
    {
        if (! is_string($data[$key] ?? null)) {
            throw new RuntimeException("Expected string database field {$key}.");
        }

        return $data[$key];
    }
}

<?php

namespace Lootwright\GameAdapters\Shared\GameData;

use DomainException;
use Lootwright\Application\GameData\DTO\GameDataSourceDocument;
use Lootwright\Application\GameData\DTO\NormalizedGameDataRecord;
use Lootwright\Application\GameData\DTO\NormalizedGameDataset;
use Lootwright\Application\GameData\Ports\GameDataNormalizer;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

abstract class AbstractGameDataNormalizer implements GameDataNormalizer
{
    final public function normalize(GameDataSourceDocument $document): NormalizedGameDataset
    {
        if ($document->edition !== $this->edition() || $document->schemaVersion !== $this->schemaVersion()) {
            throw new DomainException('The source document schema or edition does not match this normalizer.');
        }
        if ($document->approvalStatus !== 'approved') {
            throw new DomainException('Only an approved source snapshot may be normalized as canonical game data.');
        }

        $provenance = new SourceProvenanceReference(
            $document->edition,
            $document->sourceCode,
            $document->sourceVersion,
            $document->sourceChecksumSha256,
            $document->sourceSnapshotId,
            $document->importedAt,
        );
        $records = [];
        $identities = [];

        foreach ($document->records as $raw) {
            if (array_diff(array_keys($raw), ['category', 'external_id', 'display_name', 'attributes']) !== []) {
                throw new DomainException('A source record contains fields outside the canonical import envelope.');
            }
            $categoryValue = $raw['category'] ?? null;
            $externalId = $raw['external_id'] ?? null;
            $displayName = $raw['display_name'] ?? null;
            $attributes = $raw['attributes'] ?? [];
            if (! is_string($categoryValue)
                || ! CanonicalEntityType::tryFrom($categoryValue) instanceof CanonicalEntityType
                || ! is_string($externalId)
                || ($displayName !== null && ! is_string($displayName))
                || ! is_array($attributes)
                || array_is_list($attributes)
            ) {
                throw new DomainException('A source record does not match the canonical import envelope.');
            }
            if (isset($attributes['edition']) && $attributes['edition'] !== $document->edition->value) {
                throw new DomainException('A source record contains a cross-edition attribute.');
            }
            $this->assertSafeValue($attributes);
            $category = CanonicalEntityType::from($categoryValue);
            $payload = [
                'attributes' => $attributes,
                'category' => $category->value,
                'display_name' => $displayName,
                'edition' => $document->edition->value,
                'external_id' => $externalId,
                'provenance' => $provenance,
            ];
            $record = new NormalizedGameDataRecord(
                $document->edition,
                $category,
                $externalId,
                $displayName,
                $provenance,
                $attributes,
                hash('sha256', CanonicalJson::encode($payload)),
            );
            $identity = $category->value."\0".$externalId;
            if (isset($identities[$identity]) && ! hash_equals($identities[$identity], $record->checksumSha256)) {
                throw new DomainException('A source document contains contradictory duplicate records.');
            }
            if (! isset($identities[$identity])) {
                $records[] = $record;
                $identities[$identity] = $record->checksumSha256;
            }
        }

        usort($records, static fn (NormalizedGameDataRecord $left, NormalizedGameDataRecord $right): int => [
            $left->category->value,
            $left->externalId,
        ] <=> [$right->category->value, $right->externalId]);

        return new NormalizedGameDataset(
            $document->edition,
            $document->schemaVersion,
            $document->sourceSnapshotId,
            $records,
            hash('sha256', CanonicalJson::encode($records)),
        );
    }

    abstract protected function schemaVersion(): string;

    private function assertSafeValue(mixed $value, int $depth = 0): void
    {
        if ($depth > 16) {
            throw new DomainException('Canonical game-data attributes exceed the nesting limit.');
        }
        if (is_string($value) && mb_strlen($value) > 8_192) {
            throw new DomainException('Canonical game-data attributes contain an oversized string.');
        }
        if (! is_array($value)) {
            return;
        }
        if (count($value) > 2_000 || strlen(CanonicalJson::encode($value)) > 65_536) {
            throw new DomainException('Canonical game-data attributes exceed the record limit.');
        }
        foreach ($value as $key => $child) {
            if (is_string($key) && (mb_strlen($key) > 128 || preg_match('/[\x00-\x1F\x7F]/u', $key) === 1)) {
                throw new DomainException('Canonical game-data attributes contain an invalid key.');
            }
            $this->assertSafeValue($child, $depth + 1);
        }
    }
}

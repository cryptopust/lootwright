<?php

namespace Tests\Unit\Application;

use DateTimeImmutable;
use DomainException;
use Lootwright\Application\GameData\DTO\GameDataSourceDocument;
use Lootwright\Application\GameData\DTO\SourceAuthorityCandidate;
use Lootwright\Application\GameData\SourceAuthorityResolver;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\PoE1\GameData\Poe1GameDataNormalizer;
use Lootwright\GameAdapters\PoE2\GameData\Poe2GameDataNormalizer;
use PHPUnit\Framework\TestCase;

final class GameDataNormalizationTest extends TestCase
{
    private const SNAPSHOT = '019c1234-5678-7abc-8def-0123456789ab';

    public function test_edition_specific_normalizers_reject_cross_edition_documents(): void
    {
        $document = $this->document(GameEdition::Poe1, 'lootwright.poe1.game-data.v1');
        $dataset = (new Poe1GameDataNormalizer)->normalize($document);

        self::assertSame(GameEdition::Poe1, $dataset->edition);
        self::assertSame('class:fixture', $dataset->records[0]->externalId);

        $this->expectException(DomainException::class);
        (new Poe2GameDataNormalizer)->normalize($document);
    }

    public function test_normalization_is_byte_stable_and_rejects_contradictory_duplicates(): void
    {
        $normalizer = new Poe1GameDataNormalizer;
        $document = $this->document(GameEdition::Poe1, 'lootwright.poe1.game-data.v1');

        self::assertSame(
            $normalizer->normalize($document)->checksumSha256,
            $normalizer->normalize($document)->checksumSha256,
        );

        $records = $document->records;
        $records[] = [...$records[0], 'display_name' => 'Contradiction'];
        $this->expectException(DomainException::class);
        $normalizer->normalize(new GameDataSourceDocument(
            $document->edition,
            $document->schemaVersion,
            $document->sourceCode,
            $document->sourceVersion,
            $document->sourceSnapshotId,
            $document->sourceChecksumSha256,
            $document->importedAt,
            'approved',
            $records,
        ));
    }

    public function test_authority_resolver_requires_consensus_before_precedence_selection(): void
    {
        $first = (new Poe1GameDataNormalizer)->normalize(
            $this->document(GameEdition::Poe1, 'lootwright.poe1.game-data.v1', 'SOURCE-ONE'),
        )->records[0];
        $second = (new Poe1GameDataNormalizer)->normalize(
            $this->document(GameEdition::Poe1, 'lootwright.poe1.game-data.v1', 'SOURCE-TWO'),
        )->records[0];
        $resolver = new SourceAuthorityResolver([]);
        $consensus = $resolver->resolve([
            new SourceAuthorityCandidate($second, 'trusted_community'),
            new SourceAuthorityCandidate($first, 'official_structured'),
        ]);

        self::assertFalse($consensus->conflict);
        self::assertSame('SOURCE-ONE', $consensus->selected?->provenance->sourceCode);

        $different = (new Poe1GameDataNormalizer)->normalize(
            $this->document(GameEdition::Poe1, 'lootwright.poe1.game-data.v1', 'SOURCE-THREE', 'Different'),
        )->records[0];
        $conflict = $resolver->resolve([
            new SourceAuthorityCandidate($first, 'official_structured'),
            new SourceAuthorityCandidate($different, 'approved_upstream'),
        ]);

        self::assertTrue($conflict->conflict);
        self::assertNull($conflict->selected);
    }

    private function document(
        GameEdition $edition,
        string $schema,
        string $source = 'SOURCE-ONE',
        string $displayName = 'Fixture class',
    ): GameDataSourceDocument {
        return new GameDataSourceDocument(
            $edition,
            $schema,
            $source,
            'fixture-1',
            self::SNAPSHOT,
            str_repeat('a', 64),
            new DateTimeImmutable('2026-08-21T00:00:00Z'),
            'approved',
            [[
                'category' => 'character_class',
                'external_id' => 'class:fixture',
                'display_name' => $displayName,
                'attributes' => ['edition' => $edition->value],
            ]],
        );
    }
}

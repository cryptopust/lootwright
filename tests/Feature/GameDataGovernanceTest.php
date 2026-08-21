<?php

namespace Tests\Feature;

use App\Modules\ExternalSources\DatabaseSourceUpdateObserver;
use DateTimeImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Lootwright\Application\GameData\AssembleCanonicalGameData;
use Lootwright\Application\GameData\DTO\GameDataSourceDocument;
use Lootwright\Application\GameData\Ports\DataCoverageReporter;
use Lootwright\Application\GameData\Ports\SourceAuthorityRegistry;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\GameAdapters\PoE1\GameData\Poe1GameDataNormalizer;
use Tests\TestCase;

final class GameDataGovernanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_only_reviewed_category_authorities_are_enabled(): void
    {
        $registry = $this->app->make(SourceAuthorityRegistry::class);

        self::assertSame('official_structured', $registry->tier(
            GameEdition::Poe1,
            CanonicalEntityType::PassiveNode,
            'GGG-POE1-SKILLTREE-001',
        ));
        self::assertNull($registry->tier(
            GameEdition::Poe2,
            CanonicalEntityType::PassiveNode,
            'GGG-POE1-SKILLTREE-001',
        ));
        self::assertNull($registry->tier(
            GameEdition::Poe1,
            CanonicalEntityType::ModifierDefinition,
            'REPOE-CANDIDATE',
        ));
    }

    public function test_no_active_ruleset_reports_zero_observed_and_unknown_denominator(): void
    {
        $coverage = $this->app->make(DataCoverageReporter::class)->forEdition(GameEdition::Poe2);

        self::assertNotEmpty($coverage);
        foreach ($coverage as $entry) {
            self::assertSame(0, $entry->observedRecords);
            self::assertNull($entry->expectedRecords);
            self::assertNull($entry->coverageBasisPoints);
            self::assertSame('unavailable', $entry->status);
        }
    }

    public function test_update_observations_are_checksum_based_append_only_records(): void
    {
        $observer = new DatabaseSourceUpdateObserver;
        $checksum = str_repeat('a', 64);
        $observer->record('GGG-POE1-SKILLTREE-001', GameEdition::Poe1, 'fixture', null, $checksum, 'changed_staged');
        $observer->record('GGG-POE1-SKILLTREE-001', GameEdition::Poe1, 'fixture', $checksum, $checksum, 'unchanged');

        $this->assertDatabaseCount('source_update_observations', 2);
        self::assertSame(
            ['changed_staged', 'unchanged'],
            DB::table('source_update_observations')->orderBy('checked_at')->orderBy('created_at')->pluck('status')->all(),
        );
        $this->expectException(\Throwable::class);
        DB::table('source_update_observations')->update(['status' => 'failed']);
    }

    public function test_contradictory_authorized_sources_are_quarantined_without_selection(): void
    {
        DB::table('game_data_source_authorities')->insert([
            'game_edition' => 'poe1',
            'data_category' => 'character_class',
            'source_code' => 'POB-GAME-DATA-CANDIDATE',
            'authority_tier' => 'approved_upstream',
            'priority' => 2,
            'enabled' => true,
            'reviewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $normalizer = new Poe1GameDataNormalizer;
        $official = $normalizer->normalize($this->document('GGG-POE1-SKILLTREE-001', 'Official'));
        $candidate = $normalizer->normalize($this->document('POB-GAME-DATA-CANDIDATE', 'Contradiction'));

        $selected = $this->app->make(AssembleCanonicalGameData::class)->assemble(
            GameEdition::Poe1,
            [$official, $candidate],
        );

        self::assertSame([], $selected);
        $this->assertDatabaseHas('canonical_data_conflicts', [
            'game_edition' => 'poe1',
            'data_category' => 'character_class',
            'external_id' => 'class:fixture',
            'status' => 'quarantined',
        ]);
    }

    public function test_source_without_category_authority_is_rejected(): void
    {
        $dataset = (new Poe1GameDataNormalizer)->normalize(
            $this->document('POB-GAME-DATA-CANDIDATE', 'Candidate'),
        );

        $this->expectException(DomainException::class);
        $this->app->make(AssembleCanonicalGameData::class)->assemble(GameEdition::Poe1, [$dataset]);
    }

    private function document(string $sourceCode, string $displayName): GameDataSourceDocument
    {
        return new GameDataSourceDocument(
            GameEdition::Poe1,
            'lootwright.poe1.game-data.v1',
            $sourceCode,
            'fixture-1',
            $sourceCode === 'GGG-POE1-SKILLTREE-001'
                ? '019c1234-5678-7abc-8def-0123456789ab'
                : '019c1234-5678-7abc-8def-0123456789ac',
            str_repeat($sourceCode === 'GGG-POE1-SKILLTREE-001' ? 'a' : 'b', 64),
            new DateTimeImmutable('2026-08-21T00:00:00Z'),
            'approved',
            [[
                'category' => 'character_class',
                'external_id' => 'class:fixture',
                'display_name' => $displayName,
                'attributes' => ['edition' => 'poe1'],
            ]],
        );
    }
}

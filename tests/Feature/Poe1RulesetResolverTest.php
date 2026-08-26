<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Lootwright\Domain\PoeCatalog\Canonical\CanonicalEntityType;
use Lootwright\Domain\Rulesets\Ports\RulesetResolver;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\GameAdapters\PoE1\Rulesets\Poe1CanonicalResolver;
use Lootwright\GameAdapters\PoE1\Rulesets\Poe1RulesetLoader;
use Tests\TestCase;

final class Poe1RulesetResolverTest extends TestCase
{
    use RefreshDatabase;

    private const REVISION = '8bd138b32ea2631455cac5935bfab089f826094f';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $fixture = base_path('tests/Fixtures/ggg/passive-tree-8bd138b-reduced.json');
        Config::set('source-governance.ggg_passive_tree.enabled', true);
        Config::set('source-governance.ggg_passive_tree.contact', 'operator@example.test');
        Config::set('source-governance.ggg_passive_tree.approved_revisions.'.self::REVISION.'.source_checksum_sha256', hash_file('sha256', $fixture));
        self::assertSame(0, Artisan::call('poe:import-passive-tree', ['--file' => $fixture, '--activate' => true]));
    }

    public function test_resolvers_use_active_canonical_records_and_keep_unknowns_explicit(): void
    {
        $identity = app(RulesetResolver::class)->resolve(
            GameEdition::Poe1,
            PatchVersion::from(GameEdition::Poe1, '3.29.1')->value(),
            null,
            ParserVersion::from(GameEdition::Poe1, '1.0.0')->value(),
        )->value();
        self::assertInstanceOf(RulesetIdentity::class, $identity);
        $ruleset = app(Poe1RulesetLoader::class)->load($identity);
        $resolver = new Poe1CanonicalResolver($ruleset);

        self::assertNotNull($resolver->characterClass('poe1.pob.class.ranger'));
        self::assertNotNull($resolver->ascendancy('poe1.pob.ascendancy.deadeye'));
        self::assertNotNull($resolver->resolve(CanonicalEntityType::PassiveNode, 'poe1.pob.node.58556'));
        self::assertNull($resolver->characterClass('poe1.pob.class.fixture'));
        self::assertSame(7, $ruleset->coverage[CanonicalEntityType::CharacterClass->value]);
    }
}

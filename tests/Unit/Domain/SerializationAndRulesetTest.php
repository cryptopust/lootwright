<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\PoeCatalog\Identifier\ItemBaseId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemId;
use Lootwright\Domain\PoeCatalog\Identifier\ItemSlotId;
use Lootwright\Domain\PoeCatalog\Identifier\ModifierId;
use Lootwright\Domain\PoeCatalog\Identifier\SkillId;
use Lootwright\Domain\PoeCatalog\Item\CanonicalItem;
use Lootwright\Domain\PoeCatalog\Item\ItemRarity;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\DataProvenance;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Recommendations\RecommendationImpact;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Version\SourceVersion;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

class SerializationAndRulesetTest extends TestCase
{
    public function test_canonical_serialization_is_byte_stable_and_sorts_maps(): void
    {
        $first = DomainFixtures::value(RecommendationImpact::create([
            'zeta' => 100,
            'alpha' => -50,
        ]), RecommendationImpact::class);
        $second = DomainFixtures::value(RecommendationImpact::create([
            'alpha' => -50,
            'zeta' => 100,
        ]), RecommendationImpact::class);

        self::assertSame(CanonicalJson::encode($first), CanonicalJson::encode($second));
        self::assertSame('{"alpha":-50,"zeta":100}', CanonicalJson::encode($first));
    }

    public function test_canonical_build_serialization_keeps_game_and_version_boundaries(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $encoded = CanonicalJson::encode($build);

        self::assertSame($encoded, CanonicalJson::encode($build));
        self::assertStringContainsString('"edition":"poe1"', $encoded);
        self::assertStringContainsString('"checksum_sha256"', $encoded);
        self::assertStringContainsString('"parser_version"', $encoded);
        self::assertStringNotContainsString('poe2', $encoded);
    }

    public function test_ruleset_and_provenance_reject_invalid_checksums(): void
    {
        $edition = GameEdition::Poe1;
        $sourceVersion = DomainFixtures::value(
            SourceVersion::from($edition, 'fixture-1'),
            SourceVersion::class,
        );
        $badProvenance = DataProvenance::create(
            $edition,
            'LOOTWRIGHT-001',
            $sourceVersion,
            'not-a-checksum',
            PermissionStatus::Allowed,
            CommercialUseStatus::Allowed,
        );
        $validRuleset = DomainFixtures::ruleset($edition);
        $badRuleset = RulesetIdentity::create(
            $edition,
            $validRuleset->id,
            $validRuleset->version,
            $validRuleset->patch,
            $validRuleset->league,
            $validRuleset->parserVersion,
            strtoupper($validRuleset->checksumSha256),
            $validRuleset->provenance,
        );

        self::assertSame(DomainErrorCode::InvalidChecksum, $badProvenance->error()->code);
        self::assertSame(DomainErrorCode::InvalidChecksum, $badRuleset->error()->code);
    }

    public function test_item_factory_rejects_wrong_identifier_types_even_with_same_edition(): void
    {
        $edition = GameEdition::Poe1;
        $validItem = DomainFixtures::value(CanonicalItem::create(
            $edition,
            DomainFixtures::value(
                ItemId::from($edition, 'fixture.item'),
                ItemId::class,
            ),
            DomainFixtures::value(
                ItemBaseId::from($edition, 'fixture.base'),
                ItemBaseId::class,
            ),
            DomainFixtures::value(
                ItemSlotId::from($edition, 'fixture.slot'),
                ItemSlotId::class,
            ),
            ItemRarity::Rare,
            modifiers: [DomainFixtures::value(
                ModifierId::from($edition, 'fixture.modifier'),
                ModifierId::class,
            )],
        ), CanonicalItem::class);

        $wrongType = CanonicalItem::create(
            $edition,
            $validItem->id,
            $validItem->baseId,
            $validItem->slotId,
            $validItem->rarity,
            modifiers: [DomainFixtures::value(
                SkillId::from($edition, 'fixture.skill'),
                SkillId::class,
            )],
        );

        self::assertSame(DomainErrorCode::InvalidValue, $wrongType->error()->code);
    }
}

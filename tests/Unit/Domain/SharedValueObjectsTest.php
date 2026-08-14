<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\PoeCatalog\Identifier\SkillId;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Identity\BuildId;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\Domain\Shared\Value\Budget;
use Lootwright\Domain\Shared\Value\CurrencyCode;
use Lootwright\Domain\Shared\Value\Locale;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

class SharedValueObjectsTest extends TestCase
{
    public function test_game_scope_rejects_a_realm_from_another_edition(): void
    {
        $result = GameScope::create(GameEdition::Poe1, PlatformRealm::Poe2);

        self::assertTrue($result->isFailure());
        self::assertSame(DomainErrorCode::RealmMismatch, $result->error()->code);
    }

    public function test_build_and_analysis_ids_use_canonical_uuid_v7_values(): void
    {
        $buildId = BuildId::from(GameEdition::Poe1, DomainFixtures::POE1_BUILD_UUID);
        $analysisId = AnalysisId::from(GameEdition::Poe1, DomainFixtures::POE1_ANALYSIS_UUID);
        $uuidV4 = BuildId::from(GameEdition::Poe1, '550e8400-e29b-41d4-a716-446655440000');

        self::assertTrue($buildId->isSuccess());
        self::assertTrue($analysisId->isSuccess());
        self::assertTrue($uuidV4->isFailure());
        self::assertSame(DomainErrorCode::InvalidIdentifier, $uuidV4->error()->code);
    }

    public function test_identifier_equality_includes_type_and_edition(): void
    {
        $poe1 = DomainFixtures::value(
            SkillId::from(GameEdition::Poe1, 'fixture.skill'),
            SkillId::class,
        );
        $same = DomainFixtures::value(
            SkillId::from(GameEdition::Poe1, 'fixture.skill'),
            SkillId::class,
        );
        $poe2 = DomainFixtures::value(
            SkillId::from(GameEdition::Poe2, 'fixture.skill'),
            SkillId::class,
        );

        self::assertTrue($poe1->equals($same));
        self::assertFalse($poe1->equals($poe2));
    }

    public function test_identifier_serialization_requires_a_known_edition_and_canonical_value(): void
    {
        $roundTrip = SkillId::fromArray([
            'edition' => 'poe1',
            'value' => 'fixture.skill',
        ]);
        $unknownEdition = SkillId::fromArray([
            'edition' => 'future-game',
            'value' => 'fixture.skill',
        ]);
        $invalidIdentifier = SkillId::from(GameEdition::Poe1, 'Fixture Skill');

        self::assertTrue($roundTrip->isSuccess());
        self::assertSame(
            '{"edition":"poe1","value":"fixture.skill"}',
            CanonicalJson::encode($roundTrip->value()),
        );
        self::assertSame(DomainErrorCode::UnsupportedSerialization, $unknownEdition->error()->code);
        self::assertSame(DomainErrorCode::InvalidIdentifier, $invalidIdentifier->error()->code);
    }

    public function test_locale_and_budget_use_canonical_non_float_boundaries(): void
    {
        $currency = DomainFixtures::value(CurrencyCode::from('UNIT'), CurrencyCode::class);
        $budget = DomainFixtures::value(Budget::fromDecimal($currency, '12.3400'), Budget::class);

        self::assertSame('12.34', $budget->amount);
        self::assertSame('{"amount":"12.34","currency":"UNIT"}', CanonicalJson::encode($budget));
        self::assertSame(DomainErrorCode::InvalidAmount, Budget::fromDecimal($currency, '-1')->error()->code);
        self::assertSame(DomainErrorCode::InvalidLocale, Locale::from('EN_us')->error()->code);
    }
}

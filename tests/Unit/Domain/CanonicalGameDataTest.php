<?php

namespace Tests\Unit\Domain;

use InvalidArgumentException;
use Lootwright\Domain\PoeCatalog\Canonical\Ascendancy;
use Lootwright\Domain\PoeCatalog\Canonical\CharacterClass;
use Lootwright\Domain\Rulesets\DatasetClassification;
use Lootwright\Domain\Rulesets\GameRuleset;
use Lootwright\Domain\Rulesets\GameVersion;
use Lootwright\Domain\Rulesets\ProvenanceStatus;
use Lootwright\Domain\Rulesets\RulesetCompatibilityChecker;
use Lootwright\Domain\Rulesets\RulesetCompatibilityStatus;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Provenance\SourceProvenanceReference;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

final class CanonicalGameDataTest extends TestCase
{
    public function test_canonical_identity_is_edition_and_ruleset_scoped(): void
    {
        $poe1 = $this->provenance(GameEdition::Poe1);
        $poe2 = $this->provenance(GameEdition::Poe2);

        $poe1Class = new CharacterClass(GameEdition::Poe1, DomainFixtures::POE1_RULESET_UUID, 'class:0', 'Ranger', $poe1);
        $poe2Class = new CharacterClass(GameEdition::Poe2, DomainFixtures::POE2_RULESET_UUID, 'class:0', 'Ranger', $poe2);

        self::assertNotSame($poe1Class->edition, $poe2Class->edition);
        self::assertSame($poe1Class->externalId, $poe2Class->externalId);

        $this->expectException(InvalidArgumentException::class);
        new CharacterClass(GameEdition::Poe2, DomainFixtures::POE2_RULESET_UUID, 'class:0', 'Ranger', $poe1);
    }

    public function test_ascendancy_requires_a_stable_class_relationship_and_alternate_base(): void
    {
        $provenance = $this->provenance(GameEdition::Poe1);
        $ascendancy = new Ascendancy(GameEdition::Poe1, DomainFixtures::POE1_RULESET_UUID, 'ascendancy:1', 'Deadeye', $provenance, 'class:0');
        self::assertSame('class:0', $ascendancy->characterClassExternalId);

        $this->expectException(InvalidArgumentException::class);
        new Ascendancy(GameEdition::Poe1, DomainFixtures::POE1_RULESET_UUID, 'ascendancy:alternate', 'Alternate', $provenance, 'class:0', 'alternate');
    }

    public function test_fixture_and_invalid_provenance_are_never_production_compatible(): void
    {
        $identity = DomainFixtures::ruleset(GameEdition::Poe1, DomainFixtures::patch(GameEdition::Poe1, '3.28.0'));
        $checker = new RulesetCompatibilityChecker;
        $fixture = new GameRuleset(
            $identity,
            new GameVersion(GameEdition::Poe1, patch: $identity->patch),
            DatasetClassification::Fixture,
            ProvenanceStatus::Approved,
            RulesetCompatibilityStatus::Compatible,
        );
        $invalid = new GameRuleset(
            $identity,
            new GameVersion(GameEdition::Poe1, patch: $identity->patch),
            DatasetClassification::ApprovedImport,
            ProvenanceStatus::Invalid,
            RulesetCompatibilityStatus::InvalidProvenance,
        );

        self::assertSame(RulesetCompatibilityStatus::FixtureRejected, $checker->check(GameEdition::Poe1, '3.28.0', 'fixture.league', '1.0.0', $fixture));
        self::assertSame(RulesetCompatibilityStatus::InvalidProvenance, $checker->check(GameEdition::Poe1, '3.28.0', 'fixture.league', '1.0.0', $invalid));
    }

    private function provenance(GameEdition $edition): SourceProvenanceReference
    {
        return new SourceProvenanceReference(
            $edition,
            'LOOTWRIGHT-001',
            'fixture-1',
            str_repeat('a', 64),
        );
    }
}

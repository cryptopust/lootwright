<?php

namespace Tests\Unit\GameAdapters;

use Lootwright\Domain\BuildIntake\Import\BuildInputType;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\BuildIntake\Import\PropertySupportStatus;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\GameAdapters\PoE1\BuildImport\Poe1BuildImporter;
use Lootwright\GameAdapters\PoE1\ItemText\Poe1ItemTextImporter;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Parser;
use Lootwright\GameAdapters\PoE2\BuildImport\Poe2BuildImporter;
use Lootwright\GameAdapters\PoE2\ItemText\Poe2ItemTextImporter;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Parser;
use Lootwright\GameAdapters\Shared\BuildImport\BuildImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\SafeXmlParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class BuildImportPipelineTest extends TestCase
{
    public function test_poe1_item_text_is_normalized_without_promoting_observed_modifiers_to_canonical_ids(): void
    {
        $input = "Item Class: Body Armours\r\nRarity: Rare\r\nFixture Ward\r\nAstral Plate\r\n--------\r\nItem Level: 86\r\nSockets: R-R-R-R-R-R\r\n--------\r\n+99 to maximum Life";
        $result = $this->success((new Poe1ItemTextImporter)->import($input, new ImportLimits));
        $build = $result->canonicalBuild;

        self::assertSame(GameEdition::Poe1, $build->edition);
        self::assertNotNull($build->sourceMetadata);
        self::assertSame('USER-ITEM-TEXT-001', $build->sourceMetadata->sourceId);
        self::assertSame('item_text', $build->sourceMetadata->inputType->value);
        self::assertSame('Fixture Ward', $build->items[0]['display_name_observed']);
        self::assertSame('Astral Plate', $build->items[0]['base_name_observed']);
        self::assertSame(86, $build->items[0]['item_level_observed']);
        self::assertFalse($build->items[0]['raw_text_retained']);
        self::assertNull($build->itemModifiers[0]['canonical_modifier_id']);
        self::assertSame('+99 to maximum Life', $build->itemModifiers[0]['observed_text_untrusted']);
        self::assertSame(PropertySupportStatus::PartiallySupported, $build->propertySupport['item_modifiers']);
        self::assertStringNotContainsString('Fixture Ward', CanonicalJson::encode($result->provenance));
    }

    public function test_item_text_adapters_are_edition_scoped_and_reject_explicit_cross_edition_identifiers(): void
    {
        $poe1 = $this->success((new Poe1ItemTextImporter)->import("Rarity: Normal\nFixture", new ImportLimits));
        $poe2 = $this->success((new Poe2ItemTextImporter)->import("Rarity: Normal\nFixture", new ImportLimits));

        self::assertStringStartsWith('poe1.user.item.', $poe1->canonicalBuild->items[0]['id']);
        self::assertStringStartsWith('poe2.user.item.', $poe2->canonicalBuild->items[0]['id']);
        self::assertTrue($poe2->canonicalBuild->beta);
        self::assertSame(
            DomainErrorCode::EditionMismatch,
            (new Poe1ItemTextImporter)->import("Rarity: Normal\npoe2.item.fixture", new ImportLimits)->error()->code,
        );
        self::assertSame(
            DomainErrorCode::EditionMismatch,
            (new Poe2ItemTextImporter)->import("Rarity: Normal\npoe1:item.fixture", new ImportLimits)->error()->code,
        );
    }

    public function test_shared_pipeline_dispatches_to_separate_edition_adapters_and_detects_format_mismatch(): void
    {
        $pob = $this->pobImporter();
        $coordinator = new BuildImportCoordinator([
            new Poe1BuildImporter($pob, new Poe1ItemTextImporter),
            new Poe2BuildImporter($pob, new Poe2ItemTextImporter),
        ]);
        $poe1 = $this->success($coordinator->import(
            '<PathOfBuilding><Build/></PathOfBuilding>',
            BuildInputType::DecodedXml,
            GameEdition::Poe1,
        ));

        self::assertSame(GameEdition::Poe1, $poe1->canonicalBuild->edition);
        self::assertSame(BuildInputType::DecodedXml, $coordinator->detect('  <PathOfBuilding/>'));
        self::assertSame(BuildInputType::ItemText, $coordinator->detect("Rarity: Rare\nFixture"));
        self::assertSame(BuildInputType::PobShareCode, $coordinator->detect('eJz-fixture'));
        self::assertSame(
            GameEdition::Poe1,
            $this->success($coordinator->importDetected(
                "Rarity: Normal\nFixture",
                GameEdition::Poe1,
            ))->canonicalBuild->edition,
        );
        self::assertSame(
            DomainErrorCode::EditionMismatch,
            $coordinator->import(
                '<PathOfBuilding><Build/></PathOfBuilding>',
                BuildInputType::DecodedXml,
                GameEdition::Poe2,
            )->error()->code,
        );
        self::assertSame(
            DomainErrorCode::UnsupportedInput,
            $coordinator->import(
                '<PathOfBuilding><Build/></PathOfBuilding>',
                BuildInputType::PobShareCode,
                GameEdition::Poe1,
            )->error()->code,
        );
    }

    public function test_item_text_limits_and_encoding_fail_closed(): void
    {
        $importer = new Poe1ItemTextImporter;

        self::assertSame(DomainErrorCode::InputTooLarge, $importer->import('12345', new ImportLimits(textBytes: 4))->error()->code);
        self::assertSame(DomainErrorCode::InputTooLarge, $importer->import("a\nb", new ImportLimits(itemTextLines: 1))->error()->code);
        self::assertSame(DomainErrorCode::InputTooLarge, $importer->import('12345', new ImportLimits(lineBytes: 4))->error()->code);
        self::assertSame(DomainErrorCode::InvalidEncoding, $importer->import("Fixture\xFF", new ImportLimits)->error()->code);
        self::assertSame(DomainErrorCode::InvalidEncoding, $importer->import("Fixture\0Hidden", new ImportLimits)->error()->code);
    }

    public function test_duplicate_sections_nested_smuggling_and_cross_edition_ids_fail_or_remain_noncanonical(): void
    {
        $importer = $this->pobImporter();

        self::assertSame(
            DomainErrorCode::DuplicateValue,
            $importer->import('<PathOfBuilding><Build/><Build/></PathOfBuilding>')->error()->code,
        );
        self::assertSame(
            DomainErrorCode::EditionMismatch,
            $importer->import('<PathOfBuilding><Build className="poe2.class.fixture"/></PathOfBuilding>')->error()->code,
        );
        self::assertSame(
            DomainErrorCode::EditionMismatch,
            $importer->import('<PathOfBuilding><Build className="poe2:class.fixture"/></PathOfBuilding>')->error()->code,
        );

        $nested = $this->success($importer->import('<PathOfBuilding><Build/><Unknown><PlayerStat stat="Life" value="999999"/></Unknown><Skills><Unknown><Skill><Gem skillId="smuggled"/></Skill></Unknown></Skills></PathOfBuilding>'));
        self::assertSame([], $nested->canonicalBuild->summaryValues);
        self::assertSame([], $nested->canonicalBuild->skills);
        self::assertNull($nested->canonicalBuild->life);
        self::assertContains('Unknown', array_map(static fn ($feature): string => $feature->element, $nested->unsupportedFeatures));
    }

    public function test_xml_text_attribute_and_diagnostic_budgets_are_enforced(): void
    {
        $importer = $this->pobImporter();

        self::assertSame(
            DomainErrorCode::InputTooLarge,
            $importer->import('<PathOfBuilding><Build className="12345"/></PathOfBuilding>', new ImportLimits(attributeBytes: 4))->error()->code,
        );
        self::assertSame(
            DomainErrorCode::InputTooLarge,
            $importer->import('<PathOfBuilding><Build/><Notes>12345</Notes></PathOfBuilding>', new ImportLimits(xmlTextBytes: 4))->error()->code,
        );
        self::assertSame(
            DomainErrorCode::InputTooLarge,
            $importer->import('<PathOfBuilding><Build/><One/><Two/></PathOfBuilding>', new ImportLimits(unsupportedFeatures: 1))->error()->code,
        );
    }

    public function test_committed_malicious_fixtures_fail_with_expected_typed_errors(): void
    {
        $importer = $this->pobImporter();
        $fixtures = [
            'dtd.xml' => DomainErrorCode::UnsafeXml,
            'duplicate-sections.xml' => DomainErrorCode::DuplicateValue,
            'deep-nesting.xml' => DomainErrorCode::InputTooLarge,
            'cross-edition.xml' => DomainErrorCode::EditionMismatch,
        ];

        foreach ($fixtures as $fixture => $expected) {
            $input = file_get_contents(dirname(__DIR__, 2).'/Fixtures/Pob/Malicious/'.$fixture);

            if (! is_string($input)) {
                throw new RuntimeException('Malicious parser fixture could not be read.');
            }

            $limits = $fixture === 'deep-nesting.xml' ? new ImportLimits(xmlDepth: 4) : new ImportLimits;
            self::assertSame($expected, $importer->import($input, $limits)->error()->code, $fixture);
        }
    }

    public function test_verified_poe1_summary_aliases_are_projected_but_poe2_values_remain_unpromoted(): void
    {
        $poe1 = $this->success($this->pobImporter()->import('<PathOfBuilding><Build><PlayerStat stat="Life" value="1234"/><PlayerStat stat="FireResist" value="75"/><PlayerStat stat="Str" value="100"/></Build></PathOfBuilding>'));
        $poe2 = $this->success($this->pobImporter()->import('<PathOfBuilding2><Build><PlayerStat stat="Life" value="1234"/><PlayerStat stat="FireResist" value="75"/></Build></PathOfBuilding2>'));

        self::assertSame(1234, $poe1->canonicalBuild->life);
        self::assertSame(['strength' => 100], $poe1->canonicalBuild->attributes);
        self::assertSame(['fire' => 75], $poe1->canonicalBuild->resistances);
        self::assertNull($poe2->canonicalBuild->life);
        self::assertSame([], $poe2->canonicalBuild->resistances);
        self::assertSame(PropertySupportStatus::Unknown, $poe2->canonicalBuild->propertySupport['life']);
    }

    /** @return iterable<string, array{string}> */
    public static function malformedInputs(): iterable
    {
        yield 'punctuation' => ['%%%'];
        yield 'truncated xml' => ['<PathOfBuilding><Build>'];
        yield 'invalid zlib' => [rtrim(strtr(base64_encode('invalid-zlib'), '+/', '-_'), '=')];
        yield 'DTD' => ['<!DOCTYPE x><PathOfBuilding><Build/></PathOfBuilding>'];
        yield 'duplicate tree' => ['<PathOfBuilding><Build/><Tree/><Tree/></PathOfBuilding>'];
    }

    #[DataProvider('malformedInputs')]
    public function test_malformed_inputs_return_typed_results_without_uncaught_parser_failures(string $input): void
    {
        $first = $this->pobImporter()->import($input);
        $second = $this->pobImporter()->import($input);

        self::assertTrue($first->isFailure());
        self::assertSame($first->error()->code, $second->error()->code);
    }

    public function test_fuzz_style_malformed_envelopes_are_deterministically_rejected(): void
    {
        $importer = $this->pobImporter();

        for ($index = 0; $index < 128; $index++) {
            $input = '%'.substr(hash('sha256', 'lootwright-fuzz-'.$index), 0, ($index % 63) + 1);
            $first = $importer->import($input);
            $second = $importer->import($input);

            self::assertTrue($first->isFailure());
            self::assertSame(DomainErrorCode::InvalidEncoding, $first->error()->code);
            self::assertSame($first->error()->code, $second->error()->code);
        }
    }

    private function pobImporter(): PobImportCoordinator
    {
        return new PobImportCoordinator(
            new PobEnvelopeDecoder,
            new SafeXmlParser,
            [new Pob1Parser(new Pob1Normalizer), new Pob2Parser(new Pob2Normalizer)],
        );
    }

    private function success(DomainResult $result): PobImportResult
    {
        if ($result->isFailure()) {
            self::fail($result->error()->code->value.': '.$result->error()->message);
        }

        $value = $result->value();

        if (! $value instanceof PobImportResult) {
            throw new RuntimeException('Expected a normalized build import result.');
        }

        return $value;
    }
}

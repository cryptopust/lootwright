<?php

namespace Tests\Unit\GameAdapters;

use Closure;
use Lootwright\Domain\BuildIntake\Import\ImportLimits;
use Lootwright\Domain\BuildIntake\Import\PobImportResult;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Normalizer;
use Lootwright\GameAdapters\PoE1\Pob\Pob1Parser;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Normalizer;
use Lootwright\GameAdapters\PoE2\Pob\Pob2Parser;
use Lootwright\GameAdapters\Shared\Pob\PobEnvelopeDecoder;
use Lootwright\GameAdapters\Shared\Pob\PobImportCoordinator;
use Lootwright\GameAdapters\Shared\Pob\SafeXmlParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PobImportTest extends TestCase
{
    public function test_valid_poe1_code_extracts_canonical_fields_and_hostile_notes_as_text(): void
    {
        $result = $this->success($this->importer()->import($this->code($this->fixture('poe1-minimal.xml'))));

        self::assertSame(GameEdition::Poe1, $result->canonicalBuild->edition);
        self::assertFalse($result->canonicalBuild->beta);
        self::assertSame(42, $result->canonicalBuild->characterLevel);
        self::assertSame(['poe1.pob.node.101', 'poe1.pob.node.202'], $result->canonicalBuild->passiveNodeIds);
        self::assertCount(1, $result->canonicalBuild->skills);
        self::assertCount(1, $result->canonicalBuild->items);
        self::assertSame('<script>fixture</script>', substr($result->canonicalBuild->notes, 10, 24));
        self::assertStringNotContainsString('<script>', CanonicalJson::encode($result));
        self::assertSame('POB1-FORMAT-001', $result->provenance->sourceId);
    }

    public function test_valid_poe2_is_separate_and_marked_beta(): void
    {
        $result = $this->success($this->importer()->import($this->fixture('poe2-minimal.xml')));

        self::assertSame(GameEdition::Poe2, $result->canonicalBuild->edition);
        self::assertTrue($result->canonicalBuild->beta);
        self::assertSame('POB2-FORMAT-001', $result->provenance->sourceId);
        self::assertSame('poe2.pob.class.fixture_two', $result->canonicalBuild->characterClassId);
    }

    public function test_identical_parser_local_identifiers_are_edition_scoped(): void
    {
        $poe1 = $this->success($this->importer()->import(
            '<PathOfBuilding><Build className="Same"/><Tree><Spec nodes="7"/></Tree><Skills><Skill><Gem skillId="same"/></Skill></Skills><Items><Item id="7">same</Item></Items></PathOfBuilding>',
        ));
        $poe2 = $this->success($this->importer()->import(
            '<PathOfBuilding2><Build className="Same" bandit="poe1-only"/><Tree><Spec nodes="7"/></Tree><Skills><Skill><Gem skillId="same"/></Skill></Skills><Items><Item id="7">same</Item></Items></PathOfBuilding2>',
        ));

        self::assertSame('poe1.pob.class.same', $poe1->canonicalBuild->characterClassId);
        self::assertSame('poe2.pob.class.same', $poe2->canonicalBuild->characterClassId);
        self::assertNotSame($poe1->canonicalBuild->passiveNodeIds, $poe2->canonicalBuild->passiveNodeIds);
        self::assertNotSame($poe1->canonicalBuild->skills[0]['gems'][0]['id'], $poe2->canonicalBuild->skills[0]['gems'][0]['id']);
        self::assertNotSame($poe1->canonicalBuild->items[0]['id'], $poe2->canonicalBuild->items[0]['id']);
        self::assertSame([], $poe2->canonicalBuild->choices);
        self::assertContains(
            'Build@unsupported_attributes',
            array_map(static fn ($feature): string => $feature->element, $poe2->unsupportedFeatures),
        );
    }

    public function test_ambiguous_edition_and_urls_are_rejected_without_fetching(): void
    {
        $ambiguous = '<PathOfBuilding><Build/><PathOfBuilding2/></PathOfBuilding>';

        self::assertSame(DomainErrorCode::AmbiguousGameEdition, $this->importer()->import($ambiguous)->error()->code);
        self::assertSame(DomainErrorCode::UnsupportedInput, $this->importer()->import('https://pobb.in/fixture')->error()->code);
        self::assertStringContainsString('Paste the raw PoB code', $this->importer()->import('https://pobb.in/fixture')->error()->message);
    }

    public function test_malformed_base64_and_invalid_compression_are_typed(): void
    {
        self::assertSame(DomainErrorCode::InvalidEncoding, $this->importer()->import('abc$')->error()->code);
        self::assertSame(DomainErrorCode::InvalidCompression, $this->importer()->import($this->base64Url('not-zlib'))->error()->code);
    }

    public function test_decompression_bomb_and_huge_xml_are_rejected(): void
    {
        $limits = new ImportLimits(inputBytes: 20_000, compressedBytes: 20_000, xmlBytes: 1_024, expansionRatio: 8);
        $compressed = gzcompress(str_repeat('A', 8_192), 9);

        if (! is_string($compressed)) {
            throw new RuntimeException('Bomb fixture compression failed.');
        }

        $bomb = $this->base64Url($compressed);

        self::assertSame(DomainErrorCode::DecompressionLimit, $this->importer()->import($bomb, $limits)->error()->code);
        self::assertSame(DomainErrorCode::InputTooLarge, $this->importer()->import('<PathOfBuilding>'.str_repeat(' ', 2_000).'</PathOfBuilding>', $limits)->error()->code);
    }

    public function test_passive_node_overflow_is_rejected_instead_of_truncated(): void
    {
        $xml = '<PathOfBuilding><Build/><Tree><Spec nodes="1,2"/></Tree></PathOfBuilding>';

        self::assertSame(
            DomainErrorCode::InputTooLarge,
            $this->importer()->import($xml, new ImportLimits(passiveNodes: 1))->error()->code,
        );
    }

    public function test_skill_and_gem_overflow_are_rejected_instead_of_truncated(): void
    {
        $skills = '<PathOfBuilding><Build/><Skills><Skill/><Skill/></Skills></PathOfBuilding>';
        $gems = '<PathOfBuilding><Build/><Skills><Skill><Gem/><Gem/></Skill></Skills></PathOfBuilding>';

        self::assertSame(
            DomainErrorCode::InputTooLarge,
            $this->importer()->import($skills, new ImportLimits(skills: 1))->error()->code,
        );
        self::assertSame(
            DomainErrorCode::InputTooLarge,
            $this->importer()->import($gems, new ImportLimits(gems: 1))->error()->code,
        );
    }

    public function test_invalid_scalar_fields_remain_explicitly_unknown_or_untrusted(): void
    {
        $xml = '<PathOfBuilding><Build level="invalid"/><Skills><Skill enabled="invalid"><Gem level="invalid" quality="invalid" enabled="invalid"/></Skill></Skills><Config><Input name="flag" boolean="invalid"/><Input name="count" number="invalid"/></Config></PathOfBuilding>';
        $result = $this->success($this->importer()->import($xml));
        $warningCodes = array_map(static fn ($warning): string => $warning->code, $result->warnings);

        self::assertNull($result->canonicalBuild->characterLevel);
        self::assertNull($result->canonicalBuild->skills[0]['enabled']);
        self::assertNull($result->canonicalBuild->skills[0]['gems'][0]['enabled']);
        self::assertSame('invalid', $result->canonicalBuild->configuration['flag']);
        self::assertSame('invalid', $result->canonicalBuild->configuration['count']);
        self::assertContains('invalid_boolean', $warningCodes);
        self::assertContains('invalid_integer', $warningCodes);
    }

    public function test_processing_time_budget_fails_closed_with_a_typed_error(): void
    {
        $ticks = [0, 2_000_000];
        $clock = static function () use (&$ticks): int {
            return array_shift($ticks) ?? 2_000_000;
        };

        self::assertSame(
            DomainErrorCode::ProcessingLimit,
            $this->importer($clock)->import(
                '<PathOfBuilding><Build/></PathOfBuilding>',
                new ImportLimits(processingMilliseconds: 1),
            )->error()->code,
        );
    }

    public function test_xxe_deep_xml_malformed_xml_and_invalid_utf8_are_rejected(): void
    {
        $xxe = '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><PathOfBuilding><Build>&e;</Build></PathOfBuilding>';
        $deep = '<PathOfBuilding><Build/><A><B><C><D/></C></B></A></PathOfBuilding>';

        self::assertSame(DomainErrorCode::UnsafeXml, $this->importer()->import($xxe)->error()->code);
        self::assertSame(DomainErrorCode::InputTooLarge, $this->importer()->import($deep, new ImportLimits(xmlDepth: 4))->error()->code);
        self::assertSame(DomainErrorCode::InvalidXml, $this->importer()->import('<PathOfBuilding><Build></PathOfBuilding>')->error()->code);
        self::assertSame(DomainErrorCode::InvalidXml, $this->importer()->import("<PathOfBuilding><Build/>\xFF</PathOfBuilding>")->error()->code);
        self::assertSame(
            DomainErrorCode::InvalidXml,
            $this->importer()->import('<?xml version="1.0" encoding="ISO-8859-1"?><PathOfBuilding><Build/></PathOfBuilding>')->error()->code,
        );
    }

    public function test_unknown_nodes_are_preserved_and_duplicate_items_are_rejected(): void
    {
        $valid = $this->success($this->importer()->import($this->fixture('poe1-minimal.xml')));
        $duplicate = '<PathOfBuilding><Build/><Items><Item id="1">one</Item><Item id="1">two</Item></Items></PathOfBuilding>';

        $future = array_values(array_filter(
            $valid->unsupportedFeatures,
            static fn ($feature): bool => $feature->element === 'LootwrightFuture',
        ));
        self::assertCount(1, $future);
        self::assertSame(['example' => 'one'], $future[0]->attributes);
        self::assertSame(DomainErrorCode::DuplicateValue, $this->importer()->import($duplicate)->error()->code);
    }

    public function test_unconsumed_sections_and_attributes_are_reported_explicitly(): void
    {
        $xml = '<PathOfBuilding><Build level="1" future="fixture"/><Calcs mode="fixture"/></PathOfBuilding>';
        $result = $this->success($this->importer()->import($xml));
        $features = array_map(
            static fn ($feature): string => $feature->element,
            $result->unsupportedFeatures,
        );

        self::assertContains('Build@unsupported_attributes', $features);
        self::assertContains('Calcs', $features);
    }

    public function test_encoded_and_decompressed_round_trip_normalize_identically(): void
    {
        $xml = $this->fixture('poe1-minimal.xml');
        $direct = $this->success($this->importer()->import($xml));
        $encoded = $this->success($this->importer()->import($this->code($xml)));

        self::assertSame(CanonicalJson::encode($direct), CanonicalJson::encode($encoded));
    }

    private function importer(?Closure $clock = null): PobImportCoordinator
    {
        return new PobImportCoordinator(
            new PobEnvelopeDecoder,
            new SafeXmlParser,
            [new Pob1Parser(new Pob1Normalizer), new Pob2Parser(new Pob2Normalizer)],
            $clock,
        );
    }

    private function fixture(string $name): string
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/Fixtures/Pob/'.$name);

        if (! is_string($contents)) {
            throw new RuntimeException('Fixture could not be read.');
        }

        return $contents;
    }

    private function code(string $xml): string
    {
        $compressed = gzcompress($xml, 9);

        if (! is_string($compressed)) {
            throw new RuntimeException('Fixture compression failed.');
        }

        return $this->base64Url($compressed);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function success(DomainResult $result): PobImportResult
    {
        if ($result->isFailure()) {
            self::fail($result->error()->code->value.': '.$result->error()->message);
        }

        $value = $result->value();

        if (! $value instanceof PobImportResult) {
            throw new RuntimeException('Expected a PoB import result.');
        }

        return $value;
    }
}

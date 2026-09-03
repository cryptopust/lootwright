<?php

namespace Lootwright\GameAdapters\PoE2\Analysis;

use InvalidArgumentException;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\AnalysisStatus;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingCategory;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\BuildIntake\Import\CanonicalImportedBuild;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Serialization\CanonicalJson;

/** Deterministic PoE2 rules; deliberately does not import PoE1 registries. */
final readonly class Poe2DeterministicAnalysisEngine
{
    public const ENGINE_VERSION = '1.0.0';

    /** @param array<string, mixed> $sourceProvenance */
    public function analyze(CanonicalImportedBuild $build, AnalysisId $analysisId, RulesetIdentity $ruleset, Poe2AnalysisRuleset $configuration, array $sourceProvenance = []): AnalysisResult
    {
        if ($build->edition !== GameEdition::Poe2 || $ruleset->edition !== GameEdition::Poe2 || ! $analysisId->belongsTo(GameEdition::Poe2)) {
            throw new InvalidArgumentException('PoE2 analysis inputs must share the PoE2 edition.');
        }
        $this->rejectForeignIdentifiers($build);
        $findings = [];
        $rules = $configuration->ruleCodes;
        if (in_array('poe2.data.character.level.missing', $rules, true) && $build->characterLevel === null) {
            $findings[] = $this->finding($analysisId, $ruleset, 'poe2.data.character.level.missing', FindingSeverity::Information, FindingCategory::DataQuality, 'PoE2 character level is missing', 'The PoE2 import does not contain a verified character level.', null, 'reported level', ['build:character_level'], $sourceProvenance);
        }
        if (in_array('poe2.data.character.class.missing', $rules, true) && $build->characterClassId === null) {
            $findings[] = $this->finding($analysisId, $ruleset, 'poe2.data.character.class.missing', FindingSeverity::Warning, FindingCategory::DataQuality, 'PoE2 character class is missing', 'The class was not present in the normalized PoE2 build.', null, 'reported class', ['build:character_class'], $sourceProvenance);
        }
        if (in_array('poe2.data.character.ascendancy.missing', $rules, true) && $build->ascendancyId === null) {
            $findings[] = $this->finding($analysisId, $ruleset, 'poe2.data.character.ascendancy.missing', FindingSeverity::Information, FindingCategory::DataQuality, 'PoE2 ascendancy is missing', 'No PoE2 ascendancy was supplied; no class mapping was inferred.', null, 'reported ascendancy', ['build:ascendancy'], $sourceProvenance);
        }
        if (in_array('poe2.skills.main.missing', $rules, true) && $build->skills === []) {
            $findings[] = $this->finding($analysisId, $ruleset, 'poe2.skills.main.missing', FindingSeverity::Warning, FindingCategory::Skills, 'PoE2 main skill is missing', 'The imported PoE2 build has no skill group from which a main skill can be selected.', 0, 'at least one skill group', ['skills'], $sourceProvenance);
        }
        if (in_array('poe2.data.resistances.unavailable', $rules, true) && $build->resistances === []) {
            $findings[] = $this->finding($analysisId, $ruleset, 'poe2.data.resistances.unavailable', FindingSeverity::Information, FindingCategory::Resistances, 'PoE2 resistance data is unavailable', 'The selected PoE2 source did not provide resistance facts; no cap or threshold was guessed.', [], 'source-reported resistances', ['player_stat:resistances'], $sourceProvenance);
        }

        return new AnalysisResult(GameEdition::Poe2, $ruleset, self::ENGINE_VERSION, AnalysisStatus::Complete, $findings, unsupportedData: $this->unsupportedData($build));
    }

    /** @param list<string> $evidence
     * @param  array<string, mixed>  $provenance
     */
    private function finding(AnalysisId $id, RulesetIdentity $ruleset, string $code, FindingSeverity $severity, FindingCategory $category, string $title, string $explanation, mixed $observed, mixed $expected, array $evidence, array $provenance): Finding
    {
        $result = Finding::deterministic($id, $ruleset, $code, $severity, $category, $title, $explanation, $observed, $expected, [], [], [], $evidence, $provenance);
        if ($result->isFailure() || ! $result->value() instanceof Finding) {
            throw new InvalidArgumentException('PoE2 deterministic finding could not be created.');
        }

        return $result->value();
    }

    private function rejectForeignIdentifiers(CanonicalImportedBuild $build): void
    {
        $json = CanonicalJson::encode($build);
        if (preg_match('/(?:^|["\\s])poe1[.:][a-z0-9._:-]+/i', $json) === 1) {
            throw new InvalidArgumentException('PoE1 identifiers cannot enter the PoE2 analyzer.');
        }
    }

    /** @return list<string> */
    private function unsupportedData(CanonicalImportedBuild $build): array
    {
        $unsupported = [];
        foreach ($build->propertySupport as $property => $status) {
            if (in_array($status->value, ['unsupported', 'unknown'], true)) {
                $unsupported[] = 'property:'.$property.':'.$status->value;
            }
        }
        foreach ($build->unsupportedFields as $feature) {
            $unsupported[] = 'input:'.$feature->path.':'.$feature->element;
        }
        $unsupported = array_values(array_unique($unsupported));
        sort($unsupported, SORT_STRING);

        return $unsupported;
    }
}

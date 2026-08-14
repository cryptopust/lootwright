<?php

namespace Tests\Support;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\BuildIntake\BuildSnapshot;
use Lootwright\Domain\BuildIntake\CanonicalBuild;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\BuildIntake\Intent\ContentGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayerGoal;
use Lootwright\Domain\BuildIntake\Intent\PlayStyle;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\PoeCatalog\BuildCatalog;
use Lootwright\Domain\PoeCatalog\Identifier\CharacterClassId;
use Lootwright\Domain\PolicyProvenance\CommercialUseStatus;
use Lootwright\Domain\PolicyProvenance\DataProvenance;
use Lootwright\Domain\PolicyProvenance\PermissionStatus;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Recommendations\RecommendationImpact;
use Lootwright\Domain\Rulesets\RulesetIdentity;
use Lootwright\Domain\Shared\Error\DomainResult;
use Lootwright\Domain\Shared\Evidence\ExplanationTrace;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Evidence\TraceStep;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Game\GameScope;
use Lootwright\Domain\Shared\Game\PlatformRealm;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Identity\BuildId;
use Lootwright\Domain\Shared\Identity\RulesetId;
use Lootwright\Domain\Shared\Value\Confidence;
use Lootwright\Domain\Shared\Value\Locale;
use Lootwright\Domain\Shared\Version\LeagueId;
use Lootwright\Domain\Shared\Version\ParserVersion;
use Lootwright\Domain\Shared\Version\PatchVersion;
use Lootwright\Domain\Shared\Version\RulesetVersion;
use Lootwright\Domain\Shared\Version\SourceVersion;
use RuntimeException;

final class DomainFixtures
{
    public const POE1_BUILD_UUID = '01890f47-0f7d-7a2b-8c3d-1234567890ab';

    public const POE1_ANALYSIS_UUID = '01890f47-0f7d-7a2b-9c3d-1234567890ab';

    public const POE1_RULESET_UUID = '01890f47-0f7d-7a2b-ac3d-1234567890ab';

    public const POE2_BUILD_UUID = '01890f47-0f7d-7a2b-bc3d-1234567890ab';

    public const POE2_ANALYSIS_UUID = '01890f47-0f7e-7a2b-8c3d-1234567890ab';

    public const POE2_RULESET_UUID = '01890f47-0f7e-7a2b-9c3d-1234567890ab';

    /**
     * @template TObject of object
     *
     * @param  class-string<TObject>  $expectedClass
     * @return TObject
     */
    public static function value(DomainResult $result, string $expectedClass): object
    {
        if ($result->isFailure()) {
            throw new RuntimeException($result->error()->message);
        }

        $value = $result->value();

        if (! $value instanceof $expectedClass) {
            throw new RuntimeException("Expected {$expectedClass} from domain result.");
        }

        return $value;
    }

    public static function scope(GameEdition $edition): GameScope
    {
        $realm = $edition === GameEdition::Poe1 ? PlatformRealm::Pc : PlatformRealm::Poe2;

        return self::value(GameScope::create($edition, $realm), GameScope::class);
    }

    public static function buildId(GameEdition $edition): BuildId
    {
        $uuid = $edition === GameEdition::Poe1 ? self::POE1_BUILD_UUID : self::POE2_BUILD_UUID;

        return self::value(BuildId::from($edition, $uuid), BuildId::class);
    }

    public static function analysisId(GameEdition $edition): AnalysisId
    {
        $uuid = $edition === GameEdition::Poe1 ? self::POE1_ANALYSIS_UUID : self::POE2_ANALYSIS_UUID;

        return self::value(AnalysisId::from($edition, $uuid), AnalysisId::class);
    }

    public static function patch(GameEdition $edition, ?string $value = null): PatchVersion
    {
        $value ??= $edition === GameEdition::Poe1 ? '1.2.3' : '2.3.4';

        return self::value(PatchVersion::from($edition, $value), PatchVersion::class);
    }

    public static function league(GameEdition $edition): LeagueId
    {
        return self::value(LeagueId::from($edition, 'fixture.league'), LeagueId::class);
    }

    public static function parser(GameEdition $edition, string $value = '1.0.0'): ParserVersion
    {
        return self::value(ParserVersion::from($edition, $value), ParserVersion::class);
    }

    public static function ruleset(GameEdition $edition, ?PatchVersion $patch = null): RulesetIdentity
    {
        $rulesetUuid = $edition === GameEdition::Poe1
            ? self::POE1_RULESET_UUID
            : self::POE2_RULESET_UUID;
        $sourceVersion = self::value(
            SourceVersion::from($edition, 'fixture-1'),
            SourceVersion::class,
        );
        $provenance = self::value(DataProvenance::create(
            $edition,
            'LOOTWRIGHT-001',
            $sourceVersion,
            str_repeat('a', 64),
            PermissionStatus::Allowed,
            CommercialUseStatus::Allowed,
        ), DataProvenance::class);

        return self::value(RulesetIdentity::create(
            $edition,
            self::value(RulesetId::from($edition, $rulesetUuid), RulesetId::class),
            self::value(RulesetVersion::from($edition, '1.0.0'), RulesetVersion::class),
            $patch ?? self::patch($edition),
            self::league($edition),
            self::parser($edition),
            str_repeat('b', 64),
            $provenance,
        ), RulesetIdentity::class);
    }

    public static function catalog(GameEdition $edition): BuildCatalog
    {
        $characterClass = self::value(
            CharacterClassId::from($edition, 'fixture.class'),
            CharacterClassId::class,
        );

        return self::value(BuildCatalog::create(
            $edition,
            $characterClass,
            null,
        ), BuildCatalog::class);
    }

    public static function snapshot(GameEdition $edition, ?PatchVersion $patch = null): BuildSnapshot
    {
        return self::value(BuildSnapshot::create(
            self::buildId($edition),
            self::scope($edition),
            $patch ?? self::patch($edition),
            self::league($edition),
            self::parser($edition),
            self::value(Locale::from('en-US'), Locale::class),
            self::catalog($edition),
            str_repeat('c', 64),
        ), BuildSnapshot::class);
    }

    public static function canonicalBuild(GameEdition $edition): CanonicalBuild
    {
        return self::value(CanonicalBuild::create(
            self::snapshot($edition),
            self::ruleset($edition),
        ), CanonicalBuild::class);
    }

    public static function trace(CanonicalBuild $build): ExplanationTrace
    {
        $edition = $build->snapshot->scope->edition;
        $rule = self::value(RuleReference::create(
            $edition,
            $build->ruleset->id,
            $build->ruleset->version,
            'fixture.rule',
        ), RuleReference::class);
        $step = self::value(TraceStep::create(
            'fixture.step',
            'A deterministic fixture step.',
            ['input:fixture'],
            $rule,
        ), TraceStep::class);

        return self::value(ExplanationTrace::create($edition, [$step]), ExplanationTrace::class);
    }

    public static function finding(CanonicalBuild $build): Finding
    {
        $edition = $build->snapshot->scope->edition;
        $trace = self::trace($build);

        return self::value(Finding::create(
            $build,
            self::analysisId($edition),
            'fixture.finding',
            FindingSeverity::Opportunity,
            'A deterministic fixture finding.',
            ['input:fixture'],
            $trace->steps[0]->rule,
            $trace,
        ), Finding::class);
    }

    public static function recommendation(CanonicalBuild $build): Recommendation
    {
        $edition = $build->snapshot->scope->edition;
        $impact = self::value(RecommendationImpact::create([
            'fixture_dimension' => 1_000,
        ]), RecommendationImpact::class);

        return self::value(Recommendation::create(
            $edition,
            self::analysisId($edition),
            'fixture.recommendation',
            UpgradePriority::Medium,
            $impact,
            [self::finding($build)],
            [],
            self::trace($build),
        ), Recommendation::class);
    }

    public static function intent(GameEdition $edition): BuildIntent
    {
        $goal = self::value(PlayerGoal::create(
            $edition,
            'Improve the fixture build without changing its identity.',
            self::value(ContentGoal::from($edition, 'fixture.content'), ContentGoal::class),
            self::value(PlayStyle::from($edition, 'fixture.style'), PlayStyle::class),
        ), PlayerGoal::class);

        return self::value(BuildIntent::create(
            $goal,
            self::value(Locale::from('en-US'), Locale::class),
            self::value(Confidence::fromBasisPoints(9_000), Confidence::class),
            [],
        ), BuildIntent::class);
    }
}

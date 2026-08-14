<?php

namespace Tests\Unit\Domain;

use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\Analysis\FindingSeverity;
use Lootwright\Domain\BuildIntake\Intent\UpgradePriority;
use Lootwright\Domain\Recommendations\Recommendation;
use Lootwright\Domain\Recommendations\RecommendationImpact;
use Lootwright\Domain\Shared\Error\DomainErrorCode;
use Lootwright\Domain\Shared\Evidence\RuleReference;
use Lootwright\Domain\Shared\Game\GameEdition;
use Lootwright\Domain\Shared\Identity\AnalysisId;
use Lootwright\Domain\Shared\Identity\RulesetId;
use PHPUnit\Framework\TestCase;
use Tests\Support\DomainFixtures;

class AnalysisAndRecommendationTest extends TestCase
{
    public function test_finding_must_reference_the_canonical_build_ruleset(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $otherRulesetId = DomainFixtures::value(RulesetId::from(
            GameEdition::Poe1,
            '01890f47-0f7e-7a2b-bc3d-1234567890ab',
        ), RulesetId::class);
        $otherRule = DomainFixtures::value(RuleReference::create(
            GameEdition::Poe1,
            $otherRulesetId,
            $build->ruleset->version,
            'fixture.other-rule',
        ), RuleReference::class);

        $result = Finding::create(
            $build,
            DomainFixtures::analysisId(GameEdition::Poe1),
            'fixture.finding',
            FindingSeverity::Warning,
            'A ruleset mismatch fixture.',
            ['input:fixture'],
            $otherRule,
            DomainFixtures::trace($build),
        );

        self::assertSame(DomainErrorCode::RulesetMismatch, $result->error()->code);
    }

    public function test_recommendation_rejects_a_finding_from_another_analysis(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $impact = DomainFixtures::value(
            RecommendationImpact::create(['fixture_dimension' => 500]),
            RecommendationImpact::class,
        );

        $result = Recommendation::create(
            GameEdition::Poe1,
            DomainFixtures::value(AnalysisId::from(
                GameEdition::Poe1,
                '01890f47-0f7e-7a2b-ac3d-1234567890ab',
            ), AnalysisId::class),
            'fixture.recommendation',
            UpgradePriority::High,
            $impact,
            [DomainFixtures::finding($build)],
            [],
            DomainFixtures::trace($build),
        );

        self::assertSame(DomainErrorCode::AnalysisMismatch, $result->error()->code);
    }

    public function test_valid_finding_and_recommendation_retain_evidence_trace(): void
    {
        $build = DomainFixtures::canonicalBuild(GameEdition::Poe1);
        $finding = DomainFixtures::finding($build);
        $recommendation = DomainFixtures::recommendation($build);

        self::assertSame('fixture.rule', $finding->rule->ruleKey);
        self::assertSame(['input:fixture'], $finding->inputEvidence);
        self::assertSame('fixture.finding', $recommendation->findings[0]->code);
        self::assertSame($build->ruleset->id->value, $finding->rule->rulesetId->value);
    }
}

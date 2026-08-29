<?php

namespace Lootwright\GameAdapters\PoE2\Recommendations;

use InvalidArgumentException;
use Lootwright\Domain\Analysis\AnalysisResult;
use Lootwright\Domain\Analysis\Finding;
use Lootwright\Domain\BuildIntake\Intent\BuildIntent;
use Lootwright\Domain\Recommendations\BudgetUncertainty;
use Lootwright\Domain\Recommendations\MarketDataRequirement;
use Lootwright\Domain\Recommendations\UpgradeCandidate;
use Lootwright\Domain\Recommendations\UpgradeCandidateFactory;
use Lootwright\Domain\Recommendations\UpgradeClassification;
use Lootwright\Domain\Recommendations\UpgradePriorityScorer;
use Lootwright\Domain\Shared\Evidence\RulesetReference;
use Lootwright\Domain\Shared\Game\GameEdition;

/** PoE2-only recommendation mapping; no PoE1 rule or stat identifiers. */
final readonly class Poe2UpgradeCandidateFactory implements UpgradeCandidateFactory
{
    public function __construct(private UpgradePriorityScorer $scorer = new UpgradePriorityScorer) {}

    public function edition(): GameEdition
    {
        return GameEdition::Poe2;
    }

    public function create(AnalysisResult $analysis, BuildIntent $intent): array
    {
        if ($analysis->gameEdition !== GameEdition::Poe2 || $intent->goal->edition !== GameEdition::Poe2) {
            throw new InvalidArgumentException('The PoE2 candidate factory accepts only matching PoE2 products.');
        }
        $codes = array_fill_keys(array_column($analysis->findings, 'code'), true);
        $candidates = [];
        foreach ($analysis->findings as $finding) {
            $definition = match ($finding->code) {
                'poe2.data.character.level.missing', 'poe2.data.character.class.missing', 'poe2.data.character.ascendancy.missing' => [UpgradeClassification::Structural, 'Complete the reported PoE2 character identity', MarketDataRequirement::NotRequired, ['poe2_build_identity:complete']],
                'poe2.skills.main.missing' => [UpgradeClassification::HighImpact, 'Assign a supported PoE2 main skill', MarketDataRequirement::NotRequired, ['poe2_skills:main_skill_selected']],
                'poe2.data.resistances.unavailable' => [UpgradeClassification::Structural, 'Supply verified PoE2 resistance evidence', MarketDataRequirement::NotRequired, ['poe2_resistances:verified_source']],
                default => null,
            };
            if ($definition === null) {
                continue;
            }
            [$classification, $title, $market, $effects] = $definition;
            $id = 'poe2.upgrade.'.str_replace('poe2.', '', $finding->code);
            $prerequisites = [];
            if ($finding->code === 'poe2.skills.main.missing' && isset($codes['poe2.data.character.class.missing'])) {
                $prerequisites[] = 'poe2.upgrade.data.character.class.missing';
            }
            $candidates[] = new UpgradeCandidate(
                $id,
                GameEdition::Poe2,
                new RulesetReference(GameEdition::Poe2, $analysis->ruleset->id->value, $analysis->ruleset->version->value, $analysis->ruleset->checksumSha256),
                $classification,
                $title,
                $prerequisites,
                [],
                [],
                [$finding->findingId],
                $effects,
                $market === MarketDataRequirement::Required ? BudgetUncertainty::MarketPriceUnknown : BudgetUncertainty::NotApplicable,
                $market,
                $this->scorer->score($finding, $classification, $intent),
            );
        }

        return $candidates;
    }
}

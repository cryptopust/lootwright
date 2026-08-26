<?php

namespace Lootwright\GameAdapters\PoE1\Recommendations;

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

final readonly class Poe1UpgradeCandidateFactory implements UpgradeCandidateFactory
{
    public function __construct(private UpgradePriorityScorer $scorer = new UpgradePriorityScorer) {}

    public function edition(): GameEdition
    {
        return GameEdition::Poe1;
    }

    public function create(AnalysisResult $analysis, BuildIntent $intent): array
    {
        if ($analysis->gameEdition !== GameEdition::Poe1 || $intent->goal->edition !== GameEdition::Poe1) {
            throw new \InvalidArgumentException('The PoE1 candidate factory accepts only matching PoE1 products.');
        }
        $codes = array_fill_keys(array_column($analysis->findings, 'code'), true);
        $candidates = [];
        foreach ($analysis->findings as $finding) {
            $candidate = $this->candidate($analysis, $intent, $finding, $codes);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /** @param array<string,true> $findingCodes */
    private function candidate(AnalysisResult $analysis, BuildIntent $intent, Finding $finding, array $findingCodes): ?UpgradeCandidate
    {
        $definition = match ($finding->code) {
            'data.character.level.missing', 'data.character.class.missing', 'data.character.ascendancy.missing' => [UpgradeClassification::Structural, 'Complete missing character facts', MarketDataRequirement::NotRequired, ['build_snapshot:complete_character_identity']],
            'equipment.required_slot.empty' => [UpgradeClassification::Mandatory, 'Assign an item to each reported empty required slot', MarketDataRequirement::Required, ['required_slots:assigned']],
            'equipment.slot.conflict' => [UpgradeClassification::Structural, 'Resolve conflicting equipment-slot assignments', MarketDataRequirement::NotRequired, ['equipment_slots:single_assignment']],
            'defence.fire_resistance.below_reported_max', 'defence.cold_resistance.below_reported_max', 'defence.lightning_resistance.below_reported_max' => [UpgradeClassification::HighImpact, 'Plan resistance changes against the reported maximum', MarketDataRequirement::Required, ['reported_resistance:not_below_reported_maximum']],
            'defence.chaos_resistance.negative' => [UpgradeClassification::Conditional, 'Review negative reported chaos resistance', MarketDataRequirement::Required, ['reported_chaos_resistance:non_negative']],
            'resources.mana.unreserved_negative', 'resources.mana.reservation_invalid' => [UpgradeClassification::Mandatory, 'Correct the reported mana reservation configuration', MarketDataRequirement::NotRequired, ['mana_reservation:valid']],
            'resources.mana.cost_unsustainable' => [UpgradeClassification::HighImpact, 'Reduce mana cost or increase unreserved mana for the main skill', MarketDataRequirement::NotRequired, ['mana_sustainability:cost_within_unreserved_pool']],
            'attributes.requirement.missing' => [UpgradeClassification::Mandatory, 'Resolve the unmet attribute requirement', MarketDataRequirement::NotRequired, ['attributes:requirements_met']],
            'defence.life.below_content_profile', 'defence.energy_shield.below_content_profile' => [UpgradeClassification::HighImpact, 'Raise the primary defensive pool for the selected content', MarketDataRequirement::Required, ['defence_pool:content_profile_threshold']],
            'defence.armour.low', 'defence.evasion.low', 'defence.block.low', 'defence.spell_block.low', 'defence.suppression.low' => [UpgradeClassification::HighImpact, 'Improve the reported secondary defence layer', MarketDataRequirement::Required, ['defence_layer:reviewed_baseline']],
            'recovery.life.missing' => [UpgradeClassification::HighImpact, 'Add an explicit life recovery source', MarketDataRequirement::Required, ['recovery:regeneration_or_leech']],
            'offence.crit.resolute_technique_conflict' => [UpgradeClassification::Structural, 'Remove incompatible critical-strike scaling or change the keystone plan', MarketDataRequirement::NotRequired, ['crit_scaling:consistent_with_keystone']],
            'skills.gem.disabled' => [UpgradeClassification::Structural, 'Review explicitly disabled gems', MarketDataRequirement::NotRequired, ['disabled_gems:reviewed']],
            'skills.main.insufficient_links' => [UpgradeClassification::HighImpact, 'Plan additional links for the identified main skill group', MarketDataRequirement::Required, ['main_skill_links:meet_ruleset_threshold']],
            'passive_tree.node.unknown' => [UpgradeClassification::Structural, 'Reconcile passive nodes with the active governed snapshot', MarketDataRequirement::NotRequired, ['passive_nodes:known_to_ruleset']],
            default => null,
        };
        if ($definition === null) {
            return null;
        }
        [$classification, $title, $market, $effects] = $definition;
        $id = 'upgrade.'.str_replace(['defence.', 'resources.', 'skills.', 'passive_tree.', 'equipment.', 'data.'], '', $finding->code);
        $prerequisites = [];
        if (str_starts_with($finding->code, 'defence.') && isset($findingCodes['equipment.required_slot.empty'])) {
            $prerequisites[] = 'upgrade.required_slot.empty';
        }
        $slots = array_map(static fn (string $slot): string => 'slot:'.$slot, $finding->affectedSlots);
        $score = $this->scorer->score($finding, $classification, $intent);
        $tradeRequirements = match ($finding->code) {
            'defence.fire_resistance.below_reported_max' => [['modifier_id' => 'defence.fire_resistance', 'mode' => 'required', 'minimum' => '1']],
            'defence.cold_resistance.below_reported_max' => [['modifier_id' => 'defence.cold_resistance', 'mode' => 'required', 'minimum' => '1']],
            'defence.lightning_resistance.below_reported_max' => [['modifier_id' => 'defence.lightning_resistance', 'mode' => 'required', 'minimum' => '1']],
            'defence.chaos_resistance.negative' => [['modifier_id' => 'defence.chaos_resistance', 'mode' => 'required', 'minimum' => '1']],
            default => [],
        };
        $targetSlot = $finding->affectedSlots[0] ?? null;
        if (is_string($targetSlot) && str_starts_with($targetSlot, 'slot:')) {
            $targetSlot = substr($targetSlot, 5);
        }

        return new UpgradeCandidate(
            $id,
            GameEdition::Poe1,
            new RulesetReference(GameEdition::Poe1, $analysis->ruleset->id->value, $analysis->ruleset->version->value, $analysis->ruleset->checksumSha256),
            $market === MarketDataRequirement::Required && $classification === UpgradeClassification::Conditional ? UpgradeClassification::RequiresMarketCheck : $classification,
            $title,
            $prerequisites,
            [],
            $slots,
            [$finding->findingId],
            $effects,
            $market === MarketDataRequirement::Required ? BudgetUncertainty::MarketPriceUnknown : BudgetUncertainty::NotApplicable,
            $market,
            $score,
            tradeRequirements: $tradeRequirements,
            targetSlot: is_string($targetSlot) && $targetSlot !== '' ? $targetSlot : null,
        );
    }
}

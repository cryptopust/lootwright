<?php

namespace Lootwright\GameAdapters\PoE1\Analysis;

final readonly class Poe1PlayerStatAliasRegistry
{
    /** @param array<string, list<string>> $aliases */
    public function __construct(private array $aliases = self::DEFAULT_ALIASES) {}

    /** @param array<string, int|string> $summaryValues
     * @return array<string, int|string>
     */
    public function canonicalize(array $summaryValues): array
    {
        $canonical = [];
        foreach ($this->aliases as $canonicalName => $aliases) {
            $matches = [];
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $summaryValues)) {
                    $matches[] = $summaryValues[$alias];
                }
            }
            if ($matches !== [] && count(array_unique(array_map('strval', $matches))) === 1) {
                $canonical[$canonicalName] = $matches[0];
            }
        }
        ksort($canonical, SORT_STRING);

        return $canonical;
    }

    /** @var array<string, list<string>> */
    public const DEFAULT_ALIASES = [
        'chaos_resistance' => ['ChaosResist', 'ChaosResistance'],
        'cold_resistance' => ['ColdResist', 'ColdResistance'],
        'fire_resistance' => ['FireResist', 'FireResistance'],
        'lightning_resistance' => ['LightningResist', 'LightningResistance'],
        'mana_reserved' => ['ManaReserved', 'ReservedMana'],
        'mana_total' => ['Mana', 'TotalMana'],
        'mana_unreserved' => ['ManaUnreserved', 'UnreservedMana'],
        'maximum_cold_resistance' => ['ColdResistCap', 'MaxColdResist', 'MaximumColdResistance'],
        'maximum_fire_resistance' => ['FireResistCap', 'MaxFireResist', 'MaximumFireResistance'],
        'maximum_lightning_resistance' => ['LightningResistCap', 'MaxLightningResist', 'MaximumLightningResistance'],
        'strength' => ['Str', 'Strength', 'TotalStrength'],
        'dexterity' => ['Dex', 'Dexterity', 'TotalDexterity'],
        'intelligence' => ['Int', 'Intelligence', 'TotalIntelligence'],
        'strength_requirement' => ['StrengthRequirement', 'RequiredStrength'],
        'dexterity_requirement' => ['DexterityRequirement', 'RequiredDexterity'],
        'intelligence_requirement' => ['IntelligenceRequirement', 'RequiredIntelligence'],
        'mana_cost' => ['ManaCost', 'MainSkillManaCost'],
        'mana_regeneration' => ['ManaRegen', 'ManaRegeneration'],
        'life' => ['Life', 'TotalLife'],
        'energy_shield' => ['EnergyShield', 'TotalEnergyShield'],
        'armour' => ['Armour', 'TotalArmour', 'ArmourRating'],
        'evasion' => ['Evasion', 'TotalEvasion', 'EvasionRating'],
        'block_chance' => ['BlockChance', 'TotalBlockChance', 'ChanceToBlock'],
        'spell_block_chance' => ['SpellBlockChance', 'TotalSpellBlockChance', 'ChanceToBlockSpells'],
        'spell_suppression' => ['SpellSuppression', 'SpellSuppressionChance', 'SpellSuppressionPercent'],
        'critical_strike_chance' => ['CriticalStrikeChance', 'CritChance', 'MainHandCriticalStrikeChance'],
    ];
}

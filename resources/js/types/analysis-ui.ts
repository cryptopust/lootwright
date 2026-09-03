import type { LocalizedCopy } from '@/composables/useLocale';

export type GameEdition = 'poe1' | 'poe2';
export type AnalysisSection =
    'overview' | 'findings' | 'upgrades' | 'trade' | 'provenance' | 'states';

export interface EvidenceReference {
    input: string;
    rule: string;
    source: string;
}

export interface DemoFinding {
    code: string;
    severity: 'critical' | 'warning' | 'opportunity' | 'information';
    category: string;
    title: LocalizedCopy;
    summary: LocalizedCopy;
    why: LocalizedCopy;
    limitation: LocalizedCopy;
    confidence: number;
    evidence: EvidenceReference[];
}

export interface DemoUpgrade {
    code: string;
    rank: number;
    slot: string;
    title: LocalizedCopy;
    reason: LocalizedCopy;
    limitation: LocalizedCopy;
    budgetBand: LocalizedCopy;
    dependencies: string[];
    findingCodes: string[];
    confidence: number;
}

export interface DemoFilter {
    label: string;
    minimum?: string;
    maximum?: string;
    weight?: number;
    reason: LocalizedCopy;
    findingCode: string;
}

export interface DemoRecipeVariant {
    required: DemoFilter[];
    optional: DemoFilter[];
    excluded: DemoFilter[];
}

export interface DemoRecipe {
    slot: string;
    category: string;
    baseFamily: string;
    budget: string;
    confidence: number;
    dependencies: string[];
    strict: DemoRecipeVariant;
    broad: DemoRecipeVariant;
}

export interface TradeRecipeFilter {
    canonical_modifier_id: string;
    label: string;
    minimum?: string;
    weight?: number;
}

export interface TradeRecipeView {
    game_edition: GameEdition;
    slot: string;
    item_class: string | null;
    base_constraints: Record<string, unknown>;
    rarity: string | null;
    influence_or_edition_equivalent: string | null;
    corruption_constraints: string | null;
    required_modifiers: TradeRecipeFilter[];
    optional_modifiers: TradeRecipeFilter[];
    excluded_modifiers: TradeRecipeFilter[];
    minimum_values: Record<string, string>;
    weights: Record<string, number>;
    dependencies: Array<{ slot: string; reason: string }>;
    broad_recipe: string;
    strict_recipe: string;
    explanation: string;
    provenance: {
        source_id: string;
        source_version: string;
        checksum_sha256: string;
    };
    unsupported_filters: Array<{
        modifier_id?: string;
        candidate?: string;
        reason: string;
    }>;
    ruleset: {
        edition: GameEdition;
        id: string;
        version: string;
        checksum_sha256: string;
    };
}

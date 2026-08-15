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

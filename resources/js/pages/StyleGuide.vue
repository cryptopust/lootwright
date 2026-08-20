<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import FindingRow from '@/components/analysis/FindingRow.vue';
import UpgradeRow from '@/components/analysis/UpgradeRow.vue';
import AppShell from '@/components/app/AppShell.vue';
import EvidenceCallout from '@/components/arpg/EvidenceCallout.vue';
import ItemCard from '@/components/arpg/ItemCard.vue';
import type { Rarity } from '@/components/arpg/RarityBadge.vue';
import RarityBadge from '@/components/arpg/RarityBadge.vue';
import ScopePanel from '@/components/arpg/ScopePanel.vue';
import StatChip from '@/components/arpg/StatChip.vue';
import TerminalBlock from '@/components/arpg/TerminalBlock.vue';
import { demoFindings, demoUpgrades } from '@/data/demo-analysis';

const rarities: Rarity[] = [
    'normal',
    'magic',
    'rare',
    'unique',
    'currency',
    'corrupted',
];
const item = {
    slot: 'HELMET · FIXTURE',
    name: 'Ember Ledger',
    baseName: 'Neutral Test Base',
    rarity: 'rare' as const,
    ilvl: 84,
    fixture: true,
    affixes: [
        {
            text: '+# to maximum Life',
            value: '+92',
            minimum: 80,
            maximum: 99,
            roll: 92,
            tier: 'T1',
        },
        {
            text: '+#% to Cold Resistance',
            value: '+41%',
            minimum: 36,
            maximum: 41,
            roll: 41,
            tier: 'T2',
        },
        {
            text: 'Crafted fixture affix',
            value: '+20',
            minimum: 16,
            maximum: 20,
            roll: 20,
            tier: 'crafted',
            crafted: true,
        },
    ],
};
const recipe =
    '# Fixture tarifi, canlı Trade sorgusu değildir\nslot = helmet\nlife.min = 80\ncold_resistance.min = 36\nbudget = bilinmiyor';
const finding = demoFindings[0]!;
const upgrade = demoUpgrades[0]!;
</script>

<template>
    <Head title="ARPG tasarım sistemi" />
    <AppShell :contained="false">
        <header class="page-heading">
            <p class="kicker">LOOTWRIGHT / DESIGN SYSTEM</p>
            <h1>Kanıt odaklı ARPG arayüz dili</h1>
            <p>
                Token, nadirlik, durum ve bileşen sözleşmelerinin fixture
                galerisi. Canlı oyun veya piyasa verisi içermez.
            </p>
        </header>
        <section class="style-guide-section" aria-labelledby="tokens-title">
            <p class="kicker">01 / TOKENS</p>
            <h2 id="tokens-title">Renk rolleri</h2>
            <div class="style-guide-grid">
                <div
                    v-for="token in [
                        'background',
                        'surface',
                        'surface-raised',
                        'foreground',
                        'muted-foreground',
                        'primary',
                        'accent',
                        'border',
                    ]"
                    :key="token"
                    class="token-swatch"
                    :class="`swatch-${token}`"
                >
                    <strong>{{ token }}</strong
                    ><code>var(--color-{{ token }})</code>
                </div>
            </div>
        </section>
        <section class="style-guide-section" aria-labelledby="rarities-title">
            <p class="kicker">02 / RARITY</p>
            <h2 id="rarities-title">Metinle doğrulanan nadirlik</h2>
            <div class="rarity-gallery">
                <RarityBadge
                    v-for="rarity in rarities"
                    :key="rarity"
                    :rarity="rarity"
                />
            </div>
        </section>
        <section class="style-guide-section" aria-labelledby="type-title">
            <p class="kicker">03 / TYPOGRAPHY</p>
            <h2 id="type-title">Üç işlev, üç yazı karakteri</h2>
            <div class="type-specimens">
                <div>
                    <span>DISPLAY / NEWSREADER</span>
                    <h3>Kanıt önce gelir.</h3>
                </div>
                <div>
                    <span>BODY / DM SANS</span>
                    <p>
                        Belirsiz veri tahmin edilmez; açıkça bilinmiyor olarak
                        işaretlenir.
                    </p>
                </div>
                <div>
                    <span>MONO / JETBRAINS MONO</span
                    ><code>res.chaos.cap · T1 · ilvl 84 · 75%</code>
                </div>
            </div>
        </section>
        <section class="style-guide-section" aria-labelledby="stats-title">
            <p class="kicker">04 / STATES</p>
            <h2 id="stats-title">Sayısal ve bilinmeyen değerler</h2>
            <div class="style-guide-grid">
                <StatChip label="Maximum Life" value="3,842" /><StatChip
                    label="Cold Resistance"
                    value="71%"
                /><StatChip
                    label="Exact price"
                    :value="null"
                    note="no-live-listings"
                /><StatChip label="Ruleset" value="1.4.2-fixture" />
            </div>
        </section>
        <section
            class="style-guide-section component-showcase"
            aria-labelledby="components-title"
        >
            <p class="kicker">05 / COMPONENTS</p>
            <h2 id="components-title">Item ve kanıt bileşenleri</h2>
            <div class="showcase-columns">
                <ItemCard v-bind="item" />
                <div class="showcase-stack">
                    <EvidenceCallout
                        rule="res.cold.cap"
                        source="LOOTWRIGHT-001 / fixture-1"
                        title="Cold Resistance fixture eşiğinin altında"
                        >Bu iddia yalnız sürüm sabit fixture snapshot'ından
                        gelir. Fiyat veya hayatta kalma tahmini
                        değildir.</EvidenceCallout
                    ><TerminalBlock :content="recipe" />
                </div>
            </div>
        </section>
        <section class="style-guide-section" aria-labelledby="results-title">
            <p class="kicker">06 / RESULTS</p>
            <h2 id="results-title">Bulgu ve yükseltme sırası</h2>
            <div class="result-specimens">
                <FindingRow :finding="finding" />
                <UpgradeRow :upgrade="upgrade" />
            </div>
        </section>
        <section class="style-guide-section" aria-labelledby="scope-title">
            <p class="kicker">07 / POLICY</p>
            <h2 id="scope-title">Ürün sınırı</h2>
            <ScopePanel
                :does="[
                    'Deterministik bulgu üretir',
                    'Kanıt ve ruleset gösterir',
                    'Manuel Trade tarifi verir',
                ]"
                :does-not="[
                    'Canlı ilan getirmez',
                    'Fiyat uydurmaz',
                    'Oyuncu adına işlem yapmaz',
                ]"
            />
        </section>
    </AppShell>
</template>

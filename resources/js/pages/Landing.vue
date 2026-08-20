<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppShell from '@/components/app/AppShell.vue';
import EditionBadge from '@/components/app/EditionBadge.vue';
import ItemCard from '@/components/arpg/ItemCard.vue';
import ScopePanel from '@/components/arpg/ScopePanel.vue';
import { useLocale } from '@/composables/useLocale';

const { locale, tx } = useLocale();

const workflow = [
    {
        number: '01',
        tr: 'Kendi build girdini ve hedefini gönder.',
        en: 'Submit your own build input and goal.',
        meta: 'USER CONTROLLED INPUT',
    },
    {
        number: '02',
        tr: 'Sürüm sabit kurallar deterministik bulgular üretir.',
        en: 'Version-pinned rules produce deterministic findings.',
        meta: 'NO AI CALCULATION',
    },
    {
        number: '03',
        tr: 'Öncelikli yükseltmeleri ve manuel arama tarifini incele.',
        en: 'Review prioritized upgrades and a manual search recipe.',
        meta: 'PLAYER ACTS MANUALLY',
    },
];
const showcaseItem = {
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
    ],
};
</script>

<template>
    <Head
        :title="
            tx({
                tr: 'Kanıta dayalı build analizi',
                en: 'Evidence-led build analysis',
            })
        "
    >
        <meta
            head-key="description"
            name="description"
            content="Lootwright produces deterministic Path of Exile build findings, prioritized upgrades, and manual Trade-filter recipes from user-supplied inputs."
        />
    </Head>

    <AppShell current="home" :contained="false">
        <section class="landing-hero" aria-labelledby="landing-title">
            <div class="landing-copy">
                <p class="kicker">
                    {{
                        tx({
                            tr: 'Kanıt odaklı build analizi',
                            en: 'Evidence-led build analysis',
                        })
                    }}
                </p>
                <h1 id="landing-title">
                    {{
                        tx({ tr: 'Build kararlarını', en: 'Build decisions,' })
                    }}
                    <em>{{
                        tx({
                            tr: 'kanıta dönüştür.',
                            en: 'written in evidence.',
                        })
                    }}</em>
                </h1>
                <p class="lede">
                    {{
                        tx({
                            tr: 'Lootwright, gönderdiğin build girdisini sürüm sabit kurallarla analiz eder; bulguları açıklar, yükseltmeleri sıralar ve elle uygulayabileceğin bir Trade filtre tarifi hazırlar.',
                            en: 'Lootwright analyzes the build input you submit with version-pinned rules, explains findings, ranks upgrades, and drafts a Trade filter recipe you apply yourself.',
                        })
                    }}
                </p>
                <div class="hero-actions">
                    <a class="button is-primary" href="/analyses/new">
                        {{
                            tx({
                                tr: 'Yeni analiz başlat',
                                en: 'Start a new analysis',
                            })
                        }}
                    </a>
                    <a class="text-link" href="/analyses/demo/overview">
                        {{
                            tx({
                                tr: 'Fixture demosunu incele',
                                en: 'Explore the fixture demo',
                            })
                        }}
                    </a>
                </div>
                <div class="edition-line">
                    <EditionBadge edition="poe1" />
                    <span>{{
                        tx({ tr: 'MVP analizi', en: 'MVP analysis' })
                    }}</span>
                    <EditionBadge edition="poe2" />
                    <span>{{
                        tx({
                            tr: 'Yalnızca format inceleme',
                            en: 'Format review only',
                        })
                    }}</span>
                </div>
            </div>

            <ScopePanel
                :does="[
                    tx({
                        tr: 'Deterministik bulgular',
                        en: 'Deterministic findings',
                    }),
                    tx({
                        tr: 'Kanıtlı yükseltme sırası',
                        en: 'Evidence-backed upgrade order',
                    }),
                    tx({
                        tr: 'Manuel Trade filtre tarifi',
                        en: 'Manual Trade filter recipe',
                    }),
                    tx({
                        tr: 'Kaynak ve ruleset görünürlüğü',
                        en: 'Source and ruleset visibility',
                    }),
                ]"
                :does-not="[
                    tx({
                        tr: 'Canlı fiyat veya ilan çekmez',
                        en: 'No live prices or listings',
                    }),
                    tx({
                        tr: 'Trade araması otomatikleştirmez',
                        en: 'No automated Trade search',
                    }),
                    tx({
                        tr: 'Oyuna veya tarayıcıya dokunmaz',
                        en: 'No game or browser control',
                    }),
                    tx({
                        tr: 'AI ile hesap yapmaz',
                        en: 'No AI-authored calculations',
                    }),
                ]"
            />
        </section>

        <section class="landing-showcase" aria-labelledby="showcase-title">
            <div class="section-intro">
                <p class="kicker">ITEM / EVIDENCE</p>
                <h2 id="showcase-title">Roll, tier ve kanıt aynı yüzeyde.</h2>
                <p>
                    Bu örnek yalnız özgün fixture verisidir. Nadirlik hem renk
                    hem metinle belirtilir; elde olmayan fiyat açıkça bilinmiyor
                    kalır.
                </p>
            </div>
            <ItemCard v-bind="showcaseItem" />
        </section>

        <section class="landing-workflow" aria-labelledby="workflow-title">
            <div class="section-intro">
                <p class="kicker">
                    {{ tx({ tr: 'İş akışı', en: 'Workflow' }) }}
                </p>
                <h2 id="workflow-title">
                    {{
                        tx({
                            tr: 'Karar sende kalır.',
                            en: 'Judgment stays with you.',
                        })
                    }}
                </h2>
                <p>
                    {{
                        tx({
                            tr: 'AI kapalıyken de aynı deterministik sonuçları alırsın. Açılırsa yalnızca niyeti yapılandırır veya mevcut sonuçları sade dille açıklar.',
                            en: 'The deterministic result remains available with AI off. When enabled, AI only structures intent or explains results already produced.',
                        })
                    }}
                </p>
            </div>
            <ol class="workflow-ledger">
                <li v-for="step in workflow" :key="step.number">
                    <span class="ledger-number">{{ step.number }}</span>
                    <strong>{{ locale === 'tr' ? step.tr : step.en }}</strong>
                    <small>{{ step.meta }}</small>
                </li>
            </ol>
        </section>

        <section class="landing-boundary" aria-labelledby="boundary-title">
            <p class="kicker">
                {{ tx({ tr: 'Bilinçli sınır', en: 'A deliberate boundary' }) }}
            </p>
            <h2 id="boundary-title">
                {{
                    tx({
                        tr: 'Bir bot değil. Bir fiyat aracı değil. Bir oyun istemcisi değil.',
                        en: 'Not a bot. Not a price tool. Not a game client.',
                    })
                }}
            </h2>
            <p>
                {{
                    tx({
                        tr: 'Lootwright yalnızca web üzerinde çalışır. Site kazımaz, canlı Trade endpointlerine çağrı yapmaz, oyunu veya bilgisayarını incelemez ve senin adına hiçbir işlem gerçekleştirmez.',
                        en: 'Lootwright is web-only. It does not scrape sites, call live Trade endpoints, inspect the game or your computer, or act on your behalf.',
                    })
                }}
            </p>
            <a class="text-link" href="/limitations">{{
                tx({ tr: 'Tüm sınırlamaları oku', en: 'Read all limitations' })
            }}</a>
        </section>
    </AppShell>
</template>

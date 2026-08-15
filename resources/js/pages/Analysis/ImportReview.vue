<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppShell from '@/components/app/AppShell.vue';
import EditionBadge from '@/components/app/EditionBadge.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import { useLocale } from '@/composables/useLocale';

const { tx } = useLocale();
</script>

<template>
    <Head :title="tx({ tr: 'Import incelemesi', en: 'Import review' })" />
    <AppShell current="demo">
        <header class="page-heading is-split">
            <div>
                <p class="kicker">DEMO / IMPORT REVIEW</p>
                <h1>
                    {{
                        tx({
                            tr: 'Parser ne gördü?',
                            en: 'What did the parser see?',
                        })
                    }}
                </h1>
                <p>
                    {{
                        tx({
                            tr: 'Bu ekran yalnızca fixture verisi gösterir. Hiçbir oyun veya Trade endpointine bağlanmaz.',
                            en: 'This screen shows fixture data only. It connects to no game or Trade endpoint.',
                        })
                    }}
                </p>
            </div>
            <EditionBadge edition="poe1" />
        </header>

        <StatusBanner
            tone="warning"
            :title="tx({ tr: 'Kısmi import', en: 'Partial import' })"
            :body="
                tx({
                    tr: 'Build edition kesin olarak PoE1 tespit edildi; bir support eşlemesi çözümlenemedi ve analizden çıkarıldı.',
                    en: 'The build edition was proven as PoE1; one support mapping was unresolved and excluded from analysis.',
                })
            "
        />

        <section class="review-summary" aria-labelledby="identity-title">
            <div>
                <p class="kicker">{{ tx({ tr: 'Kimlik', en: 'Identity' }) }}</p>
                <h2 id="identity-title">
                    {{
                        tx({
                            tr: 'Edition ve sürüm zinciri',
                            en: 'Edition and version chain',
                        })
                    }}
                </h2>
            </div>
            <dl class="identity-ledger">
                <div>
                    <dt>
                        {{
                            tx({
                                tr: 'Tespit edilen edition',
                                en: 'Detected edition',
                            })
                        }}
                    </dt>
                    <dd><EditionBadge edition="poe1" compact /></dd>
                </div>
                <div>
                    <dt>Adapter</dt>
                    <dd><code>pob1-fixture</code></dd>
                </div>
                <div>
                    <dt>Parser</dt>
                    <dd><code>1.0.0</code></dd>
                </div>
                <div>
                    <dt>
                        {{ tx({ tr: 'Patch işareti', en: 'Patch marker' }) }}
                    </dt>
                    <dd><code>3.27.0-fixture</code></dd>
                </div>
                <div>
                    <dt>Ruleset</dt>
                    <dd><code>1.4.2-fixture</code></dd>
                </div>
                <div>
                    <dt>{{ tx({ tr: 'Uyumluluk', en: 'Compatibility' }) }}</dt>
                    <dd>
                        <span class="status-chip is-confirmed">{{
                            tx({
                                tr: 'Fixture uyumlu',
                                en: 'Fixture compatible',
                            })
                        }}</span>
                    </dd>
                </div>
            </dl>
        </section>

        <section class="diagnostic-section" aria-labelledby="warnings-title">
            <div class="section-intro compact">
                <p class="kicker">PARSER DIAGNOSTICS</p>
                <h2 id="warnings-title">
                    {{
                        tx({
                            tr: 'Uyarılar ve destek sınırı',
                            en: 'Warnings and support boundary',
                        })
                    }}
                </h2>
            </div>
            <ol class="diagnostic-list">
                <li>
                    <span class="diagnostic-code">W-104</span>
                    <div>
                        <strong>{{
                            tx({
                                tr: 'Support eşlemesi çözümlenemedi',
                                en: 'Support mapping unresolved',
                            })
                        }}</strong>
                        <p>
                            {{
                                tx({
                                    tr: 'Ana beceri grubundaki bir support canonical fixture sözlüğünde bulunamadı. Benzer bir isim tahmin edilmedi.',
                                    en: 'One support in the main skill group was absent from the canonical fixture vocabulary. No similar name was guessed.',
                                })
                            }}
                        </p>
                    </div>
                    <span class="status-chip is-warning">{{
                        tx({ tr: 'Analiz dışı', en: 'Excluded' })
                    }}</span>
                </li>
                <li>
                    <span class="diagnostic-code">I-021</span>
                    <div>
                        <strong>{{
                            tx({
                                tr: 'Koşullu flask etkileri doğrulanmadı',
                                en: 'Conditional flask effects unproven',
                            })
                        }}</strong>
                        <p>
                            {{
                                tx({
                                    tr: 'Koşullu etkiler snapshotta saklandı ancak savunma bulgularına katılmadı.',
                                    en: 'Conditional effects were retained in the snapshot but excluded from defence findings.',
                                })
                            }}
                        </p>
                    </div>
                    <span class="status-chip is-neutral">{{
                        tx({ tr: 'Bilgi', en: 'Information' })
                    }}</span>
                </li>
            </ol>
        </section>

        <section
            class="unsupported-section"
            aria-labelledby="unsupported-title"
        >
            <div>
                <h2 id="unsupported-title">
                    {{
                        tx({
                            tr: 'Desteklenmeyen özellikler',
                            en: 'Unsupported features',
                        })
                    }}
                </h2>
                <p>
                    {{
                        tx({
                            tr: 'Bu alanlar sessizce varsayılmadı ve sonuç puanına eklenmedi.',
                            en: 'These fields were not silently assumed or included in result scoring.',
                        })
                    }}
                </p>
            </div>
            <ul class="unsupported-list">
                <li>
                    Custom config text
                    <span>{{
                        tx({
                            tr: 'saklandı, yorumlanmadı',
                            en: 'retained, not interpreted',
                        })
                    }}</span>
                </li>
                <li>
                    Conditional enemy state
                    <span>{{
                        tx({ tr: 'kanıtlanmadı', en: 'not proven' })
                    }}</span>
                </li>
                <li>
                    Unknown support identifier
                    <span>{{
                        tx({
                            tr: 'açıklama gerekli',
                            en: 'clarification required',
                        })
                    }}</span>
                </li>
            </ul>
        </section>

        <div class="page-actions">
            <a class="button is-secondary" href="/analyses/new">{{
                tx({ tr: 'Girdiyi düzelt', en: 'Edit input' })
            }}</a>
            <a class="button is-primary" href="/analyses/demo/overview">{{
                tx({
                    tr: 'Kısmi sonuçla devam et',
                    en: 'Continue with partial result',
                })
            }}</a>
        </div>
    </AppShell>
</template>

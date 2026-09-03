<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppShell from '@/components/app/AppShell.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import { useLocale } from '@/composables/useLocale';

const { tx } = useLocale();
const props = defineProps<{
    usage?: {
        calls_today: number;
        cost_today_micro_usd: number;
        calls_month: number;
        cost_month_micro_usd: number;
        input_tokens_month: number;
        output_tokens_month: number;
        failures_month: number;
    };
}>();
const usage = () =>
    props.usage ?? {
        calls_today: 0,
        cost_today_micro_usd: 0,
        calls_month: 0,
        cost_month_micro_usd: 0,
        input_tokens_month: 0,
        output_tokens_month: 0,
        failures_month: 0,
    };
</script>

<template>
    <Head
        :title="tx({ tr: 'AI kullanım ve maliyet', en: 'AI usage and cost' })"
    />
    <AppShell>
        <header class="page-heading">
            <p class="kicker">YOUR USAGE / PRIVATE</p>
            <h1>
                {{
                    tx({
                        tr: 'AI kullanım ve maliyet',
                        en: 'AI usage and cost',
                    })
                }}
            </h1>
            <p>
                {{
                    tx({
                        tr: 'Yalnızca kendi hash’lenmiş kullanım metadata’nı görürsün. Ham prompt veya provider yanıtı burada tutulmaz.',
                        en: 'You see only your own hashed usage metadata. Raw prompts and provider responses are not stored here.',
                    })
                }}
            </p>
        </header>

        <StatusBanner
            tone="neutral"
            :title="
                tx({
                    tr: 'AI sağlayıcısı kapalı',
                    en: 'AI provider is disabled',
                })
            "
            :body="
                tx({
                    tr: 'Policy Gate execution için allow vermedi. Deterministik analiz ve yerel açıklamalar etkilenmez.',
                    en: 'Policy Gate has not granted execution. Deterministic analysis and local explanations are unaffected.',
                })
            "
        />

        <section class="usage-summary" aria-label="Usage summary">
            <div>
                <span>{{ tx({ tr: 'Bugünkü çağrı', en: 'Calls today' }) }}</span
                ><strong>{{ usage().calls_today }}</strong
                ><small>{{
                    tx({ tr: 'harici provider', en: 'external provider' })
                }}</small>
            </div>
            <div>
                <span>{{
                    tx({ tr: 'Bugünkü maliyet', en: 'Cost today' })
                }}</span
                ><strong
                    >${{
                        (usage().cost_today_micro_usd / 1_000_000).toFixed(6)
                    }}</strong
                ><small>{{ tx({ tr: 'gerçekleşen', en: 'actual' }) }}</small>
            </div>
            <div>
                <span>{{
                    tx({
                        tr: 'Aylık circuit breaker',
                        en: 'Monthly circuit breaker',
                    })
                }}</span
                ><strong>{{ usage().calls_month }} calls</strong
                ><small>{{ tx({ tr: 'kullanıldı', en: 'used' }) }}</small>
            </div>
        </section>

        <section
            class="usage-table-section"
            aria-labelledby="usage-table-title"
        >
            <div class="section-title-row">
                <div>
                    <p class="kicker">AUDIT METADATA</p>
                    <h2 id="usage-table-title">
                        {{ tx({ tr: 'Son istekler', en: 'Recent requests' }) }}
                    </h2>
                </div>
            </div>
            <div class="empty-state">
                <span aria-hidden="true">0</span>
                <h3>{{ tx({ tr: 'AI isteği yok', en: 'No AI requests' }) }}</h3>
                <p>
                    {{
                        tx({
                            tr: 'Sağlayıcı kapalı olduğundan token veya maliyet kaydı oluşmadı.',
                            en: 'No token or cost record exists because the provider is disabled.',
                        })
                    }}
                </p>
            </div>
        </section>
    </AppShell>
</template>

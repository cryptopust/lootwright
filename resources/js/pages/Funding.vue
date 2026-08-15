<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppShell from '@/components/app/AppShell.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import { useLocale } from '@/composables/useLocale';

interface CostProjection {
    scenario: string;
    analyses_per_month: number;
    ai_usage_rate_basis_points: number;
    estimated_ai_calls: number;
    hosting_monthly_cents: number;
    ai_monthly_cents: number;
    total_monthly_cents: number;
}

interface FundingStatus {
    requested_enabled: boolean;
    enabled: boolean;
    accepting_funds: boolean;
    policy_decision: string;
    activation_requirements: Record<string, boolean>;
    currency: string;
    pricing_model: string;
    pricing_reviewed_on: string;
    pricing_source: string;
    cost_projections: CostProjection[];
}

const props = defineProps<{ funding: FundingStatus }>();
const { locale, tx } = useLocale();

function money(cents: number): string {
    return new Intl.NumberFormat(locale.value === 'tr' ? 'tr-TR' : 'en-US', {
        style: 'currency',
        currency: props.funding.currency,
        maximumFractionDigits: 2,
    }).format(cents / 100);
}

function scenarioName(scenario: string): string {
    const names: Record<string, { tr: string; en: string }> = {
        low: { tr: 'Düşük', en: 'Low' },
        base: { tr: 'Temel', en: 'Base' },
        high: { tr: 'Yüksek', en: 'High' },
    };

    return names[scenario] ? tx(names[scenario]) : scenario;
}
</script>

<template>
    <Head :title="tx({ tr: 'Finansman durumu', en: 'Funding status' })" />
    <AppShell>
        <header class="information-hero funding-hero">
            <p class="kicker">FUNDING POLICY / DISABLED</p>
            <h1>
                {{
                    tx({
                        tr: 'Açık kaynak bir sürdürülebilirlik hedefi. Para talebi yok.',
                        en: 'An open-source sustainability goal, with no request for money.',
                    })
                }}
            </h1>
            <p>
                {{
                    tx({
                        tr: 'Lootwright açık kaynaklıdır ve Grinding Gear Games ile bağlantılı veya GGG tarafından onaylanmış değildir. Finansman, policy ve bağımsız hukuki inceleme tamamlanana kadar kapalıdır.',
                        en: 'Lootwright is open source and is not affiliated with or endorsed by Grinding Gear Games. Funding is disabled until policy and independent legal review are complete.',
                    })
                }}
            </p>
        </header>

        <StatusBanner
            tone="warning"
            :title="
                tx({
                    tr: 'Finansman policy ile kapalı',
                    en: 'Funding is disabled by policy',
                })
            "
            :body="
                tx({
                    tr: 'Bu sayfada ödeme, bağış, sponsor, affiliate, reklam, gelir üreten sosyal bağlantı, waitlist veya iletişimle finansman aksiyonu yoktur.',
                    en: 'This page contains no payment, donation, sponsorship, affiliate, advertising, revenue-generating social, waitlist, or contact-to-fund action.',
                })
            "
        />

        <section class="funding-costs" aria-labelledby="funding-costs-title">
            <div class="section-heading">
                <p class="kicker">PROJECTED OPERATING COST</p>
                <h2 id="funding-costs-title">
                    {{
                        tx({
                            tr: 'Maliyet tahmini, vaat veya fatura değil',
                            en: 'A cost projection, not a promise or invoice',
                        })
                    }}
                </h2>
                <p>
                    {{
                        tx({
                            tr: 'Rakamlar yapılandırılmış trafik, hosting ve token varsayımlarından üretilen aylık projeksiyonlardır. Oyuncu veya build verisi bu hesaba girmez.',
                            en: 'These are monthly projections from configured traffic, hosting, and token assumptions. No player or build data enters this accounting.',
                        })
                    }}
                </p>
            </div>

            <div class="funding-table-wrap">
                <table>
                    <caption>
                        {{
                            tx({
                                tr: 'Düşük, temel ve yüksek aylık trafik senaryoları',
                                en: 'Low, base, and high monthly traffic scenarios',
                            })
                        }}
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">
                                {{ tx({ tr: 'Senaryo', en: 'Scenario' }) }}
                            </th>
                            <th scope="col">
                                {{ tx({ tr: 'Analiz', en: 'Analyses' }) }}
                            </th>
                            <th scope="col">
                                {{ tx({ tr: 'Hosting', en: 'Hosting' }) }}
                            </th>
                            <th scope="col">
                                {{
                                    tx({ tr: 'AI tahmini', en: 'AI estimate' })
                                }}
                            </th>
                            <th scope="col">
                                {{ tx({ tr: 'Toplam', en: 'Total' }) }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="projection in funding.cost_projections"
                            :key="projection.scenario"
                        >
                            <th scope="row">
                                {{ scenarioName(projection.scenario) }}
                            </th>
                            <td>
                                {{
                                    projection.analyses_per_month.toLocaleString(
                                        locale,
                                    )
                                }}
                            </td>
                            <td>
                                {{ money(projection.hosting_monthly_cents) }}
                            </td>
                            <td>{{ money(projection.ai_monthly_cents) }}</td>
                            <td>{{ money(projection.total_monthly_cents) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p class="funding-source-note">
                {{
                    tx({
                        tr: `${funding.pricing_model} standart token fiyatı ${funding.pricing_reviewed_on} tarihinde resmî OpenAI API fiyatlandırma belgesinden kaydedildi. Ücretsiz kredi, sponsorluk veya program uygunluğu varsayılmamıştır.`,
                        en: `The standard ${funding.pricing_model} token price was recorded from the official OpenAI API pricing documentation on ${funding.pricing_reviewed_on}. No free credits, sponsorship, or program eligibility is assumed.`,
                    })
                }}
            </p>
        </section>

        <section
            class="funding-reporting"
            aria-labelledby="funding-reporting-title"
        >
            <p class="kicker">IF POLICY CHANGES</p>
            <h2 id="funding-reporting-title">
                {{
                    tx({
                        tr: 'Gelecekteki raporlama ilkesi',
                        en: 'Future reporting principle',
                    })
                }}
            </h2>
            <p>
                {{
                    tx({
                        tr: 'Gönüllü finansman bir gün açıkça izin alırsa; alınan toplam, sağlayıcı ücretleri, vergi ve işletme giderleri dönemsel olarak yayımlanır. Bireysel bağışçı kimliği oyuncu, build veya öneri verisiyle birleştirilmez.',
                        en: 'If voluntary funding is ever explicitly permitted, aggregate receipts, provider fees, taxes, and operating expenses would be reported periodically. Individual supporter identity would not be joined to player, build, or recommendation data.',
                    })
                }}
            </p>
        </section>

        <section
            class="funding-principles"
            aria-labelledby="funding-principles-title"
        >
            <div>
                <p class="kicker">PERMANENT EQUALITY</p>
                <h2 id="funding-principles-title">
                    {{
                        tx({
                            tr: 'Para sonucu değiştiremez',
                            en: 'Money cannot change the result',
                        })
                    }}
                </h2>
            </div>
            <ol>
                <li>
                    <span>01</span>
                    <strong>{{
                        tx({ tr: 'Aynı ürün', en: 'Same product' })
                    }}</strong>
                    <p>
                        {{
                            tx({
                                tr: 'Özellik, kullanım veya AI kotası, adapter, veri, destek süresi ve kuyruk önceliği satın alınamaz.',
                                en: 'Features, usage or AI quota, adapters, data, support response time, and queue priority cannot be purchased.',
                            })
                        }}
                    </p>
                </li>
                <li>
                    <span>02</span>
                    <strong>{{
                        tx({ tr: 'Aynı sonuç', en: 'Same result' })
                    }}</strong>
                    <p>
                        {{
                            tx({
                                tr: 'Doğruluk, ruleset, bulgu, öneri, sıralama veya topluluk görünürlüğü bağışçı durumundan etkilenemez.',
                                en: 'Accuracy, rulesets, findings, recommendations, ranking, and community visibility cannot be affected by supporter status.',
                            })
                        }}
                    </p>
                </li>
                <li>
                    <span>03</span>
                    <strong>{{
                        tx({
                            tr: 'Oyun avantajı yok',
                            en: 'No gameplay advantage',
                        })
                    }}</strong>
                    <p>
                        {{
                            tx({
                                tr: 'Hiçbir katkı oyun içi avantaj, daha iyi tavsiye veya GGG ile ilgili bir hak satın alamaz.',
                                en: 'No contribution could purchase gameplay advantage, better advice, or any GGG-related entitlement.',
                            })
                        }}
                    </p>
                </li>
            </ol>
        </section>

        <p class="funding-final-note" role="note">
            {{
                tx({
                    tr: `Durum: ${funding.policy_decision}. Bu bilgi sayfası para toplamıyor ve OpenAI ya da GGG desteği ima etmiyor.`,
                    en: `Status: ${funding.policy_decision}. This informational page collects no money and implies no OpenAI or GGG support.`,
                })
            }}
        </p>
    </AppShell>
</template>

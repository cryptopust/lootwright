<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
import type { User } from '@/types';
const props = defineProps<{
    users: {
        total: number;
        active: number;
        suspended: number;
        verified: number;
    };
    analyses: Record<string, number>;
    failedJobs: number;
    killSwitches: number;
    catalogs: Array<{ game: string; version: string; data_version: string }>;
    source: null | {
        source_key: string;
        status: string;
        completed_at: string | null;
        failure_code: string | null;
    };
    aiCostMicroUsd: number;
    aiRuntime: {
        global_enabled: boolean;
        intent_enabled: boolean;
        explanation_enabled: boolean;
        circuit_open_until: string | null;
        consecutive_provider_failures: number;
        global_daily_budget_micro_usd: number;
        global_monthly_budget_micro_usd: number;
        global_daily_budget_ceiling_micro_usd: number;
        global_monthly_budget_ceiling_micro_usd: number;
    };
    aiUsage: {
        calls_today: number;
        input_tokens_today: number;
        output_tokens_today: number;
        cost_today_micro_usd: number;
        cost_month_micro_usd: number;
        cache_hits_today: number;
        failures_today: number;
    };
    analysisHealth: { failure_rate_percent: number; unsupported_rate_percent: number; queue_failures: number };
}>();
const actor = usePage<{ auth: { user: User } }>().props.auth.user;
const aiForm = useForm({
    global_enabled: props.aiRuntime.global_enabled,
    intent_enabled: props.aiRuntime.intent_enabled,
    explanation_enabled: props.aiRuntime.explanation_enabled,
    global_daily_budget_micro_usd:
        props.aiRuntime.global_daily_budget_micro_usd,
    global_monthly_budget_micro_usd:
        props.aiRuntime.global_monthly_budget_micro_usd,
    reason: '',
});
</script>
<template>
    <Head title="Admin" /><AppShell :contained="false"
        ><AdminNav current="dashboard" />
        <header class="page-heading">
            <p class="kicker">Yetkili operasyon görünümü</p>
            <h1>Admin paneli</h1>
            <p>Hassas içerik, raw artifact, prompt veya secret gösterilmez.</p>
        </header>
        <dl class="metric-strip">
            <div>
                <dt>Üye</dt>
                <dd>{{ users.total }}</dd>
            </div>
            <div>
                <dt>Aktif</dt>
                <dd>{{ users.active }}</dd>
            </div>
            <div>
                <dt>Askıda</dt>
                <dd>{{ users.suspended }}</dd>
            </div>
            <div>
                <dt>Doğrulanmış</dt>
                <dd>{{ users.verified }}</dd>
            </div>
        </dl>
        <div class="admin-columns">
            <section class="data-section">
                <h2>Analiz durumları</h2>
                <dl class="detail-ledger">
                    <div v-for="(total, state) in analyses" :key="state">
                        <dt>{{ state }}</dt>
                        <dd>{{ total }}</dd>
                    </div>
                </dl>
            </section>
            <section class="data-section">
                <h2>Sistem sinyalleri</h2>
                <dl class="detail-ledger">
                    <div>
                        <dt>Başarısız job</dt>
                        <dd>{{ failedJobs }}</dd>
                    </div>
                    <div>
                        <dt>Aktif kill switch</dt>
                        <dd>{{ killSwitches }}</dd>
                    </div>
                    <div>
                        <dt>Katalog</dt>
                        <dd>
                            <span
                                v-for="catalog in catalogs"
                                :key="catalog.game"
                                >{{ catalog.game }} {{ catalog.version }} ·
                                {{ catalog.data_version }}<br
                            /></span>
                        </dd>
                    </div>
                    <div>
                        <dt>Kaynak sağlığı</dt>
                        <dd>{{ source?.status ?? 'snapshot yok' }}</dd>
                    </div>
                    <div>
                        <dt>Aggregate AI maliyeti</dt>
                        <dd>
                            {{ (aiCostMicroUsd / 1_000_000).toFixed(4) }} USD
                        </dd>
                    </div>
                    <div>
                        <dt>AI runtime</dt>
                        <dd>
                            {{
                                aiRuntime.global_enabled
                                    ? 'enabled'
                                    : 'disabled'
                            }}
                            · intent
                            {{ aiRuntime.intent_enabled ? 'on' : 'off' }} ·
                            explanation
                            {{ aiRuntime.explanation_enabled ? 'on' : 'off' }}
                        </dd>
                    </div>
                    <div>
                        <dt>AI calls today</dt>
                        <dd>
                            {{ aiUsage.calls_today }} ·
                            {{
                                aiUsage.input_tokens_today +
                                aiUsage.output_tokens_today
                            }}
                            tokens ·
                            {{
                                (
                                    aiUsage.cost_today_micro_usd / 1_000_000
                                ).toFixed(4)
                            }}
                            USD
                        </dd>
                    </div>
                    <div>
                        <dt>AI circuit</dt>
                        <dd>
                            {{ aiRuntime.circuit_open_until ?? 'closed' }} ·
                            {{ aiUsage.failures_today }} failed
                        </dd>
                    </div>
                    <div><dt>Analysis failure rate</dt><dd>{{ analysisHealth.failure_rate_percent }}%</dd></div>
                    <div><dt>Unsupported mechanic rate</dt><dd>{{ analysisHealth.unsupported_rate_percent }}%</dd></div>
                    <div><dt>Queue failures</dt><dd>{{ analysisHealth.queue_failures }}</dd></div>
                </dl>
            </section>
        </div>
        <section v-if="actor.role === 'super_admin'" class="settings-section">
            <p class="kicker">AI / FAIL CLOSED</p>
            <h2>AI runtime controls</h2>
            <p>
                Bu kontroller environment anahtarlarını veya Policy Gate
                kararını aşamaz.
            </p>
            <form
                class="stack-form"
                @submit.prevent="aiForm.put('/admin/ai/settings')"
            >
                <label class="field"
                    ><span
                        ><input
                            v-model="aiForm.global_enabled"
                            type="checkbox"
                        />
                        Global AI</span
                    ></label
                >
                <label class="field"
                    ><span
                        ><input
                            v-model="aiForm.intent_enabled"
                            type="checkbox"
                        />
                        Intent AI</span
                    ></label
                >
                <label class="field"
                    ><span
                        ><input
                            v-model="aiForm.explanation_enabled"
                            type="checkbox"
                        />
                        Explanation AI</span
                    ></label
                >
                <label class="field"
                    ><span>Günlük global bütçe (micro USD)</span
                    ><input
                        v-model.number="aiForm.global_daily_budget_micro_usd"
                        type="number"
                        min="1"
                        :max="aiRuntime.global_daily_budget_ceiling_micro_usd"
                        required
                        aria-describedby="ai-daily-limit"
                /></label>
                <p id="ai-daily-limit" class="form-note">
                    Environment üst sınırı:
                    {{ aiRuntime.global_daily_budget_ceiling_micro_usd }} micro
                    USD.
                </p>
                <p
                    v-if="aiForm.errors.global_daily_budget_micro_usd"
                    class="form-error"
                    role="alert"
                >
                    {{ aiForm.errors.global_daily_budget_micro_usd }}
                </p>
                <label class="field"
                    ><span>Aylık global bütçe (micro USD)</span
                    ><input
                        v-model.number="aiForm.global_monthly_budget_micro_usd"
                        type="number"
                        min="1"
                        :max="aiRuntime.global_monthly_budget_ceiling_micro_usd"
                        required
                        aria-describedby="ai-monthly-limit"
                /></label>
                <p id="ai-monthly-limit" class="form-note">
                    Environment üst sınırı:
                    {{ aiRuntime.global_monthly_budget_ceiling_micro_usd }}
                    micro USD.
                </p>
                <p
                    v-if="aiForm.errors.global_monthly_budget_micro_usd"
                    class="form-error"
                    role="alert"
                >
                    {{ aiForm.errors.global_monthly_budget_micro_usd }}
                </p>
                <label class="field"
                    ><span>Değişiklik sebebi</span
                    ><textarea
                        v-model="aiForm.reason"
                        minlength="3"
                        maxlength="500"
                        required
                    />
                </label>
                <p v-if="aiForm.errors.reason" class="form-error" role="alert">
                    {{ aiForm.errors.reason }}
                </p>
                <button
                    class="button is-secondary"
                    :disabled="aiForm.processing"
                >
                    AI ayarlarını güncelle
                </button>
            </form>
        </section>
    </AppShell>
</template>

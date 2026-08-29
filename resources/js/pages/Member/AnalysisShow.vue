<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppShell from '@/components/app/AppShell.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import MemberNav from '@/components/member/MemberNav.vue';
const props = defineProps<{
    analysis: {
        id: string;
        state: string;
        game_edition: string;
        version: number;
        failure_code: string | null;
        created_at: string;
        updated_at: string;
        output: {
            build_summary?: {
                character_class_id?: string | null;
                ascendancy_id?: string | null;
                character_level?: number | null;
                skills?: Array<{ gems?: Array<{ id?: string }> }>;
                summary_values?: Record<string, string | number>;
                items?: Array<{ id?: string; slots?: string[] }>;
            };
            findings?: Array<{
                code: string;
                severity: string;
                title?: string;
                explanation?: string;
                observed_value?: unknown;
                expected_value?: unknown;
                evidence?: string[];
                rule_id?: string;
                ruleset_version?: string;
                confidence?: number;
            }>;
            recommendations?: Array<{
                code: string;
                priority?: string;
                decision_trace?: Record<string, unknown>;
                findings?: unknown[];
            }>;
            manual_trade_recipes?: Array<Record<string, any>>;
            analysis_result?: { unsupported_data?: string[] };
            upgrade_graph?: { ordering_reasons?: Record<string, string> };
            constraints?: { locked_items?: string[] };
            budget?: { amount?: string; currency?: string } | null;
            intent?: { goal?: { content_goal?: { value?: string }; description?: string } };
            latencies_ms?: Record<string, number>;
        } | null;
        ruleset: { id: string; version: string; checksum_sha256: string } | null;
    };
}>();
const ignored = ref<string[]>([]);
const ignoredFindings = ref<string[]>([]);
const expandedFindings = ref<string[]>([]);
const budget = ref('');
const budgetCurrency = ref('DIVINE');
const recalculating = ref(false);
const feedback = ref('');
const followUpQuestion = ref('');
const followUpAnswer = ref('');
const followUpBusy = ref(false);
const output = computed(() => props.analysis.output);
const build = computed(() => output.value?.build_summary ?? {});
const locked = ref<string[]>([]);
watch(output, (value) => {
    const persisted = value?.constraints?.locked_items;

    if (Array.isArray(persisted)) {
        locked.value = persisted.filter((item): item is string => typeof item === 'string');
    }

    if (value?.budget?.amount) {
        budget.value = value.budget.amount;
    }

    if (value?.budget?.currency) {
        budgetCurrency.value = value.budget.currency;
    }
}, { immediate: true });
const mainSkill = computed(() => {
    const first = build.value.skills?.[0] as { name?: string; gems?: Array<{ name?: string; id?: string }> } | undefined;

    return first?.name ?? first?.gems?.[0]?.name ?? first?.gems?.[0]?.id ?? 'Unknown';
});
const findings = computed(() => (output.value?.findings ?? []).filter((item) => !ignoredFindings.value.includes(item.code)));
const recommendations = computed(() => (output.value?.recommendations ?? []).filter((item) => !ignored.value.includes(item.code)));
const dependencyWarnings = computed(() => Object.entries(output.value?.upgrade_graph?.ordering_reasons ?? {}));
const value = (key: string): string | number => build.value.summary_values?.[key] ?? '—';
const label = (id?: string | null): string => id?.split('.').at(-1)?.replaceAll('_', ' ') ?? 'Unknown';
const itemKey = (item: { id?: string; slots?: string[] }): string => item.id ?? item.slots?.join('/') ?? 'unknown-item';
const recipeText = (recipe: Record<string, unknown>): string => {
    const direct = recipe.strict_recipe ?? recipe.broad_recipe;

    if (typeof direct === 'string') {
return direct;
}

    const variant = recipe.strict ?? recipe.broad_fallback;

    if (variant && typeof variant === 'object') {
        const constraints = (variant as { constraints?: unknown }).constraints;

        if (Array.isArray(constraints)) {
return constraints.map((entry) => String(entry)).join('\n');
}
    }

    return 'No printable recipe available.';
};
async function copyTradeMode(recipe: Record<string, unknown>, mode: string): Promise<void> {
    const text = `${mode}\n\n${recipeText(recipe)}\n\nLootwright validates filters locally; no Trade URL or listing request was generated.`;

    try {
        await navigator.clipboard.writeText(text);
        feedback.value = `${mode} copied.`;
    } catch {
        feedback.value = 'Clipboard access was denied.';
    }
}
function toggleLock(item: { id?: string; slots?: string[] }): void {
    const id = itemKey(item);
    locked.value = locked.value.includes(id) ? locked.value.filter((entry) => entry !== id) : [...locked.value, id];
}
function ignore(code: string): void {
    ignored.value = ignored.value.includes(code)
        ? ignored.value.filter((entry) => entry !== code)
        : [...ignored.value, code];
}
function ignoreFinding(code: string): void {
    ignoredFindings.value = ignoredFindings.value.includes(code)
        ? ignoredFindings.value.filter((entry) => entry !== code)
        : [...ignoredFindings.value, code];
}
function explain(code: string): void {
    expandedFindings.value = expandedFindings.value.includes(code)
        ? expandedFindings.value.filter((entry) => entry !== code)
        : [...expandedFindings.value, code];
}
async function recalculate(): Promise<void> {
    if (!budget.value || recalculating.value) {
return;
}

/* assistant follow-up is available through the API endpoint */
async function askAssistant(): Promise<void> {
    if (!followUpQuestion.value.trim() || followUpBusy.value) return;
    followUpBusy.value = true;
    followUpAnswer.value = '';
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    try {
        const response = await fetch(`/api/analyses/${props.analysis.id}/ai-follow-up`, {
            method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ question: followUpQuestion.value, ai_opt_in: true, cache_permitted: true }),
        });
        const data = await response.json() as { message?: string; analysis_id?: string };
        followUpAnswer.value = data.message ?? 'The deterministic result remains available.';
        if (data.analysis_id) followUpAnswer.value += ` Queued analysis: ${data.analysis_id}`;
    } catch {
        followUpAnswer.value = 'The deterministic result remains available; assistant follow-up is unavailable.';
    } finally {
        followUpBusy.value = false;
    }
}

    recalculating.value = true;
    feedback.value = '';
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const response = await fetch(`/api/analyses/${props.analysis.id}/reanalyze`, {
        method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
        body: JSON.stringify({ goals: [], budget_amount: budget.value, budget_currency: budgetCurrency.value, locked_items: locked.value }),
    });
    recalculating.value = false;
    feedback.value = response.ok ? 'Yeni bütçe ile analiz kuyruğa alındı.' : 'Bütçe güncellenemedi; mevcut sonuç korunuyor.';

    if (response.ok) {
window.setTimeout(() => window.location.reload(), 700);
}
}

function deleteAnalysis(id: string): void {
    if (
        window.confirm(
            'Bu analizi kalıcı olarak silmek istediğinden emin misin?',
        )
    ) {
        router.delete(`/analyses/${id}`);
    }
}
</script>
<template>
    <Head title="Analiz" /><AppShell current="analyses"
        ><MemberNav current="analyses" />
        <header class="page-heading">
            <p class="kicker">
                {{ analysis.game_edition }} · Sürüm {{ analysis.version }}
            </p>
            <h1>Analiz {{ analysis.id }}</h1>
            <p>Durum: <span class="status-chip">{{ analysis.state }}</span></p>
        </header>
        <template v-if="analysis.output">
            <section class="overview-strip" aria-label="Build summary">
                <div><span>Edition</span><strong>{{ analysis.game_edition === 'poe1' ? 'Path of Exile 1' : analysis.game_edition }}</strong></div>
                <div><span>Class</span><strong>{{ label(build.character_class_id) }}</strong></div>
                <div><span>Ascendancy</span><strong>{{ label(build.ascendancy_id) }}</strong></div>
                <div><span>Level</span><strong>{{ build.character_level ?? '—' }}</strong></div>
                <div><span>Main skill</span><strong>{{ mainSkill }}</strong></div>
                <div><span>Life / ES</span><strong>{{ value('life') }} / {{ value('energy_shield') }}</strong></div>
                <div><span>Armour / Evasion</span><strong>{{ value('armour') }} / {{ value('evasion') }}</strong></div>
                <div><span>Block / Suppression</span><strong>{{ value('block') }} / {{ value('spell_suppression') }}</strong></div>
                <div><span>Goal</span><strong>{{ output?.intent?.goal?.content_goal?.value ?? 'progression' }}</strong></div>
            </section>
            <section class="results-intro" aria-labelledby="findings-title">
                <div><p class="kicker">DETERMINISTIC RESULTS</p><h2 id="findings-title">What is wrong with this build?</h2><p>{{ output?.intent?.goal?.description ?? 'Findings are calculated from your normalized build and the active PoE1 ruleset.' }}</p></div>
                <div><span class="status-chip">{{ findings.length }} findings</span><span class="status-chip">{{ recommendations.length }} fixes</span></div>
            </section>
            <section class="overview-strip" aria-label="Defensive and offensive profile">
                <div><span>Defensive profile</span><strong>Life {{ value('life') }} · ES {{ value('energy_shield') }} · Armour {{ value('armour') }} · Evasion {{ value('evasion') }}</strong></div>
                <div><span>Offensive profile</span><strong>{{ value('damage') }} damage · {{ value('crit_chance') }}% crit</strong></div>
                <div><span>Resource profile</span><strong>Mana {{ value('mana') }} · Reservation {{ value('reservation') }}</strong></div>
            </section>
            <section v-if="findings.length" class="finding-group" aria-label="Findings">
                <article v-for="finding in findings" :key="finding.code" class="finding-row" :class="`is-${finding.severity}`">
                    <header><div><span class="severity-label">{{ finding.severity }}</span><code>{{ finding.rule_id ?? finding.code }}</code></div><button type="button" class="button is-secondary" :aria-expanded="expandedFindings.includes(finding.code)" @click="explain(finding.code)">Why?</button><button type="button" class="button is-secondary" @click="ignoreFinding(finding.code)">Ignore</button></header>
                    <h3>{{ finding.title ?? finding.code }}</h3><p>{{ finding.explanation ?? 'Deterministic finding from the active ruleset.' }}</p>
                    <details :open="expandedFindings.includes(finding.code)"><summary>Show evidence</summary><dl><div><dt>Observed</dt><dd>{{ finding.observed_value ?? '—' }}</dd></div><div><dt>Expected</dt><dd>{{ finding.expected_value ?? '—' }}</dd></div><div><dt>Evidence</dt><dd><code>{{ (finding.evidence ?? []).join(', ') || 'ruleset evidence' }}</code></dd></div><div><dt>Confidence</dt><dd>{{ finding.confidence ?? 10000 }} bp · ruleset {{ finding.ruleset_version ?? analysis.ruleset?.version }}</dd></div></dl></details>
                </article>
            </section>
            <section v-else class="empty-state" aria-label="No findings"><h2>No critical findings</h2><p>The deterministic ruleset found no actionable issue for this build and goal.</p></section>
            <section class="upgrade-list" aria-label="Recommended fixes">
                <article v-for="(recommendation, index) in recommendations" :key="recommendation.code" class="upgrade-row"><div class="upgrade-rank"><span>0{{ index + 1 }}</span><small>Priority</small></div><div class="upgrade-main"><header><code>{{ recommendation.code }}</code><span>{{ recommendation.priority ?? 'medium' }}</span></header><h3>{{ String(recommendation.code).replaceAll('.', ' ') }}</h3><p>Recommended from deterministic findings and current constraints.</p><button type="button" class="button is-secondary" @click="ignore(recommendation.code)">Ignore this recommendation</button></div></article>
            </section>
            <section v-if="dependencyWarnings.length" class="finding-group" aria-labelledby="dependencies-title">
                <div class="section-title-row"><div><p class="kicker">DEPENDENCIES</p><h2 id="dependencies-title">Dependency warnings</h2></div></div>
                <ul><li v-for="([candidate, reason]) in dependencyWarnings" :key="candidate"><strong>{{ candidate }}</strong> — {{ reason }}</li></ul>
            </section>
            <section v-if="output?.manual_trade_recipes?.length" class="recipe-sheet" aria-labelledby="trade-options-title">
                <div class="section-title-row"><div><p class="kicker">TRADE OPTIONS</p><h2 id="trade-options-title">Manual Trade recipes</h2></div><span>{{ output?.manual_trade_recipes?.length }} options</span></div>
                <article v-for="(recipe, index) in output?.manual_trade_recipes ?? []" :key="`${String(recipe.slot ?? 'slot')}-${index}`" class="recipe-card">
                    <header><strong>{{ label(String(recipe.slot ?? 'item')) }}</strong><span>{{ recipe.league ?? 'Standard' }}</span></header>
                    <p>{{ recipe.explanation ?? recipe.broad_recipe ?? 'Use the deterministic filter recipe below.' }}</p>
                    <nav class="segmented-control" aria-label="Trade search modes">
                        <button v-for="mode in ['Broad Search', 'Strict Search', 'Budget Search', 'Alternative Search']" :key="mode" type="button" @click="copyTradeMode(recipe, mode)">{{ mode }}</button>
                    </nav>
                    <details><summary>Show recipe</summary><pre>{{ recipeText(recipe) }}</pre></details>
                </article>
            </section>
            <section v-if="output?.analysis_result?.unsupported_data?.length" class="finding-group" aria-labelledby="unsupported-title">
                <div class="section-title-row"><div><p class="kicker">DATA BOUNDARY</p><h2 id="unsupported-title">Unsupported mechanics</h2></div></div>
                <ul><li v-for="entry in output?.analysis_result?.unsupported_data ?? []" :key="entry"><code>{{ entry }}</code></li></ul>
            </section>
            <section class="equipment-section" aria-labelledby="locked-title"><div class="section-title-row"><div><p class="kicker">CONSTRAINTS</p><h2 id="locked-title">Lock equipment</h2></div><span>{{ locked.length }} locked</span></div><ul class="equipment-grid"><li v-for="item in (build.items ?? [])" :key="itemKey(item)" :class="{ 'is-selected': locked.includes(itemKey(item)) }"><span>{{ (item.slots ?? []).join(' · ') || 'Item' }}</span><strong>{{ label(item.id) }}</strong><button type="button" class="button is-secondary" @click="toggleLock(item)">{{ locked.includes(itemKey(item)) ? 'Unlock' : 'Lock this item' }}</button></li></ul></section>
            <section class="recipe-sheet" aria-labelledby="budget-title"><div class="recipe-header"><div><p class="kicker">RECALCULATE</p><h2 id="budget-title">Change budget</h2><p>Simple budget changes reuse the deterministic planner; no AI request is made.</p></div></div><div class="form-grid two-columns"><label class="field"><span>Budget</span><input v-model="budget" inputmode="decimal" pattern="[0-9]+([.][0-9]{1,4})?" /></label><label class="field"><span>Currency</span><select v-model="budgetCurrency"><option>DIVINE</option><option>CHAOS</option></select></label></div><button type="button" class="button is-primary" :disabled="recalculating || !budget" @click="recalculate">{{ recalculating ? 'Recalculating…' : 'Recalculate' }}</button><p v-if="feedback" role="status">{{ feedback }}</p></section>
            <section class="recipe-sheet" aria-labelledby="assistant-title"><div class="recipe-header"><div><p class="kicker">OPTIONAL AI ASSISTANT</p><h2 id="assistant-title">Ask about this result</h2><p>Questions are classified against this deterministic snapshot. Unsupported mechanics are not guessed.</p></div></div><label class="field"><span>Question</span><textarea v-model="followUpQuestion" maxlength="500" rows="3" placeholder="What if I have 20 more div?" /></label><button type="button" class="button is-secondary" :disabled="followUpBusy || !followUpQuestion.trim()" @click="askAssistant">{{ followUpBusy ? 'Checking…' : 'Ask assistant' }}</button><p v-if="followUpAnswer" role="status">{{ followUpAnswer }}</p></section>
            <section class="provenance-ledger"><div><dt>Ruleset</dt><dd><code>{{ analysis.ruleset?.version ?? '—' }}</code></dd></div><div><dt>Checksum</dt><dd><code>{{ analysis.ruleset?.checksum_sha256 ?? '—' }}</code></dd></div><div><dt>Data freshness</dt><dd>Immutable snapshot at analysis time</dd></div><div v-if="output?.latencies_ms"><dt>Deterministic latency</dt><dd>{{ output.latencies_ms.planner ?? '—' }} ms planner · {{ output.latencies_ms.trade_recipe ?? '—' }} ms recipes</dd></div></section>
        </template>
        <StatusBanner v-else tone="neutral" title="Analysis is processing" body="The deterministic worker has not published a result yet. Refresh this page shortly." />
        <dl class="detail-ledger">
            <div>
                <dt>Oluşturuldu</dt>
                <dd>
                    {{ new Date(analysis.created_at).toLocaleString('tr-TR') }}
                </dd>
            </div>
            <div>
                <dt>Güncellendi</dt>
                <dd>
                    {{ new Date(analysis.updated_at).toLocaleString('tr-TR') }}
                </dd>
            </div>
            <div>
                <dt>Hata kodu</dt>
                <dd>{{ analysis.failure_code ?? 'Yok' }}</dd>
            </div>
        </dl>
        <div class="action-row">
            <a
                :href="`/api/analyses/${analysis.id}/export`"
                class="button is-secondary"
                >Veriyi dışa aktar</a
            >
            <button
                type="button"
                class="button is-danger"
                @click="deleteAnalysis(analysis.id)"
            >
                Analizi sil
            </button>
        </div></AppShell
    >
</template>

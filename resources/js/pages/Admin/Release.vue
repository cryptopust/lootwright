<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';

type ReleaseStatus = 'PASS' | 'PASS_WITH_LIMITATIONS' | 'FAIL';
type GateStatus = 'PASS' | 'FAIL' | 'BLOCKED';
type CoverageEntry = {
    category: string;
    observed_records: number;
    expected_records: number | null;
    coverage_percent: number | null;
    status: string;
};
type EditionGate = {
    edition: 'poe1' | 'poe2';
    public: boolean;
    status: ReleaseStatus;
    ruleset: { id: string; version: string } | null;
    coverage: CoverageEntry[];
    parser: {
        adapter: string | null;
        observed_completed_analysis: boolean;
        coverage_status: string;
    };
    analysis_rules: {
        available: number;
        expected: number | null;
        coverage_percent: number | null;
        status: string;
    };
    recommendation_trace: {
        recommendations: number;
        complete_traces: number;
    };
    unsupported_mechanics: {
        sample_size: number;
        analyses_with_unsupported: number;
        rate_percent: number | null;
    };
    latencies_ms: Record<string, number | string | null>;
    gates: Array<{
        key: string;
        label: string;
        status: GateStatus;
        evidence: string;
        critical: boolean;
    }>;
    blockers: string[];
    limitations: string[];
};

const props = defineProps<{
    releaseGate: {
        generated_at: string;
        overall_status: ReleaseStatus;
        active_release_edition: 'poe1';
        scope_notice: string;
        editions: Record<'poe1' | 'poe2', EditionGate>;
        market_provider: {
            source: string;
            enabled: boolean;
            status: string;
            last_completed_at: string | null;
            notice: string;
        };
        regressions: {
            status: string;
            failures: number | null;
            generated_at: string | null;
            scope?: string;
        };
        ai: {
            calls_today: number;
            failures_today: number;
            average_latency_ms: number | null;
        };
    };
}>();

const editions = [
    props.releaseGate.editions.poe1,
    props.releaseGate.editions.poe2,
];

const statusClass = (status: GateStatus | ReleaseStatus) =>
    status === 'PASS'
        ? 'is-pass'
        : status === 'PASS_WITH_LIMITATIONS'
          ? 'is-blocked'
          : status === 'BLOCKED'
            ? 'is-blocked'
            : 'is-fail';

const value = (input: number | string | null) =>
    input === null ? 'bilinmiyor' : String(input);
</script>

<template>
    <Head title="MVP release gate" />
    <AppShell :contained="false">
        <AdminNav current="release" />

        <header class="page-heading is-split">
            <div>
                <p class="kicker">RELEASE / PLAYER ACCEPTANCE</p>
                <h1>Oyuncu yolculuğu gate’i</h1>
                <p>
                    Mimari varlığı değil; production binding, gözlenen oyuncu
                    analizi ve persisted ürün zincirini ölçer.
                </p>
            </div>
            <span
                class="release-verdict"
                :class="statusClass(releaseGate.overall_status)"
                >{{ releaseGate.overall_status }}</span
            >
        </header>

        <p class="release-scope-note">
            {{ releaseGate.scope_notice }}
            <code>{{ releaseGate.generated_at }}</code>
        </p>

        <section
            v-for="edition in editions"
            :key="edition.edition"
            class="release-edition"
        >
            <header>
                <div>
                    <p class="kicker">
                        {{ edition.edition.toUpperCase() }} / INDEPENDENT GATE
                    </p>
                    <h2>
                        {{
                            edition.edition === 'poe1'
                                ? 'Path of Exile 1'
                                : 'Path of Exile 2'
                        }}
                    </h2>
                </div>
                <span
                    class="release-verdict"
                    :class="statusClass(edition.status)"
                    >{{ edition.status }}</span
                >
            </header>

            <dl class="release-metrics">
                <div>
                    <dt>Public</dt>
                    <dd>{{ edition.public ? 'evet' : 'hayır' }}</dd>
                </div>
                <div>
                    <dt>Ruleset</dt>
                    <dd>{{ edition.ruleset?.version ?? 'bilinmiyor' }}</dd>
                </div>
                <div>
                    <dt>Parser</dt>
                    <dd>
                        {{ edition.parser.adapter ?? 'bilinmiyor' }}
                        <small>{{ edition.parser.coverage_status }}</small>
                    </dd>
                </div>
                <div>
                    <dt>Analysis rules</dt>
                    <dd>{{ edition.analysis_rules.available }}</dd>
                </div>
                <div>
                    <dt>Unsupported rate</dt>
                    <dd>
                        {{
                            edition.unsupported_mechanics.rate_percent ??
                            'bilinmiyor'
                        }}<span
                            v-if="
                                edition.unsupported_mechanics.rate_percent !==
                                null
                            "
                            >%</span
                        >
                    </dd>
                </div>
            </dl>

            <div class="release-columns">
                <section>
                    <h3>Kritik yol</h3>
                    <ol class="release-gate-list">
                        <li v-for="gate in edition.gates" :key="gate.key">
                            <span
                                class="status-chip"
                                :class="statusClass(gate.status)"
                                >{{ gate.status }}</span
                            >
                            <div>
                                <strong>{{ gate.label }}</strong>
                                <p>{{ gate.evidence }}</p>
                            </div>
                        </li>
                    </ol>
                </section>

                <section>
                    <h3>Dataset coverage</h3>
                    <dl class="coverage-ledger">
                        <div
                            v-for="entry in edition.coverage"
                            :key="entry.category"
                        >
                            <dt>{{ entry.category }}</dt>
                            <dd>
                                <span>{{ entry.observed_records }}</span>
                                <span v-if="entry.expected_records !== null">
                                    / {{ entry.expected_records }} ·
                                    {{ entry.coverage_percent }}%
                                </span>
                                <em v-else>tamlık bilinmiyor</em>
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            <section class="release-latency">
                <h3>Gözlenen latency</h3>
                <dl>
                    <div
                        v-for="(metric, stage) in edition.latencies_ms"
                        :key="stage"
                    >
                        <dt>{{ stage }}</dt>
                        <dd>
                            {{ value(metric)
                            }}<span v-if="typeof metric === 'number'"> ms</span>
                        </dd>
                    </div>
                </dl>
                <p>
                    End-to-end süre queue beklemesini içerebilir. Ayrı stage
                    instrumentation yoksa değer açıkça bilinmiyor gösterilir.
                </p>
            </section>

            <section
                v-if="edition.limitations.length"
                class="release-limitations"
            >
                <h3>Sınırlamalar</h3>
                <ul>
                    <li
                        v-for="limitation in edition.limitations"
                        :key="limitation"
                    >
                        {{ limitation }}
                    </li>
                </ul>
            </section>
        </section>

        <section class="release-operations">
            <div>
                <p class="kicker">MARKET / EVIDENCE</p>
                <h2>Market provider</h2>
                <p>
                    <code>{{ releaseGate.market_provider.source }}</code> ·
                    {{
                        releaseGate.market_provider.enabled
                            ? 'enabled'
                            : 'disabled'
                    }}
                    ·
                    {{ releaseGate.market_provider.status }}
                </p>
                <small>{{ releaseGate.market_provider.notice }}</small>
            </div>
            <div>
                <p class="kicker">REGRESSION / STRUCTURAL</p>
                <h2>Evaluation</h2>
                <p>
                    {{ releaseGate.regressions.status }} ·
                    {{ releaseGate.regressions.failures ?? 'bilinmiyor' }}
                    failure
                </p>
                <small>{{ releaseGate.regressions.scope }}</small>
            </div>
            <div>
                <p class="kicker">AI / OPTIONAL</p>
                <h2>Provider health</h2>
                <p>
                    {{ releaseGate.ai.calls_today }} call ·
                    {{ releaseGate.ai.failures_today }} failure ·
                    {{ releaseGate.ai.average_latency_ms ?? 'bilinmiyor' }} ms
                </p>
                <small
                    >AI sonucu release finding’i veya recommendation
                    üretmez.</small
                >
            </div>
        </section>
    </AppShell>
</template>

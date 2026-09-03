<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';

type AdapterStatus = {
    operational: boolean;
    disabled_reason: string | null;
};

type SourceRecord = {
    code: string;
    name: string;
    source_type: string;
    editions: string[];
    enabled: boolean;
    emergency_kill_switch: boolean;
    governance_status: string;
    policy_status: string;
    disabled_reason: string;
    adapter: AdapterStatus | null;
    last_attempt_at: string | null;
    last_success_at: string | null;
    last_error: string | null;
    dataset_edition: string | null;
    ruleset_target: string | null;
    checksum: string | null;
    records_imported: number;
    records_rejected: number;
    import_status: string | null;
    update_status: string | null;
    update_checked_at: string | null;
};

const props = defineProps<{
    failedJobs: number;
    killSwitches: Array<Record<string, string | null>>;
    sourceRuns: Array<Record<string, string | null>>;
    sources: SourceRecord[];
    canTriggerImports: boolean;
    release: string | null;
}>();

const reasons = reactive<Record<string, string>>({});

function requestImport(source: SourceRecord): void {
    router.post(
        '/admin/sources/import',
        { source_code: source.code, reason: reasons[source.code] ?? '' },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Sistem" />
    <AppShell :contained="false">
        <AdminNav current="system" />
        <header class="page-heading">
            <p class="kicker">Salt okunur operasyon özeti</p>
            <h1>Sistem sağlığı</h1>
            <p>
                Environment, secret, SQL veya anahtar düzenleme özelliği yoktur.
            </p>
        </header>

        <dl class="metric-strip">
            <div>
                <dt>Başarısız job</dt>
                <dd>{{ failedJobs }}</dd>
            </div>
            <div>
                <dt>Kill switch</dt>
                <dd>{{ killSwitches.length }}</dd>
            </div>
            <div>
                <dt>Uygulama sürümü</dt>
                <dd class="small-value">{{ release ?? 'yerel' }}</dd>
            </div>
        </dl>

        <section class="data-section" aria-labelledby="source-registry-title">
            <p class="kicker">PolicyAndProvenanceGate</p>
            <h2 id="source-registry-title">Kaynak registry</h2>
            <p>
                “Operational” yalnız adapter, configuration ve policy birlikte
                izin verdiğinde görünür. Teknik erişim tek başına kullanım izni
                değildir.
            </p>
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kaynak</th>
                            <th>Edition</th>
                            <th>Policy</th>
                            <th>Adapter</th>
                            <th>Son başarı</th>
                            <th>Import</th>
                            <th>Kayıtlar</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="source in props.sources" :key="source.code">
                            <td>
                                <strong>{{ source.name }}</strong
                                ><br /><code>{{ source.code }}</code>
                            </td>
                            <td>
                                <code>{{
                                    source.editions.join(', ') || 'n/a'
                                }}</code>
                            </td>
                            <td>
                                {{ source.policy_status }}<br /><small
                                    v-if="source.emergency_kill_switch"
                                    >kill switch active</small
                                >
                            </td>
                            <td>
                                {{
                                    source.adapter?.operational
                                        ? 'operational'
                                        : 'disabled'
                                }}
                                <br /><code
                                    v-if="source.adapter?.disabled_reason"
                                    >{{ source.adapter.disabled_reason }}</code
                                >
                            </td>
                            <td>
                                <code>{{
                                    source.last_success_at ?? 'bilinmiyor'
                                }}</code>
                            </td>
                            <td>
                                {{ source.import_status ?? 'yok' }}
                                <br /><code>{{
                                    source.update_status ??
                                    'update kontrolü yok'
                                }}</code>
                                <br /><code>{{
                                    source.update_checked_at ??
                                    'kontrol zamanı yok'
                                }}</code>
                                <br /><code>{{
                                    source.ruleset_target ?? 'ruleset yok'
                                }}</code>
                                <br /><code v-if="source.checksum"
                                    >{{ source.checksum.slice(0, 12) }}…</code
                                >
                            </td>
                            <td>
                                <code
                                    >{{ source.records_imported }} /
                                    {{ source.records_rejected }}</code
                                >
                            </td>
                            <td>
                                <form
                                    v-if="
                                        canTriggerImports &&
                                        source.adapter?.operational
                                    "
                                    @submit.prevent="requestImport(source)"
                                >
                                    <label :for="`reason-${source.code}`"
                                        >İşlem sebebi</label
                                    >
                                    <input
                                        :id="`reason-${source.code}`"
                                        v-model="reasons[source.code]"
                                        required
                                        minlength="10"
                                        maxlength="500"
                                    />
                                    <button type="submit">
                                        Import kuyruğuna ekle
                                    </button>
                                </form>
                                <em v-else>{{
                                    source.adapter?.operational
                                        ? 'yalnız super-admin'
                                        : 'kullanılamaz'
                                }}</em>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-section">
            <h2>Son kaynak denemeleri</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kaynak</th>
                        <th>Durum</th>
                        <th>League</th>
                        <th>Kategori</th>
                        <th>Hata</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(run, index) in sourceRuns" :key="index">
                        <td>{{ run.source_key }}</td>
                        <td>{{ run.status }}</td>
                        <td>{{ run.league }}</td>
                        <td>{{ run.category }}</td>
                        <td>{{ run.failure_code ?? 'Yok' }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </AppShell>
</template>

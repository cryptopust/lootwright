<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
defineProps<{
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
}>();
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
                </dl>
            </section>
        </div></AppShell
    >
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
defineProps<{
    failedJobs: number;
    killSwitches: Array<Record<string, string | null>>;
    sourceRuns: Array<Record<string, string | null>>;
    release: string | null;
}>();
</script>
<template>
    <Head title="Sistem" /><AppShell :contained="false"
        ><AdminNav current="system" />
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
        </section></AppShell
    >
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
defineProps<{
    counts: {
        total: number;
        pending: number;
        completed: number;
        failed: number;
    };
    recent: Array<{
        id: string;
        state: string;
        game_edition: string;
        created_at: string;
    }>;
    source: null | {
        source_key: string;
        league: string | null;
        fetched_at: string | null;
        expires_at: string | null;
    };
    aiUsageMicroUsd: number;
}>();
</script>
<template>
    <Head title="Panel" /><AppShell current="dashboard" :contained="false"
        ><MemberNav current="dashboard" />
        <header class="page-heading is-split">
            <div>
                <p class="kicker">Üye paneli</p>
                <h1>Analiz çalışma alanın</h1>
                <p>Yalnız sana ait kayıtlar ve güncel işleme durumu.</p>
            </div>
            <a class="button is-primary" href="/analyses/new"
                >Yeni analiz oluştur</a
            >
        </header>
        <dl class="metric-strip">
            <div>
                <dt>Toplam</dt>
                <dd>{{ counts.total }}</dd>
            </div>
            <div>
                <dt>Bekleyen</dt>
                <dd>{{ counts.pending }}</dd>
            </div>
            <div>
                <dt>Tamamlanan</dt>
                <dd>{{ counts.completed }}</dd>
            </div>
            <div>
                <dt>Başarısız</dt>
                <dd>{{ counts.failed }}</dd>
            </div>
        </dl>
        <div class="admin-columns">
            <section class="data-section">
                <h2>Kaynak güncelliği</h2>
                <p v-if="source">
                    {{ source.source_key }} ·
                    {{ source.league ?? 'League yok' }} ·
                    <span class="status-chip">
                        {{
                            source.expires_at &&
                            new Date(source.expires_at) > new Date()
                                ? 'fresh'
                                : 'stale'
                        }}
                    </span>
                </p>
                <p v-else>Henüz geçerli bir ekonomi snapshotı yok.</p>
            </section>
            <section class="data-section">
                <h2>AI kullanım özeti</h2>
                <p>
                    Bugünkü kayıtlı kullanım:
                    {{ (aiUsageMicroUsd / 1_000_000).toFixed(4) }} USD. AI
                    deterministik sonuçları değiştiremez.
                </p>
            </section>
        </div>
        <section class="data-section">
            <header>
                <h2>Son analizler</h2>
                <a href="/analyses">Tümünü gör</a>
            </header>
            <div v-if="!recent.length" class="empty-state">
                <h3>Henüz analiz yok</h3>
                <p>Sihirbazla ilk PoE1 planını oluştur.</p>
            </div>
            <table v-else class="data-table">
                <thead>
                    <tr>
                        <th>Kimlik</th>
                        <th>Oyun</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in recent" :key="item.id">
                        <td>
                            <a :href="`/analyses/${item.id}`">{{ item.id }}</a>
                        </td>
                        <td>{{ item.game_edition }}</td>
                        <td>
                            <span class="status-chip">{{ item.state }}</span>
                        </td>
                        <td>
                            {{
                                new Date(item.created_at).toLocaleDateString(
                                    'tr-TR',
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section></AppShell
    >
</template>

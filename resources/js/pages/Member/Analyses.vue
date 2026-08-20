<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
const props = defineProps<{
    analyses: {
        data: Array<{
            id: string;
            state: string;
            game_edition: string;
            created_at: string;
        }>;
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: Record<string, string>;
}>();
const filters = reactive({
    status: props.filters.status ?? '',
    search: props.filters.search ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});
function apply() {
    router.get('/analyses', filters, { preserveState: true });
}
</script>
<template>
    <Head title="Analizler" /><AppShell current="analyses" :contained="false"
        ><MemberNav current="analyses" />
        <header class="page-heading is-split">
            <div>
                <p class="kicker">Sahiplik korumalı</p>
                <h1>Analizler</h1>
            </div>
            <a class="button is-primary" href="/analyses/new">Yeni analiz</a>
        </header>
        <form class="filter-bar" @submit.prevent="apply">
            <label
                >Durum<select v-model="filters.status">
                    <option value="">Tümü</option>
                    <option
                        v-for="value in [
                            'queued',
                            'processing',
                            'completed',
                            'failed',
                            'clarification_required',
                        ]"
                        :key="value"
                    >
                        {{ value }}
                    </option>
                </select></label
            ><label>Kimlik ara<input v-model="filters.search" /></label
            ><label>Başlangıç<input v-model="filters.from" type="date" /></label
            ><label>Bitiş<input v-model="filters.to" type="date" /></label
            ><button class="button is-secondary">Filtrele</button>
        </form>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kimlik</th>
                    <th>Oyun</th>
                    <th>Durum</th>
                    <th>Oluşturma</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in analyses.data" :key="item.id">
                    <td>
                        <a :href="`/analyses/${item.id}`">{{ item.id }}</a>
                    </td>
                    <td>{{ item.game_edition }}</td>
                    <td>{{ item.state }}</td>
                    <td>
                        {{ new Date(item.created_at).toLocaleString('tr-TR') }}
                    </td>
                </tr>
            </tbody>
        </table>
        <nav class="pagination" aria-label="Sayfalama">
            <a
                v-for="link in analyses.links"
                :key="link.label"
                :href="link.url ?? undefined"
                :aria-current="link.active ? 'page' : undefined"
                v-html="link.label"
            ></a></nav
    ></AppShell>
</template>

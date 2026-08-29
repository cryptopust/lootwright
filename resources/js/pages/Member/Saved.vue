<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
defineProps<{
    builds: Array<{
        id: string;
        label: string | null;
        created_at: string;
        game_edition: string;
        league: string | null;
    }>;
    analyses: Array<{
        id: string;
        created_at: string;
        state: string;
        game_edition: string;
        version: number;
    }>;
}>();
</script>
<template>
    <Head title="Saved builds and analyses" />
    <AppShell current="saved" :contained="false">
        <MemberNav current="saved" />
        <header class="page-heading">
            <p class="kicker">Account library</p>
            <h1>Saved builds &amp; analyses</h1>
            <p>Your saved records remain private to this account.</p>
        </header>
        <div class="admin-columns">
            <section class="data-section">
                <h2>Saved builds</h2>
                <p v-if="!builds.length" class="empty-state">
                    No saved builds yet.
                </p>
                <div v-else class="table-scroll" tabindex="0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Game</th>
                                <th>League</th>
                                <th>Saved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="build in builds" :key="build.id">
                                <td>
                                    <strong>{{
                                        build.label || 'Untitled build'
                                    }}</strong
                                    ><br /><code>{{ build.id }}</code>
                                </td>
                                <td>{{ build.game_edition }}</td>
                                <td>{{ build.league || '—' }}</td>
                                <td>
                                    {{
                                        new Date(
                                            build.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="data-section">
                <h2>Saved analyses</h2>
                <p v-if="!analyses.length" class="empty-state">
                    No saved analyses yet.
                </p>
                <div v-else class="table-scroll" tabindex="0">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Analysis</th>
                                <th>Game</th>
                                <th>Status</th>
                                <th>Saved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="analysis in analyses" :key="analysis.id">
                                <td>
                                    <a :href="`/analyses/${analysis.id}`"
                                        ><code>{{ analysis.id }}</code></a
                                    >
                                </td>
                                <td>{{ analysis.game_edition }}</td>
                                <td>
                                    <span class="status-chip">{{
                                        analysis.state
                                    }}</span>
                                </td>
                                <td>
                                    {{
                                        new Date(
                                            analysis.created_at,
                                        ).toLocaleDateString()
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppShell>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
defineProps<{
    entries: {
        data: Array<{
            id: string;
            action: string;
            reason: string;
            metadata: string | Record<string, unknown>;
            created_at: string;
            actor_email: string;
        }>;
    };
}>();
</script>
<template>
    <Head title="Audit log" /><AppShell :contained="false"
        ><AdminNav current="audit" />
        <header class="page-heading">
            <p class="kicker">Append-only</p>
            <h1>Admin audit log</h1>
            <p>
                Şifre, token, raw PoB, item metni, prompt, cookie ve IP içermez.
            </p>
        </header>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Aktör</th>
                    <th>İşlem</th>
                    <th>Sebep</th>
                    <th>Metadata</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="entry in entries.data" :key="entry.id">
                    <td>
                        {{ new Date(entry.created_at).toLocaleString('tr-TR') }}
                    </td>
                    <td>{{ entry.actor_email }}</td>
                    <td>
                        <code>{{ entry.action }}</code>
                    </td>
                    <td>{{ entry.reason }}</td>
                    <td>
                        <code>{{
                            typeof entry.metadata === 'string'
                                ? entry.metadata
                                : JSON.stringify(entry.metadata)
                        }}</code>
                    </td>
                </tr>
            </tbody>
        </table></AppShell
    >
</template>

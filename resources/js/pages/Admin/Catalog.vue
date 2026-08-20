<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
defineProps<{
    catalog: {
        patch: string;
        data_version: string;
        verified_at: string;
        source: string;
        classes: Array<{
            id: string;
            name: string;
            ascendancies: Array<{ id: string; name: string; kind: string }>;
        }>;
    };
}>();
</script>
<template>
    <Head title="PoE 1 katalog" /><AppShell :contained="false"
        ><AdminNav current="catalog" />
        <header class="page-heading">
            <p class="kicker">PoE 1 · {{ catalog.patch }}</p>
            <h1>Sürüm kontrollü karakter kataloğu</h1>
            <p>{{ catalog.data_version }} · {{ catalog.verified_at }}</p>
            <a
                class="text-link"
                :href="catalog.source"
                rel="noopener noreferrer"
                >Kaynak metadata</a
            >
        </header>
        <div class="catalog-ledger">
            <section v-for="character in catalog.classes" :key="character.id">
                <header>
                    <h2>{{ character.name }}</h2>
                    <code>{{ character.id }}</code>
                </header>
                <ul>
                    <li v-for="asc in character.ascendancies" :key="asc.id">
                        <span>{{ asc.name }}</span
                        ><code>{{ asc.id }} · {{ asc.kind }}</code>
                    </li>
                </ul>
            </section>
        </div></AppShell
    >
</template>

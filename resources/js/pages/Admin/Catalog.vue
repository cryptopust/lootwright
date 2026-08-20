<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
const props = defineProps<{
    catalogs: Array<{
        game: 'poe1' | 'poe2';
        version: string;
        patch: string;
        early_access: boolean;
        data_version: string;
        verified_at: string;
        source: string;
        classes: Array<{
            id: string;
            name: string;
            availability: string;
            ascendancies: Array<{
                id: string;
                name: string;
                type: string;
                kind: string;
            }>;
        }>;
    }>;
}>();
const selectedGame = ref<'poe1' | 'poe2'>('poe1');
const catalog = computed(
    () =>
        props.catalogs.find((item) => item.game === selectedGame.value) ??
        props.catalogs[0],
);
const counts = computed(() => ({
    total: catalog.value.classes.length,
    available: catalog.value.classes.filter(
        (item) => item.availability === 'available',
    ).length,
    planned: catalog.value.classes.filter(
        (item) => item.availability === 'planned',
    ).length,
    regular: catalog.value.classes
        .flatMap((item) => item.ascendancies)
        .filter((item) => item.type === 'regular').length,
    alternate: catalog.value.classes
        .flatMap((item) => item.ascendancies)
        .filter((item) => item.type === 'alternate').length,
}));
</script>
<template>
    <Head title="PoE katalogları" /><AppShell :contained="false"
        ><AdminNav current="catalog" />
        <header class="page-heading">
            <p class="kicker">
                {{ catalog.game === 'poe1' ? 'PoE 1' : 'PoE 2' }} ·
                {{ catalog.version }}
            </p>
            <h1>Sürüm kontrollü karakter kataloğu</h1>
            <p>{{ catalog.data_version }} · {{ catalog.verified_at }}</p>
            <a
                class="text-link"
                :href="catalog.source"
                rel="noopener noreferrer"
                >Kaynak metadata</a
            >
        </header>
        <div class="choice-list" aria-label="Katalog oyunu">
            <label v-for="game in ['poe1', 'poe2'] as const" :key="game"
                ><input
                    v-model="selectedGame"
                    type="radio"
                    name="catalog-game"
                    :value="game"
                /><span
                    ><strong>{{
                        game === 'poe1' ? 'PoE 1' : 'PoE 2'
                    }}</strong></span
                ></label
            >
        </div>
        <dl class="review-grid">
            <div>
                <dt>Toplam sınıf</dt>
                <dd>{{ counts.total }}</dd>
            </div>
            <div>
                <dt>Oynanabilir</dt>
                <dd>{{ counts.available }}</dd>
            </div>
            <div>
                <dt>Planned</dt>
                <dd>{{ counts.planned }}</dd>
            </div>
            <div>
                <dt>Normal Ascendancy</dt>
                <dd>{{ counts.regular }}</dd>
            </div>
            <div>
                <dt>Alternatif</dt>
                <dd>{{ counts.alternate }}</dd>
            </div>
            <div>
                <dt>Early Access</dt>
                <dd>{{ catalog.early_access ? 'Evet' : 'Hayır' }}</dd>
            </div>
        </dl>
        <div class="catalog-ledger">
            <section v-for="character in catalog.classes" :key="character.id">
                <header>
                    <h2>{{ character.name }}</h2>
                    <code
                        >{{ catalog.game }}:{{ character.id }} ·
                        {{ character.availability }}</code
                    >
                </header>
                <ul>
                    <li v-for="asc in character.ascendancies" :key="asc.id">
                        <span>{{ asc.name }}</span
                        ><code
                            >{{ catalog.game }}:{{ asc.id }} ·
                            {{ asc.type }}</code
                        >
                    </li>
                </ul>
            </section>
        </div></AppShell
    >
</template>

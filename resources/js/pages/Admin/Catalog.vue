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
    rulesets: Array<{
        id: string;
        game_edition: 'poe1' | 'poe2';
        version: string;
        patch: string;
        checksum_sha256: string;
        published_at: string;
        dataset_classification: string;
        provenance_status: string;
        compatibility_status: string;
        active: boolean;
        sources: string;
        source_checksums: string;
        import_failures: string;
        entity_counts: Record<string, number>;
    }>;
    importFailures: Array<{
        source_key: string;
        game_edition: 'poe1' | 'poe2';
        status: string;
        failure_code: string | null;
        started_at: string;
    }>;
    coverage: Record<
        'poe1' | 'poe2',
        Array<{
            category: string;
            ruleset_version: string | null;
            observed_records: number;
            expected_records: number | null;
            coverage_percent: number | null;
            status: string;
        }>
    >;
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
        <section class="catalog-ledger" aria-labelledby="coverage-heading">
            <header>
                <h2 id="coverage-heading">Canonical veri kapsamı</h2>
            </header>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Ruleset</th>
                        <th>Kayıt</th>
                        <th>Kapsam</th>
                        <th>Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="entry in props.coverage[selectedGame]"
                        :key="entry.category"
                    >
                        <td>
                            <code>{{ entry.category }}</code>
                        </td>
                        <td>{{ entry.ruleset_version ?? 'bilinmiyor' }}</td>
                        <td>
                            <code
                                >{{ entry.observed_records }} /
                                {{
                                    entry.expected_records ?? 'bilinmiyor'
                                }}</code
                            >
                        </td>
                        <td>
                            <code>{{
                                entry.coverage_percent === null
                                    ? 'bilinmiyor'
                                    : `${entry.coverage_percent}%`
                            }}</code>
                        </td>
                        <td>{{ entry.status }}</td>
                    </tr>
                </tbody>
            </table>
            <p>
                <em
                    >Beklenen toplam doğrulanmamışsa yüzdelik tahmin edilmez;
                    eksik kapsam açıkça gösterilir.</em
                >
            </p>
        </section>
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
        </div>
        <section class="catalog-ledger" aria-labelledby="ruleset-heading">
            <header>
                <h2 id="ruleset-heading">Canonical ruleset kayıtları</h2>
            </header>
            <article v-for="ruleset in props.rulesets" :key="ruleset.id">
                <header>
                    <h3>{{ ruleset.game_edition }} · {{ ruleset.version }}</h3>
                    <code
                        >{{ ruleset.patch }} ·
                        {{ ruleset.active ? 'active' : 'inactive' }}</code
                    >
                </header>
                <dl class="review-grid">
                    <div>
                        <dt>Dataset</dt>
                        <dd>{{ ruleset.dataset_classification }}</dd>
                    </div>
                    <div>
                        <dt>Provenance</dt>
                        <dd>{{ ruleset.provenance_status }}</dd>
                    </div>
                    <div>
                        <dt>Uyumluluk</dt>
                        <dd>{{ ruleset.compatibility_status }}</dd>
                    </div>
                    <div>
                        <dt>Import</dt>
                        <dd>{{ ruleset.published_at }}</dd>
                    </div>
                    <div>
                        <dt>Kaynak</dt>
                        <dd>{{ ruleset.sources || 'bilinmiyor' }}</dd>
                    </div>
                    <div>
                        <dt>Hata</dt>
                        <dd>{{ ruleset.import_failures || 'yok' }}</dd>
                    </div>
                </dl>
                <p>
                    <code>ruleset sha256: {{ ruleset.checksum_sha256 }}</code>
                </p>
                <p>
                    <code
                        >source sha256:
                        {{ ruleset.source_checksums || 'bilinmiyor' }}</code
                    >
                </p>
                <p>
                    <code>{{ JSON.stringify(ruleset.entity_counts) }}</code>
                </p>
            </article>
            <p v-if="props.rulesets.length === 0">
                <em>Onaylı imported canonical ruleset henüz yok.</em>
            </p>
        </section>
        <section class="catalog-ledger" aria-labelledby="failures-heading">
            <header><h2 id="failures-heading">Son import hataları</h2></header>
            <ul v-if="props.importFailures.length > 0">
                <li
                    v-for="failure in props.importFailures"
                    :key="`${failure.source_key}:${failure.started_at}`"
                >
                    <code
                        >{{ failure.game_edition }} · {{ failure.source_key }} ·
                        {{ failure.status }}</code
                    >
                    <span>{{ failure.failure_code || 'ayrıntı yok' }}</span>
                </li>
            </ul>
            <p v-else><em>Kayıtlı import hatası yok.</em></p>
        </section></AppShell
    >
</template>

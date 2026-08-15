<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppShell from '@/components/app/AppShell.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import { useLocale } from '@/composables/useLocale';
import { informationPages } from '@/data/information-pages';

const props = defineProps<{ page: keyof typeof informationPages }>();
const { tx } = useLocale();
const confirmation = ref('');
const deletionState = ref<'idle' | 'ready' | 'done'>('idle');
const content = computed(
    () => informationPages[props.page] ?? informationPages.limitations,
);

function prepareDeletion(): void {
    deletionState.value = confirmation.value === 'SİL' ? 'ready' : 'idle';
}

function simulateDeletion(): void {
    if (deletionState.value === 'ready') {
        deletionState.value = 'done';
    }
}
</script>

<template>
    <Head :title="tx(content.title)" />
    <AppShell :current="page === 'methodology' ? 'methodology' : ''">
        <header class="information-hero">
            <p class="kicker">{{ content.eyebrow }}</p>
            <h1>{{ tx(content.title) }}</h1>
            <p>{{ tx(content.summary) }}</p>
        </header>

        <div class="information-layout">
            <aside>
                <span>{{
                    tx({ tr: 'Bu bölümde', en: 'In this section' })
                }}</span>
                <a
                    v-for="(section, index) in content.sections"
                    :key="index"
                    :href="`#section-${index}`"
                    >{{ tx(section.title) }}</a
                >
            </aside>
            <div class="information-content">
                <section
                    v-for="(section, index) in content.sections"
                    :id="`section-${index}`"
                    :key="index"
                >
                    <span class="section-index">0{{ index + 1 }}</span>
                    <h2>{{ tx(section.title) }}</h2>
                    <p
                        v-for="(
                            paragraph, paragraphIndex
                        ) in section.paragraphs"
                        :key="paragraphIndex"
                    >
                        {{ tx(paragraph) }}
                    </p>
                    <ul v-if="section.bullets">
                        <li v-for="bullet in section.bullets" :key="tx(bullet)">
                            {{ tx(bullet) }}
                        </li>
                    </ul>
                </section>

                <section
                    v-if="page === 'deletion'"
                    class="deletion-demo"
                    aria-labelledby="deletion-demo-title"
                >
                    <span class="section-index">03</span>
                    <h2 id="deletion-demo-title">
                        {{
                            tx({
                                tr: 'Fixture silme kontrolü',
                                en: 'Fixture deletion control',
                            })
                        }}
                    </h2>
                    <StatusBanner
                        tone="warning"
                        :title="
                            tx({
                                tr: 'Bu işlem geri alınamaz',
                                en: 'This action cannot be undone',
                            })
                        "
                        :body="
                            tx({
                                tr: 'Demo kontrolü gerçek API çağrısı yapmaz. Üretimde işlem yalnızca owner-scoped workspace’i siler.',
                                en: 'The demo control makes no real API call. In production it deletes only the owner-scoped workspace.',
                            })
                        "
                    />
                    <label class="field">
                        <span>{{
                            tx({
                                tr: 'Devam etmek için SİL yaz',
                                en: 'Type SİL to continue',
                            })
                        }}</span>
                        <input
                            v-model="confirmation"
                            type="text"
                            autocomplete="off"
                            @input="prepareDeletion"
                        />
                    </label>
                    <button
                        type="button"
                        class="button is-danger"
                        :disabled="deletionState !== 'ready'"
                        @click="simulateDeletion"
                    >
                        {{
                            tx({
                                tr: 'Fixture workspace’i sil',
                                en: 'Delete fixture workspace',
                            })
                        }}
                    </button>
                    <p
                        v-if="deletionState === 'done'"
                        class="success-copy"
                        role="status"
                    >
                        {{
                            tx({
                                tr: 'Fixture silme akışı tamamlandı. Gerçek veri değişmedi.',
                                en: 'Fixture deletion flow completed. No real data changed.',
                            })
                        }}
                    </p>
                </section>
            </div>
        </div>
    </AppShell>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import { useLocale } from '@/composables/useLocale';
import type { EvidenceReference } from '@/types/analysis-ui';

defineProps<{
    evidence: EvidenceReference[];
    why: string;
    limitation: string;
}>();

const open = ref(false);
const { tx } = useLocale();
</script>

<template>
    <div class="evidence-disclosure">
        <button
            type="button"
            class="disclosure-trigger"
            :aria-expanded="open"
            @click="open = !open"
        >
            <span>{{ tx({ tr: 'Neden?', en: 'Why?' }) }}</span>
            <span aria-hidden="true">{{ open ? '−' : '+' }}</span>
        </button>
        <div v-if="open" class="evidence-body">
            <p>{{ why }}</p>
            <dl class="evidence-list">
                <template v-for="reference in evidence" :key="reference.rule">
                    <dt>
                        {{ tx({ tr: 'Girdi kanıtı', en: 'Input evidence' }) }}
                    </dt>
                    <dd>
                        <code>{{ reference.input }}</code>
                    </dd>
                    <dt>{{ tx({ tr: 'Kural', en: 'Rule' }) }}</dt>
                    <dd>
                        <code>{{ reference.rule }}</code>
                    </dd>
                    <dt>{{ tx({ tr: 'Kaynak', en: 'Source' }) }}</dt>
                    <dd>{{ reference.source }}</dd>
                </template>
            </dl>
            <p class="limitation-copy">
                <strong>{{ tx({ tr: 'Sınır:', en: 'Limitation:' }) }}</strong>
                {{ limitation }}
            </p>
        </div>
    </div>
</template>

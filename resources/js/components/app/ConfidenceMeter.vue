<script setup lang="ts">
import { computed } from 'vue';

import { useLocale } from '@/composables/useLocale';

const props = defineProps<{
    value: number;
    label?: string;
}>();

const { tx } = useLocale();
const boundedValue = computed(() => Math.max(0, Math.min(100, props.value)));
</script>

<template>
    <div class="confidence-meter">
        <div class="confidence-label">
            <span>{{ label ?? tx({ tr: 'Güven', en: 'Confidence' }) }}</span>
            <strong>{{ boundedValue }}%</strong>
        </div>
        <div
            class="confidence-track"
            role="progressbar"
            :aria-valuenow="boundedValue"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <span :style="{ width: `${boundedValue}%` }"></span>
        </div>
    </div>
</template>

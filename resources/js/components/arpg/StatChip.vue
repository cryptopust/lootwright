<script setup lang="ts">
import UnknownValue from './UnknownValue.vue';

defineProps<{ label: string; value: string | number | null; note?: string }>();
</script>

<template>
    <div class="stat-chip">
        <span>{{ label }}</span>
        <strong v-if="value !== null">{{ value }}</strong>
        <UnknownValue v-else :note="note" />
    </div>
</template>

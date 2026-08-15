<script setup lang="ts">
import type { GameEdition } from '@/types/analysis-ui';

defineProps<{
    edition: GameEdition;
    compact?: boolean;
}>();
</script>

<template>
    <span
        class="edition-badge"
        :class="[`is-${edition}`, { 'is-compact': compact }]"
        :aria-label="edition === 'poe1' ? 'Path of Exile 1' : 'Path of Exile 2'"
    >
        <span aria-hidden="true">{{ edition === 'poe1' ? 'I' : 'II' }}</span>
        {{ edition === 'poe1' ? 'PoE 1' : 'PoE 2' }}
    </span>
</template>

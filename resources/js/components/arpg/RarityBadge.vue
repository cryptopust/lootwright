<script setup lang="ts">
export type Rarity =
    'normal' | 'magic' | 'rare' | 'unique' | 'currency' | 'corrupted';

defineProps<{ rarity: Rarity }>();
</script>

<template>
    <span class="rarity-badge" :class="`is-${rarity}`">
        <span class="rarity-diamond" aria-hidden="true"></span>
        {{ rarity }}
    </span>
</template>

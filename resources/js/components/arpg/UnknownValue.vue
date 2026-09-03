<script setup lang="ts">
defineProps<{ note?: string }>();
</script>

<template>
    <span class="unknown-value"
        ><em>bilinmiyor</em><code v-if="note">{{ note }}</code></span
    >
</template>

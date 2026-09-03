<script setup lang="ts">
defineProps<{ rule: string; source: string; title?: string }>();
</script>

<template>
    <aside class="evidence-callout">
        <p>
            Kanıt · <code>{{ rule }}</code>
        </p>
        <h3 v-if="title">{{ title }}</h3>
        <div class="evidence-callout-body"><slot /></div>
        <footer>
            kaynak: <code>{{ source }}</code>
        </footer>
    </aside>
</template>

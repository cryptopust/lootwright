<script setup lang="ts">
defineProps<{
    tone: 'info' | 'warning' | 'danger' | 'success' | 'neutral';
    title: string;
    body: string;
    actionLabel?: string;
    actionHref?: string;
}>();
</script>

<template>
    <section class="status-banner" :class="`is-${tone}`" role="status">
        <span class="status-symbol" aria-hidden="true"></span>
        <div>
            <h2>{{ title }}</h2>
            <p>{{ body }}</p>
        </div>
        <a
            v-if="actionLabel && actionHref"
            class="text-link"
            :href="actionHref"
        >
            {{ actionLabel }}
        </a>
    </section>
</template>

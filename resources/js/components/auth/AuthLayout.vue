<script setup lang="ts">
import AppShell from '@/components/app/AppShell.vue';

defineProps<{ title: string; description: string }>();
</script>

<template>
    <AppShell current="account">
        <section class="auth-layout">
            <header>
                <p class="kicker">Güvenli Lootwright hesabı</p>
                <h1>{{ title }}</h1>
                <p>{{ description }}</p>
            </header>
            <div class="auth-panel"><slot /></div>
        </section>
    </AppShell>
</template>

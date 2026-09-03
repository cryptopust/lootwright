<script setup lang="ts">
defineProps<{ current: string }>();
</script>
<template>
    <nav class="section-nav" aria-label="Admin paneli">
        <a
            href="/admin"
            :aria-current="current === 'dashboard' ? 'page' : undefined"
            >Özet</a
        ><a
            href="/admin/users"
            :aria-current="current === 'users' ? 'page' : undefined"
            >Üyeler</a
        ><a
            href="/admin/audit-log"
            :aria-current="current === 'audit' ? 'page' : undefined"
            >Audit log</a
        ><a
            href="/admin/catalog"
            :aria-current="current === 'catalog' ? 'page' : undefined"
            >Katalog</a
        ><a
            href="/admin/system"
            :aria-current="current === 'system' ? 'page' : undefined"
            >Sistem</a
        ><a
            href="/admin/release"
            :aria-current="current === 'release' ? 'page' : undefined"
            >Release gate</a
        >
    </nav>
</template>

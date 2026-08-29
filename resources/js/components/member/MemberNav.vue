<script setup lang="ts">
defineProps<{ current: string }>();
</script>
<template>
    <nav class="section-nav" aria-label="Üye paneli">
        <a
            href="/dashboard"
            :aria-current="current === 'dashboard' ? 'page' : undefined"
            >Özet</a
        ><a
            href="/analyses"
            :aria-current="current === 'analyses' ? 'page' : undefined"
            >Analiz geçmişi</a
        ><a
            href="/saved"
            :aria-current="current === 'saved' ? 'page' : undefined"
            >Saved</a
        ><a
            href="/profile"
            :aria-current="current === 'profile' ? 'page' : undefined"
            >Profil</a
        ><a
            href="/profile/security"
            :aria-current="current === 'security' ? 'page' : undefined"
            >Güvenlik</a
        ><a
            href="/profile/privacy"
            :aria-current="current === 'privacy' ? 'page' : undefined"
            >Gizlilik</a
        ><a
            href="/usage"
            :aria-current="current === 'usage' ? 'page' : undefined"
            >AI kullanımı</a
        >
    </nav>
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
defineProps<{
    preferences: null | {
        ai_explanation_opt_in: boolean;
        store_normalized_analysis: boolean;
    };
}>();
const deletion = useForm({});
</script>
<template>
    <Head title="Gizlilik" /><AppShell
        ><MemberNav current="privacy" />
        <header class="page-heading">
            <p class="kicker">Veri kontrolü</p>
            <h1>Gizlilik ve silme</h1>
            <p>
                Ham PoB ve item metni normalize edildikten sonra tutulmaz.
                Güvenli taslaklar yedi gün içinde sona erer.
            </p>
        </header>
        <section class="settings-section danger-zone">
            <h2>Hesap ve verilerimi sil</h2>
            <p>
                Bu istek analizlerini, taslaklarını ve kullanıcıya bağlı
                verileri silme workflowuna alır. İşlem geri alınamaz.
            </p>
            <form @submit.prevent="deletion.delete('/api/user-data')">
                <button class="button is-danger">Silme talebi oluştur</button>
            </form>
        </section></AppShell
    >
</template>

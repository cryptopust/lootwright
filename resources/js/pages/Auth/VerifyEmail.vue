<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';
const form = useForm({});
</script>
<template>
    <Head title="E-posta doğrulama" /><AuthLayout
        title="E-postanı doğrula"
        description="Kalıcı analiz oluşturmak için gelen kutundaki doğrulama bağlantısını aç."
        ><form
            class="stack-form"
            @submit.prevent="form.post('/email/verification-notification')"
        >
            <button class="button is-primary" :disabled="form.processing">
                Doğrulama mesajını yeniden gönder</button
            ><a href="/profile">E-posta adresimi düzelt</a>
        </form></AuthLayout
    >
</template>

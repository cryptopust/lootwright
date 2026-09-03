<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';
const form = useForm({ email: '' });
</script>
<template>
    <Head title="Şifre sıfırlama" /><AuthLayout
        title="Şifreni sıfırla"
        description="Sana süreli ve tek kullanımlık bir sıfırlama bağlantısı gönderelim."
        ><form
            class="stack-form"
            @submit.prevent="form.post('/forgot-password')"
        >
            <label class="field"
                ><span>E-posta</span
                ><input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                /><small v-if="form.errors.email" role="alert">{{
                    form.errors.email
                }}</small></label
            ><button class="button is-primary" :disabled="form.processing">
                Bağlantı gönder</button
            ><a class="text-link" href="/login">Girişe dön</a>
        </form></AuthLayout
    >
</template>

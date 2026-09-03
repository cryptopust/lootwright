<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';
const form = useForm({ password: '' });
</script>
<template>
    <Head title="Şifre doğrulama" /><AuthLayout
        title="Şifreni doğrula"
        description="Bu hassas işlem için kısa süre önce kimliğini doğrulaman gerekiyor."
        ><form
            class="stack-form"
            @submit.prevent="form.post('/user/confirm-password')"
        >
            <label class="field"
                ><span>Şifre</span
                ><input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required /></label
            ><button class="button is-primary">Devam et</button>
        </form></AuthLayout
    >
</template>

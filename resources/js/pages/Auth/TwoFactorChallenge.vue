<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';
const form = useForm({ code: '', recovery_code: '' });
</script>
<template>
    <Head title="İki aşamalı doğrulama" /><AuthLayout
        title="Doğrulama kodu"
        description="Authenticator uygulamandaki kodu veya sakladığın recovery kodlarından birini kullan."
        ><form
            class="stack-form"
            @submit.prevent="form.post('/two-factor-challenge')"
        >
            <label class="field"
                ><span>6 haneli kod</span
                ><input
                    v-model="form.code"
                    inputmode="numeric"
                    autocomplete="one-time-code" /></label
            ><label class="field"
                ><span>Recovery kodu (alternatif)</span
                ><input
                    v-model="form.recovery_code"
                    autocomplete="one-time-code" /></label
            ><button class="button is-primary">Doğrula</button>
        </form></AuthLayout
    >
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';
const props = defineProps<{ email: string; token: string }>();
const form = useForm({
    email: props.email,
    token: props.token,
    password: '',
    password_confirmation: '',
});
</script>
<template>
    <Head title="Yeni şifre" /><AuthLayout
        title="Yeni şifre belirle"
        description="Yeni şifren güçlü ve yalnız bu hesap için olsun."
        ><form
            class="stack-form"
            @submit.prevent="form.post('/reset-password')"
        >
            <label class="field"
                ><span>E-posta</span
                ><input v-model="form.email" type="email" readonly /></label
            ><label class="field"
                ><span>Yeni şifre</span
                ><input
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    required /></label
            ><label class="field"
                ><span>Şifreyi doğrula</span
                ><input
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
            /></label>
            <div
                v-if="Object.keys(form.errors).length"
                class="form-error"
                role="alert"
            >
                <p v-for="error in form.errors" :key="error">{{ error }}</p>
            </div>
            <button class="button is-primary">Şifreyi güncelle</button>
        </form></AuthLayout
    >
</template>

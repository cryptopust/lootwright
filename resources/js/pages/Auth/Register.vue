<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
</script>
<template>
    <Head title="Kayıt" /><AuthLayout
        title="Lootwright hesabı oluştur"
        description="Analizlerini yalnız sana ait, doğrulanmış bir hesapta sakla."
        ><form class="stack-form" @submit.prevent="form.post('/register')">
            <label class="field"
                ><span>Ad</span
                ><input
                    v-model="form.name"
                    autocomplete="name"
                    required /></label
            ><label class="field"
                ><span>E-posta</span
                ><input
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required /></label
            ><label class="field"
                ><span>Şifre</span
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
            <button class="button is-primary" :disabled="form.processing">
                Hesap oluştur</button
            ><a class="text-link" href="/login">Zaten hesabım var</a>
        </form></AuthLayout
    >
</template>

<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/auth/AuthLayout.vue';

const form = useForm({ email: '', password: '', remember: false });
</script>

<template>
    <Head title="Giriş" />
    <AuthLayout
        title="Hesabına giriş yap"
        description="Kayıtlı analizlerine ve güvenli taslaklarına devam et."
    >
        <form
            class="stack-form"
            @submit.prevent="
                form.post('/login', { onFinish: () => form.reset('password') })
            "
        >
            <label class="field"
                ><span>E-posta</span
                ><input
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                /><small v-if="form.errors.email" role="alert">{{
                    form.errors.email
                }}</small></label
            >
            <label class="field"
                ><span>Şifre</span
                ><input
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    required
                /><small v-if="form.errors.password" role="alert">{{
                    form.errors.password
                }}</small></label
            >
            <label class="inline-choice"
                ><input v-model="form.remember" type="checkbox" /> Bu cihazda
                oturumu açık tut</label
            >
            <button class="button is-primary" :disabled="form.processing">
                {{ form.processing ? 'Giriş yapılıyor…' : 'Giriş yap' }}
            </button>
            <div class="auth-links">
                <a href="/forgot-password">Şifremi unuttum</a
                ><a href="/register">Yeni hesap oluştur</a>
            </div>
        </form>
    </AuthLayout>
</template>

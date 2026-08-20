<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
import type { User } from '@/types';
const user = usePage<{ auth: { user: User } }>().props.auth.user;
const form = useForm({ name: user.name, email: user.email });
</script>
<template>
    <Head title="Profil" /><AppShell
        ><MemberNav current="profile" />
        <header class="page-heading">
            <p class="kicker">Hesap</p>
            <h1>Profil bilgileri</h1>
        </header>
        <form
            class="stack-form settings-form"
            @submit.prevent="form.put('/user/profile-information')"
        >
            <label class="field"
                ><span>Ad</span
                ><input v-model="form.name" autocomplete="name" /></label
            ><label class="field"
                ><span>E-posta</span
                ><input v-model="form.email" type="email" autocomplete="email"
            /></label>
            <div
                v-if="Object.keys(form.errors).length"
                class="form-error"
                role="alert"
            >
                <p v-for="error in form.errors" :key="error">{{ error }}</p>
            </div>
            <button class="button is-primary">Profili kaydet</button>
        </form></AppShell
    >
</template>

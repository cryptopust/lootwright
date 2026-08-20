<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
const props = defineProps<{
    twoFactorEnabled: boolean;
    twoFactorPending: boolean;
    canDisableTwoFactor: boolean;
}>();
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const twoFactor = useForm({});
const confirmation = useForm({ code: '' });
const qrCode = ref('');
const pending = ref(props.twoFactorPending);

async function loadQrCode(): Promise<void> {
    const response = await fetch('/user/two-factor-qr-code', {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        return;
    }

    const body = await response.json();

    if (typeof body.svg === 'string') {
        qrCode.value = `data:image/svg+xml;base64,${window.btoa(body.svg)}`;
    }
}

function reloadPage(): void {
    window.location.reload();
}

onMounted(() => {
    if (pending.value) {
        void loadQrCode();
    }
});
</script>
<template>
    <Head title="Güvenlik" /><AppShell
        ><MemberNav current="security" />
        <header class="page-heading">
            <p class="kicker">Hesap güvenliği</p>
            <h1>Şifre ve iki aşamalı doğrulama</h1>
        </header>
        <section class="settings-section">
            <h2>Şifre değiştir</h2>
            <form
                class="stack-form settings-form"
                @submit.prevent="
                    form.put('/user/password', {
                        onSuccess: () => form.reset(),
                    })
                "
            >
                <label class="field"
                    ><span>Mevcut şifre</span
                    ><input
                        v-model="form.current_password"
                        type="password"
                        autocomplete="current-password" /></label
                ><label class="field"
                    ><span>Yeni şifre</span
                    ><input
                        v-model="form.password"
                        type="password"
                        autocomplete="new-password" /></label
                ><label class="field"
                    ><span>Yeni şifreyi doğrula</span
                    ><input
                        v-model="form.password_confirmation"
                        type="password"
                        autocomplete="new-password" /></label
                ><button class="button is-primary">Şifreyi güncelle</button>
            </form>
        </section>
        <section class="settings-section">
            <h2>İki aşamalı doğrulama</h2>
            <p>
                Admin ve super-admin hesaplarında zorunludur. Recovery kodlarını
                yalnız oluşturulduğu anda güvenli bir parola yöneticisine
                kaydet.
            </p>
            <form
                v-if="!twoFactorEnabled && !pending"
                @submit.prevent="
                    twoFactor.post('/user/two-factor-authentication', {
                        onSuccess: () => {
                            pending = true;
                            loadQrCode();
                        },
                    })
                "
            >
                <button class="button is-secondary">
                    2FA kurulumunu başlat
                </button>
            </form>
            <div v-else-if="pending" class="settings-form">
                <p>
                    Authenticator uygulamanla QR kodunu tara ve üretilen altı
                    haneli kodu doğrula. Recovery kodları bu panelde düz metin
                    olarak gösterilmez.
                </p>
                <img
                    v-if="qrCode"
                    :src="qrCode"
                    alt="İki aşamalı doğrulama kurulum QR kodu"
                    width="220"
                    height="220"
                />
                <form
                    class="stack-form settings-form"
                    @submit.prevent="
                        confirmation.post(
                            '/user/confirmed-two-factor-authentication',
                            { onSuccess: reloadPage },
                        )
                    "
                >
                    <label class="field">
                        <span>Doğrulama kodu</span>
                        <input
                            v-model="confirmation.code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            pattern="[0-9]{6}"
                            required
                        />
                    </label>
                    <button class="button is-primary">2FA'yı doğrula</button>
                </form>
            </div>
            <p v-else-if="twoFactorEnabled && !canDisableTwoFactor">
                Bu yetkili hesapta 2FA zorunludur ve devre dışı bırakılamaz.
            </p>
            <form
                v-else-if="twoFactorEnabled && canDisableTwoFactor"
                @submit.prevent="
                    twoFactor.delete('/user/two-factor-authentication')
                "
            >
                <button class="button is-danger">2FA'yı kapat</button>
            </form>
        </section></AppShell
    >
</template>

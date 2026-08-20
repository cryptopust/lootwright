<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
import type { User } from '@/types';
const props = defineProps<{
    managedUser: {
        id: number;
        name: string;
        email: string;
        role: string;
        status: string;
        email_verified_at: string | null;
        last_login_at: string | null;
        created_at: string;
        suspended_at: string | null;
        suspension_reason: string | null;
    };
    analysisCount: number;
}>();
const actor = usePage<{ auth: { user: User } }>().props.auth.user;
const statusForm = useForm({
    status: props.managedUser.status === 'active' ? 'suspended' : 'active',
    reason: '',
});
const roleForm = useForm({ role: props.managedUser.role, reason: '' });
</script>
<template>
    <Head title="Üye detayı" /><AppShell
        ><AdminNav current="users" />
        <header class="page-heading">
            <p class="kicker">Üye #{{ managedUser.id }}</p>
            <h1>{{ managedUser.name }}</h1>
            <p>{{ managedUser.email }}</p>
        </header>
        <dl class="detail-ledger">
            <div>
                <dt>Rol</dt>
                <dd>{{ managedUser.role }}</dd>
            </div>
            <div>
                <dt>Durum</dt>
                <dd>{{ managedUser.status }}</dd>
            </div>
            <div>
                <dt>E-posta</dt>
                <dd>
                    {{
                        managedUser.email_verified_at
                            ? 'Doğrulandı'
                            : 'Bekliyor'
                    }}
                </dd>
            </div>
            <div>
                <dt>Son giriş</dt>
                <dd>{{ managedUser.last_login_at ?? 'Yok' }}</dd>
            </div>
            <div>
                <dt>Analiz sayısı</dt>
                <dd>{{ analysisCount }}</dd>
            </div>
        </dl>
        <div class="admin-columns">
            <section class="settings-section">
                <h2>
                    {{
                        managedUser.status === 'active'
                            ? 'Askıya al'
                            : 'Yeniden aktifleştir'
                    }}
                </h2>
                <form
                    class="stack-form"
                    @submit.prevent="
                        statusForm.put(`/admin/users/${managedUser.id}/status`)
                    "
                >
                    <label class="field"
                        ><span>İşlem sebebi</span
                        ><textarea
                            v-model="statusForm.reason"
                            minlength="3"
                            maxlength="500"
                            required
                        /></label
                    ><button
                        class="button"
                        :class="
                            managedUser.status === 'active'
                                ? 'is-danger'
                                : 'is-secondary'
                        "
                    >
                        Durumu değiştir
                    </button>
                </form>
            </section>
            <section
                v-if="actor.role === 'super_admin'"
                class="settings-section"
            >
                <h2>Rol değiştir</h2>
                <form
                    class="stack-form"
                    @submit.prevent="
                        roleForm.put(`/admin/users/${managedUser.id}/role`)
                    "
                >
                    <label class="field"
                        ><span>Yeni rol</span
                        ><select v-model="roleForm.role">
                            <option value="member">member</option>
                            <option value="admin">admin</option>
                            <option value="super_admin">super_admin</option>
                        </select></label
                    ><label class="field"
                        ><span>İşlem sebebi</span
                        ><textarea
                            v-model="roleForm.reason"
                            minlength="3"
                            maxlength="500"
                            required
                        /></label
                    ><button class="button is-secondary">Rolü güncelle</button>
                </form>
            </section>
        </div></AppShell
    >
</template>

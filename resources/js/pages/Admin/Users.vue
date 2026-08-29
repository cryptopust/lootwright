<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AdminNav from '@/components/admin/AdminNav.vue';
import AppShell from '@/components/app/AppShell.vue';
const props = defineProps<{
    users: {
        data: Array<{
            id: number;
            name: string;
            email: string;
            role: string;
            status: string;
            email_verified_at: string | null;
            created_at: string;
            analyses_count: number;
        }>;
    };
    filters: Record<string, string>;
}>();
const filters = reactive({
    search: props.filters.search ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});
</script>
<template>
    <Head title="Üye yönetimi" /><AppShell :contained="false"
        ><AdminNav current="users" />
        <header class="page-heading">
            <p class="kicker">Server-side authorization</p>
            <h1>Üye yönetimi</h1>
        </header>
        <form
            class="filter-bar"
            @submit.prevent="router.get('/admin/users', filters)"
        >
            <label>Ara<input v-model="filters.search" /></label
            ><label
                >Rol<select v-model="filters.role">
                    <option value="">Tümü</option>
                    <option value="member">member</option>
                    <option value="admin">admin</option>
                    <option value="super_admin">super_admin</option>
                </select></label
            ><label
                >Durum<select v-model="filters.status">
                    <option value="">Tümü</option>
                    <option value="active">active</option>
                    <option value="suspended">suspended</option>
                </select></label
            ><button class="button is-secondary">Filtrele</button>
        </form>
        <div class="table-scroll" tabindex="0" aria-label="Üye tablosu">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Üye</th>
                        <th>Rol</th>
                        <th>Durum</th>
                        <th>Doğrulama</th>
                        <th>Analiz</th>
                        <th>Kayıt</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users.data" :key="user.id">
                        <td>
                            <a :href="`/admin/users/${user.id}`"
                                ><strong>{{ user.name }}</strong
                                ><br /><small>{{ user.email }}</small></a
                            >
                        </td>
                        <td>{{ user.role }}</td>
                        <td>{{ user.status }}</td>
                        <td>
                            {{
                                user.email_verified_at
                                    ? 'Doğrulandı'
                                    : 'Bekliyor'
                            }}
                        </td>
                        <td>{{ user.analyses_count }}</td>
                        <td>
                            {{
                                new Date(user.created_at).toLocaleDateString(
                                    'tr-TR',
                                )
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></AppShell
    >
</template>

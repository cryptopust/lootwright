<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/components/app/AppShell.vue';
import MemberNav from '@/components/member/MemberNav.vue';
defineProps<{
    analysis: {
        id: string;
        state: string;
        game_edition: string;
        version: number;
        failure_code: string | null;
        created_at: string;
        updated_at: string;
    };
}>();

function deleteAnalysis(id: string): void {
    if (
        window.confirm(
            'Bu analizi kalıcı olarak silmek istediğinden emin misin?',
        )
    ) {
        router.delete(`/analyses/${id}`);
    }
}
</script>
<template>
    <Head title="Analiz" /><AppShell current="analyses"
        ><MemberNav current="analyses" />
        <header class="page-heading">
            <p class="kicker">
                {{ analysis.game_edition }} · Sürüm {{ analysis.version }}
            </p>
            <h1>Analiz {{ analysis.id }}</h1>
            <p>
                Durum: <span class="status-chip">{{ analysis.state }}</span>
            </p>
        </header>
        <dl class="detail-ledger">
            <div>
                <dt>Oluşturuldu</dt>
                <dd>
                    {{ new Date(analysis.created_at).toLocaleString('tr-TR') }}
                </dd>
            </div>
            <div>
                <dt>Güncellendi</dt>
                <dd>
                    {{ new Date(analysis.updated_at).toLocaleString('tr-TR') }}
                </dd>
            </div>
            <div>
                <dt>Hata kodu</dt>
                <dd>{{ analysis.failure_code ?? 'Yok' }}</dd>
            </div>
        </dl>
        <div class="action-row">
            <a
                :href="`/api/analyses/${analysis.id}/export`"
                class="button is-secondary"
                >Veriyi dışa aktar</a
            >
            <button
                type="button"
                class="button is-danger"
                @click="deleteAnalysis(analysis.id)"
            >
                Analizi sil
            </button>
        </div></AppShell
    >
</template>

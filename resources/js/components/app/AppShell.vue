<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';

import BrandMark from '@/components/app/BrandMark.vue';
import { useLocale } from '@/composables/useLocale';

withDefaults(
    defineProps<{
        current?: string;
        contained?: boolean;
    }>(),
    {
        current: '',
        contained: true,
    },
);

const menuOpen = ref(false);
const { locale, setLocale, tx } = useLocale();

function closeMenu(): void {
    menuOpen.value = false;
}

function onEscape(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeMenu();
    }
}

if (typeof window !== 'undefined') {
    window.addEventListener('keydown', onEscape);
}

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', onEscape);
    }
});

const navigation = [
    {
        key: 'new',
        href: '/analyses/new',
        tr: 'Yeni analiz',
        en: 'New analysis',
    },
    {
        key: 'demo',
        href: '/analyses/demo/overview',
        tr: 'Demo çalışma alanı',
        en: 'Demo workspace',
    },
    {
        key: 'methodology',
        href: '/methodology',
        tr: 'Metodoloji',
        en: 'Methodology',
    },
];
</script>

<template>
    <a class="skip-link" href="#main-content">
        {{ tx({ tr: 'İçeriğe geç', en: 'Skip to content' }) }}
    </a>

    <div class="app-frame">
        <header class="app-header">
            <a class="brand" href="/" aria-label="Lootwright ana sayfa">
                <BrandMark />
                <span class="brand-word">Lootwright</span>
            </a>

            <button
                type="button"
                class="mobile-menu-button"
                :aria-expanded="menuOpen"
                aria-controls="primary-navigation"
                @click="menuOpen = !menuOpen"
            >
                {{ tx({ tr: 'Menü', en: 'Menu' }) }}
                <span aria-hidden="true">{{ menuOpen ? '×' : '≡' }}</span>
            </button>

            <nav
                id="primary-navigation"
                class="primary-navigation"
                :class="{ 'is-open': menuOpen }"
                :aria-label="
                    tx({ tr: 'Ana navigasyon', en: 'Primary navigation' })
                "
            >
                <a
                    v-for="item in navigation"
                    :key="item.key"
                    :href="item.href"
                    :aria-current="current === item.key ? 'page' : undefined"
                    @click="closeMenu"
                >
                    {{ locale === 'tr' ? item.tr : item.en }}
                </a>
                <div
                    class="locale-switcher"
                    :aria-label="tx({ tr: 'Dil', en: 'Language' })"
                >
                    <button
                        type="button"
                        :aria-pressed="locale === 'tr'"
                        @click="setLocale('tr')"
                    >
                        TR
                    </button>
                    <button
                        type="button"
                        :aria-pressed="locale === 'en'"
                        @click="setLocale('en')"
                    >
                        EN
                    </button>
                </div>
            </nav>
        </header>

        <main
            id="main-content"
            :class="['app-main', { 'is-wide': !contained }]"
        >
            <slot />
        </main>

        <footer class="app-footer">
            <div>
                <strong>Lootwright</strong>
                <span>{{
                    tx({
                        tr: 'Kanıt odaklı, manuel tasarım',
                        en: 'Evidence-led, manual by design',
                    })
                }}</span>
            </div>
            <nav
                :aria-label="
                    tx({ tr: 'Yasal ve politika', en: 'Legal and policy' })
                "
            >
                <a href="/privacy">{{
                    tx({ tr: 'Gizlilik', en: 'Privacy' })
                }}</a>
                <a href="/limitations">{{
                    tx({ tr: 'Sınırlamalar', en: 'Limitations' })
                }}</a>
                <a href="/non-affiliation">{{
                    tx({ tr: 'Bağımsızlık', en: 'Non-affiliation' })
                }}</a>
                <a href="/funding">{{
                    tx({ tr: 'Finansman durumu', en: 'Funding status' })
                }}</a>
            </nav>
            <p class="non-affiliation">
                This product isn't affiliated with or endorsed by Grinding Gear
                Games in any way.
            </p>
        </footer>
    </div>
</template>

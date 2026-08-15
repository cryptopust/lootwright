import { computed, onMounted, ref } from 'vue';

export type AppLocale = 'tr' | 'en';
export type LocalizedCopy = Record<AppLocale, string>;

const locale = ref<AppLocale>('tr');
let initialized = false;

function applyLocale(value: AppLocale): void {
    locale.value = value;

    if (typeof document !== 'undefined') {
        document.documentElement.lang = value;
    }

    if (typeof window !== 'undefined') {
        window.localStorage.setItem('lootwright.locale', value);
    }
}

export function useLocale() {
    onMounted(() => {
        if (initialized || typeof window === 'undefined') {
            return;
        }

        initialized = true;
        const stored = window.localStorage.getItem('lootwright.locale');

        if (stored === 'tr' || stored === 'en') {
            applyLocale(stored);
        } else {
            applyLocale('tr');
        }
    });

    return {
        locale: computed(() => locale.value),
        setLocale: applyLocale,
        tx: (copy: LocalizedCopy): string => copy[locale.value],
    };
}

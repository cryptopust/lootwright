<script setup lang="ts">
import ConfidenceMeter from '@/components/app/ConfidenceMeter.vue';
import { useLocale } from '@/composables/useLocale';
import type { DemoUpgrade } from '@/types/analysis-ui';

defineProps<{ upgrade: DemoUpgrade }>();
const { tx } = useLocale();
</script>

<template>
    <article class="upgrade-row">
        <div
            class="upgrade-rank"
            :aria-label="`${tx({ tr: 'Öncelik', en: 'Priority' })} ${upgrade.rank}`"
        >
            <span>0{{ upgrade.rank }}</span>
            <small>{{ tx({ tr: 'Öncelik', en: 'Priority' }) }}</small>
        </div>
        <div class="upgrade-main">
            <header>
                <span class="slot-label">{{ upgrade.slot }}</span>
                <code>{{ upgrade.code }}</code>
            </header>
            <h3>{{ tx(upgrade.title) }}</h3>
            <p>{{ tx(upgrade.reason) }}</p>
            <dl class="upgrade-meta">
                <div>
                    <dt>{{ tx({ tr: 'Bütçe bandı', en: 'Budget band' }) }}</dt>
                    <dd>{{ tx(upgrade.budgetBand) }}</dd>
                </div>
                <div>
                    <dt>
                        {{
                            tx({ tr: 'Kaynak bulgular', en: 'Source findings' })
                        }}
                    </dt>
                    <dd>
                        <code>{{ upgrade.findingCodes.join(', ') }}</code>
                    </dd>
                </div>
            </dl>
            <div v-if="upgrade.dependencies.length" class="dependency-block">
                <strong>{{
                    tx({ tr: 'Bağımlılıklar', en: 'Dependencies' })
                }}</strong>
                <ul>
                    <li
                        v-for="dependency in upgrade.dependencies"
                        :key="dependency"
                    >
                        {{ dependency }}
                    </li>
                </ul>
            </div>
            <p class="limitation-copy">
                <strong>{{ tx({ tr: 'Sınır:', en: 'Limitation:' }) }}</strong>
                {{ tx(upgrade.limitation) }}
            </p>
        </div>
        <ConfidenceMeter :value="upgrade.confidence" />
    </article>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

import ConfidenceMeter from '@/components/app/ConfidenceMeter.vue';
import EditionBadge from '@/components/app/EditionBadge.vue';
import TerminalBlock from '@/components/arpg/TerminalBlock.vue';
import { useLocale } from '@/composables/useLocale';
import type {
    DemoRecipe,
    DemoRecipeVariant,
    TradeRecipeView,
} from '@/types/analysis-ui';

type Recipe = DemoRecipe | TradeRecipeView;

const props = withDefaults(
    defineProps<{
        recipe: Recipe;
        externalLinkEnabled?: boolean;
    }>(),
    { externalLinkEnabled: true },
);
const variant = ref<'strict' | 'broad'>('strict');
const copyStatus = ref<'idle' | 'copied' | 'failed'>('idle');
const { tx } = useLocale();
const isProductionRecipe = computed(
    () => 'strict_recipe' in props.recipe,
);
const filters = computed<DemoRecipeVariant>(() => {
    if (!isProductionRecipe.value) {
        return (props.recipe as DemoRecipe)[variant.value];
    }

    const recipe = props.recipe as TradeRecipeView;
    const map = (items: TradeRecipeView['required_modifiers']) =>
        items.map((filter) => ({
            label: filter.label,
            minimum: filter.minimum,
            weight: filter.weight,
            reason: { tr: recipe.explanation, en: recipe.explanation },
            findingCode: filter.canonical_modifier_id,
        }));

    return {
        required: map(recipe.required_modifiers),
        optional: map(recipe.optional_modifiers),
        excluded: map(recipe.excluded_modifiers),
    };
});
const edition = computed(() =>
    isProductionRecipe.value
        ? (props.recipe as TradeRecipeView).game_edition
        : 'poe1',
);
const itemContext = computed(() =>
    isProductionRecipe.value
        ? ((props.recipe as TradeRecipeView).item_class ?? 'Item class bilinmiyor')
        : `${(props.recipe as DemoRecipe).category} · ${(props.recipe as DemoRecipe).baseFamily}`,
);
const budgetContext = computed(() =>
    isProductionRecipe.value
        ? 'Market price bilinmiyor'
        : (props.recipe as DemoRecipe).budget,
);
const confidence = computed(() =>
    isProductionRecipe.value ? null : (props.recipe as DemoRecipe).confidence,
);
const dependencies = computed(() =>
    props.recipe.dependencies.map((dependency) =>
        typeof dependency === 'string'
            ? dependency
            : `${dependency.slot}: ${dependency.reason}`,
    ),
);
const rulesetVersion = computed(() =>
    isProductionRecipe.value
        ? (props.recipe as TradeRecipeView).ruleset.version
        : '1.4.2-fixture',
);
const source = computed(() =>
    isProductionRecipe.value
        ? `${(props.recipe as TradeRecipeView).provenance.source_id} / ${(props.recipe as TradeRecipeView).provenance.source_version}`
        : 'LOOTWRIGHT-001 / fixture-1',
);
const unsupportedFilters = computed(() =>
    isProductionRecipe.value
        ? (props.recipe as TradeRecipeView).unsupported_filters
        : [],
);
const renderedText = computed(() => {
    if (isProductionRecipe.value) {
        const recipe = props.recipe as TradeRecipeView;

        return variant.value === 'strict'
            ? recipe.strict_recipe
            : recipe.broad_recipe;
    }

    const lines = ['Lootwright manual Trade recipe', `Slot: ${props.recipe.slot}`];

    for (const [heading, values] of Object.entries({
        Required: filters.value.required,
        Optional: filters.value.optional,
        Excluded: filters.value.excluded,
    })) {
        lines.push('', `${heading}:`);
        lines.push(
            ...(values.length === 0
                ? ['- none']
                : values.map(
                      (filter) =>
                          `- ${filter.label}${filter.minimum ? ` · min ${filter.minimum}` : ''}${filter.maximum ? ` · max ${filter.maximum}` : ''}${filter.weight ? ` · weight ${filter.weight}` : ''}`,
                  )),
        );
    }

    return lines.join('\n');
});

async function copyRecipe(): Promise<void> {
    copyStatus.value = 'idle';

    try {
        await navigator.clipboard.writeText(renderedText.value);
        copyStatus.value = 'copied';
    } catch {
        copyStatus.value = 'failed';
    }
}
</script>

<template>
    <article class="recipe-sheet">
        <header class="recipe-header">
            <div>
                <p class="kicker">
                    {{
                        tx({
                            tr: 'Manuel Trade tarifi',
                            en: 'Manual Trade recipe',
                        })
                    }}
                </p>
                <h2>{{ recipe.slot }}</h2>
                <p>{{ itemContext }}</p>
            </div>
            <div class="recipe-context">
                <EditionBadge :edition="edition" compact />
                <span v-if="!isProductionRecipe">PC</span>
                <span v-if="!isProductionRecipe">Fixture League</span>
            </div>
        </header>

        <div class="recipe-budget">
            <span>{{ tx({ tr: 'Bütçe bağlamı', en: 'Budget context' }) }}</span>
            <strong>{{ budgetContext }}</strong>
        </div>

        <div class="recipe-toolbar">
            <div
                class="segmented-control"
                role="group"
                :aria-label="tx({ tr: 'Tarif varyantı', en: 'Recipe variant' })"
            >
                <button
                    type="button"
                    :aria-pressed="variant === 'strict'"
                    @click="variant = 'strict'"
                >
                    {{ tx({ tr: 'Katı tarif', en: 'Strict recipe' }) }}
                </button>
                <button
                    type="button"
                    :aria-pressed="variant === 'broad'"
                    @click="variant = 'broad'"
                >
                    {{ tx({ tr: 'Geniş fallback', en: 'Broad fallback' }) }}
                </button>
            </div>
            <button
                type="button"
                class="button is-secondary"
                @click="copyRecipe"
            >
                {{
                    tx({
                        tr: 'Düz metin tarifi kopyala',
                        en: 'Copy plain-text recipe',
                    })
                }}
            </button>
        </div>

        <p class="copy-status" aria-live="polite">
            <template v-if="copyStatus === 'copied'">{{
                tx({
                    tr: 'Tarif kopyalandı. URL kopyalanmadı.',
                    en: 'Recipe copied. No URL was copied.',
                })
            }}</template>
            <template v-else-if="copyStatus === 'failed'">{{
                tx({
                    tr: 'Tarayıcı kopyalamaya izin vermedi.',
                    en: 'The browser did not allow copying.',
                })
            }}</template>
        </p>

        <TerminalBlock
            :content="renderedText"
            :label="
                tx({
                    tr: 'Satır numaralı manuel filtre tarifi',
                    en: 'Line-numbered manual filter recipe',
                })
            "
        />

        <div class="filter-columns">
            <section>
                <h3>
                    {{
                        tx({ tr: 'Zorunlu filtreler', en: 'Required filters' })
                    }}
                </h3>
                <p v-if="filters.required.length === 0" class="empty-inline">
                    {{
                        tx({
                            tr: 'Zorunlu filtre yok.',
                            en: 'No required filters.',
                        })
                    }}
                </p>
                <ul class="filter-list">
                    <li v-for="filter in filters.required" :key="filter.label">
                        <strong>{{ filter.label }}</strong>
                        <code v-if="filter.minimum"
                            >min {{ filter.minimum }}</code
                        >
                        <code v-if="filter.maximum"
                            >max {{ filter.maximum }}</code
                        >
                        <p>{{ tx(filter.reason) }}</p>
                        <small>{{ filter.findingCode }}</small>
                    </li>
                </ul>
            </section>
            <section>
                <h3>
                    {{
                        tx({
                            tr: 'Ağırlıklı isteğe bağlı',
                            en: 'Weighted optional',
                        })
                    }}
                </h3>
                <p v-if="filters.optional.length === 0" class="empty-inline">
                    {{
                        tx({
                            tr: 'İsteğe bağlı filtre yok.',
                            en: 'No optional filters.',
                        })
                    }}
                </p>
                <ul class="filter-list is-optional">
                    <li v-for="filter in filters.optional" :key="filter.label">
                        <strong>{{ filter.label }}</strong>
                        <code v-if="filter.minimum"
                            >min {{ filter.minimum }}</code
                        >
                        <code v-if="filter.weight"
                            >weight {{ filter.weight }}</code
                        >
                        <p>{{ tx(filter.reason) }}</p>
                        <small>{{ filter.findingCode }}</small>
                    </li>
                </ul>
            </section>
            <section>
                <h3>{{ tx({ tr: 'Hariç tutulanlar', en: 'Excluded' }) }}</h3>
                <p v-if="filters.excluded.length === 0" class="empty-inline">
                    {{
                        tx({
                            tr: 'Hariç tutulan filtre yok.',
                            en: 'No excluded filters.',
                        })
                    }}
                </p>
                <ul class="filter-list is-excluded">
                    <li v-for="filter in filters.excluded" :key="filter.label">
                        <strong>{{ filter.label }}</strong>
                        <p>{{ tx(filter.reason) }}</p>
                        <small>{{ filter.findingCode }}</small>
                    </li>
                </ul>
            </section>
        </div>

        <div class="recipe-footer">
            <div>
                <h3>
                    {{
                        tx({
                            tr: 'Slot bağımlılıkları',
                            en: 'Slot dependencies',
                        })
                    }}
                </h3>
                <ul>
                    <li
                        v-for="dependency in dependencies"
                        :key="dependency"
                    >
                        {{ dependency }}
                    </li>
                </ul>
            </div>
            <ConfidenceMeter v-if="confidence !== null" :value="confidence" />
            <dl>
                <div>
                    <dt>Ruleset</dt>
                    <dd>{{ rulesetVersion }}</dd>
                </div>
                <div>
                    <dt>Source</dt>
                    <dd>{{ source }}</dd>
                </div>
            </dl>
        </div>

        <section v-if="unsupportedFilters.length > 0" class="manual-action-boundary">
            <div>
                <strong>{{ tx({ tr: 'Desteklenmeyen filtreler', en: 'Unsupported filters' }) }}</strong>
                <ul>
                    <li
                        v-for="filter in unsupportedFilters"
                        :key="filter.modifier_id ?? filter.candidate"
                    >
                        <code>{{ filter.modifier_id ?? filter.candidate }}</code>
                        — {{ filter.reason }}
                    </li>
                </ul>
            </div>
        </section>

        <div class="manual-action-boundary">
            <div>
                <strong>{{
                    tx({
                        tr: 'Filtreleri sen uygularsın',
                        en: 'You apply the filters',
                    })
                }}</strong>
                <p>
                    {{
                        tx({
                            tr: 'Lootwright ilan getirmez, fiyat hesaplamaz veya arama URL’si üretmez.',
                            en: 'Lootwright does not fetch listings, calculate prices, or generate a search URL.',
                        })
                    }}
                </p>
            </div>
            <a
                v-if="externalLinkEnabled"
                class="button is-primary"
                href="https://www.pathofexile.com/trade"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{
                    tx({
                        tr: 'Resmî PoE1 Trade ana sayfasını aç',
                        en: 'Open the official PoE1 Trade homepage',
                    })
                }}
            </a>
            <span v-else class="status-chip is-danger">
                {{
                    tx({
                        tr: 'Harici bağlantılar acil durum anahtarıyla kapalı',
                        en: 'External links are disabled by the emergency switch',
                    })
                }}
            </span>
        </div>
    </article>
</template>

<script setup lang="ts">
import AffixRow from './AffixRow.vue';
import RarityBadge from './RarityBadge.vue';
import type { Rarity } from './RarityBadge.vue';

export interface ItemAffix {
    text: string;
    value: string;
    minimum: number;
    maximum: number;
    roll: number;
    tier: string;
    crafted?: boolean;
}

defineProps<{
    slot: string;
    name: string;
    baseName: string;
    rarity: Rarity;
    ilvl: number;
    affixes: ItemAffix[];
    fixture?: boolean;
}>();
</script>

<template>
    <article class="item-card" :class="`is-${rarity}`">
        <header>
            <div>
                <p class="slot-label">{{ slot }}</p>
                <h3>{{ name }}</h3>
                <p>{{ baseName }}</p>
            </div>
            <div class="item-meta">
                <RarityBadge :rarity="rarity" /><code>ilvl {{ ilvl }}</code>
            </div>
        </header>
        <p v-if="fixture" class="fixture-note">
            FIXTURE · CANLI OYUN İDDİASI DEĞİL
        </p>
        <ul class="affix-list">
            <AffixRow
                v-for="affix in affixes"
                :key="`${affix.text}-${affix.tier}`"
                v-bind="affix"
            />
        </ul>
    </article>
</template>

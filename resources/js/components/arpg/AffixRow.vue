<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        text: string;
        value: string;
        minimum: number;
        maximum: number;
        roll: number;
        tier: string;
        crafted?: boolean;
    }>(),
    { crafted: false },
);

const percentage = Math.max(
    0,
    Math.min(
        100,
        ((props.roll - props.minimum) /
            Math.max(1, props.maximum - props.minimum)) *
            100,
    ),
);
</script>

<template>
    <li class="affix-row">
        <div>
            <span>{{ text }}</span
            ><code>{{ value }}</code>
        </div>
        <div
            class="affix-track"
            role="img"
            :aria-label="`${text}: ${roll}, aralık ${minimum} ile ${maximum}`"
        >
            <span :style="{ width: `${percentage}%` }"></span>
        </div>
        <footer>
            <code>{{ minimum }}–{{ maximum }}</code
            ><code class="affix-tier">{{ tier }}</code
            ><code v-if="crafted" class="crafted-label">crafted</code>
        </footer>
    </li>
</template>

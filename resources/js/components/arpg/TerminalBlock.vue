<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{ content: string; label?: string }>();
const status = ref<'idle' | 'copied' | 'failed'>('idle');
const lines = computed(() => props.content.split('\n'));

function parts(line: string): Array<{ text: string; threshold: boolean }> {
    return line
        .split(/(-?\d+(?:\.\d+)?%?)/g)
        .filter(Boolean)
        .map((text) => ({
            text,
            threshold: /^-?\d+(?:\.\d+)?%?$/.test(text),
        }));
}

async function copy(): Promise<void> {
    try {
        await navigator.clipboard.writeText(props.content);
        status.value = 'copied';
    } catch {
        status.value = 'failed';
    }
}
</script>

<template>
    <section
        class="terminal-block"
        :aria-label="label ?? 'Manuel filtre tarifi'"
    >
        <header>
            <span>LOOTWRIGHT / RECIPE</span
            ><button type="button" @click="copy">
                {{
                    status === 'copied'
                        ? 'Kopyalandı'
                        : status === 'failed'
                          ? 'Kopyalanamadı'
                          : 'Kopyala'
                }}
            </button>
        </header>
        <ol>
            <li v-for="(line, index) in lines" :key="`${index}-${line}`">
                <code :class="{ 'is-comment': line.trim().startsWith('#') }"
                    ><template v-if="line"
                        ><span
                            v-for="(part, partIndex) in parts(line)"
                            :key="`${partIndex}-${part.text}`"
                            :class="{ 'is-threshold': part.threshold }"
                            >{{ part.text }}</span
                        ></template
                    ><template v-else> </template
                ></code>
            </li>
        </ol>
    </section>
</template>

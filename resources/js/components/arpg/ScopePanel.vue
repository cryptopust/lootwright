<script setup lang="ts">
withDefaults(
    defineProps<{ policy?: string; does: string[]; doesNot: string[] }>(),
    { policy: '1.0.0' },
);
</script>

<template>
    <aside class="scope-panel">
        <header>
            <span>LOOTWRIGHT / SCOPE</span><code>POLICY {{ policy }}</code>
        </header>
        <div>
            <section>
                <h2>Yaptığı <span aria-hidden="true">✓</span></h2>
                <ul>
                    <li v-for="item in does" :key="item">{{ item }}</li>
                </ul>
            </section>
            <section>
                <h2>Yapmadığı <span aria-hidden="true">×</span></h2>
                <ul>
                    <li v-for="item in doesNot" :key="item">{{ item }}</li>
                </ul>
            </section>
        </div>
    </aside>
</template>

<script setup lang="ts">
import ConfidenceMeter from '@/components/app/ConfidenceMeter.vue';
import EvidenceDisclosure from '@/components/app/EvidenceDisclosure.vue';
import { useLocale } from '@/composables/useLocale';
import type { DemoFinding } from '@/types/analysis-ui';

defineProps<{ finding: DemoFinding }>();
const { tx } = useLocale();
</script>

<template>
    <article class="finding-row" :class="`is-${finding.severity}`">
        <header>
            <div>
                <span class="severity-label">{{ finding.severity }}</span>
                <span class="category-label">{{ finding.category }}</span>
            </div>
            <code>{{ finding.code }}</code>
        </header>
        <div class="finding-content">
            <div>
                <h3>{{ tx(finding.title) }}</h3>
                <p>{{ tx(finding.summary) }}</p>
            </div>
            <ConfidenceMeter :value="finding.confidence" />
        </div>
        <EvidenceDisclosure
            :why="tx(finding.why)"
            :limitation="tx(finding.limitation)"
            :evidence="finding.evidence"
        />
    </article>
</template>

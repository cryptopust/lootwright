<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';

const principles = [
    {
        number: '01',
        title: 'Deterministic first',
        body: 'The same normalized input and ruleset must produce the same evidence-backed result.',
    },
    {
        number: '02',
        title: 'Manual by design',
        body: 'Search guidance remains a readable recipe that the player chooses how to use.',
    },
    {
        number: '03',
        title: 'Permission before data',
        body: 'Unknown provenance closes a door. It never becomes a convenient fallback.',
    },
];
</script>

<template>
    <Head title="Foundation">
        <meta
            head-key="description"
            name="description"
            content="Lootwright is an evidence-first build analysis and manual item-search planning project."
        />
    </Head>

    <a class="skip-link" href="#main-content">Skip to content</a>

    <div class="site-frame">
        <header class="masthead" aria-label="Primary">
            <a class="brand" href="/" aria-label="Lootwright home">
                <span class="brand-mark" aria-hidden="true">
                    <span></span>
                </span>
                <span class="brand-word">Lootwright</span>
            </a>

            <div class="release-stamp" aria-label="Current product phase">
                <span class="release-dot"></span>
                Foundation · PoE1 first
            </div>
        </header>

        <main id="main-content">
            <section class="hero" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <p class="eyebrow">Evidence-led build analysis</p>
                    <h1 id="hero-title">
                        Build decisions,
                        <em>written in evidence.</em>
                    </h1>
                    <p class="hero-summary">
                        Lootwright is becoming a calm workshop for understanding
                        a build, ranking useful upgrades, and drafting a manual
                        search recipe—without turning judgment over to a black
                        box.
                    </p>

                    <div class="hero-actions">
                        <Button as="a" href="#principles" size="lg">
                            Read the principles
                            <span aria-hidden="true">↓</span>
                        </Button>
                        <span class="availability"
                            >Foundation only · analysis not yet enabled</span
                        >
                    </div>
                </div>

                <aside class="ledger" aria-label="Planned workflow ledger">
                    <div class="ledger-head">
                        <span>Workflow ledger</span>
                        <span>LW / 001</span>
                    </div>

                    <div class="ledger-mark" aria-hidden="true">
                        <span class="ledger-orbit ledger-orbit-one"></span>
                        <span class="ledger-orbit ledger-orbit-two"></span>
                        <span class="ledger-axis"></span>
                        <span class="ledger-core">LW</span>
                    </div>

                    <ol class="ledger-list">
                        <li>
                            <span class="ledger-index">A</span>
                            <span>
                                <strong>Submitted facts</strong>
                                <small>User-controlled input</small>
                            </span>
                            <span class="ledger-state is-now">Foundation</span>
                        </li>
                        <li>
                            <span class="ledger-index">B</span>
                            <span>
                                <strong>Deterministic findings</strong>
                                <small>Versioned evidence</small>
                            </span>
                            <span class="ledger-state">Planned</span>
                        </li>
                        <li>
                            <span class="ledger-index">C</span>
                            <span>
                                <strong>Manual recipe</strong>
                                <small>No automated trade</small>
                            </span>
                            <span class="ledger-state">Planned</span>
                        </li>
                    </ol>
                </aside>
            </section>

            <section
                id="principles"
                class="principles"
                aria-labelledby="principles-title"
            >
                <div class="section-heading">
                    <p class="eyebrow">Workshop rules</p>
                    <h2 id="principles-title">Trust is part of the output.</h2>
                </div>

                <ol class="principle-grid">
                    <li v-for="principle in principles" :key="principle.number">
                        <span class="principle-number">{{
                            principle.number
                        }}</span>
                        <h3>{{ principle.title }}</h3>
                        <p>{{ principle.body }}</p>
                    </li>
                </ol>
            </section>

            <section class="boundary" aria-labelledby="boundary-title">
                <p class="eyebrow">A deliberate boundary</p>
                <h2 id="boundary-title">
                    Analysis should sharpen agency,
                    <span>not automate it away.</span>
                </h2>
                <p>
                    No scraping, live prices, browser automation, game-client
                    inspection, or automated trading. Lootwright stays a web
                    tool for explanations and human-readable planning.
                </p>
            </section>
        </main>

        <footer class="site-footer">
            <div>
                <span class="footer-brand">Lootwright</span>
                <span>Open-source · lootwright.org</span>
            </div>
            <p>
                This product isn't affiliated with or endorsed by Grinding Gear
                Games in any way.
            </p>
        </footer>
    </div>
</template>

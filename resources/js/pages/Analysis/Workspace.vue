<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

import FindingRow from '@/components/analysis/FindingRow.vue';
import ManualTradeRecipeCard from '@/components/analysis/ManualTradeRecipeCard.vue';
import UpgradeRow from '@/components/analysis/UpgradeRow.vue';
import AppShell from '@/components/app/AppShell.vue';
import ConfidenceMeter from '@/components/app/ConfidenceMeter.vue';
import EditionBadge from '@/components/app/EditionBadge.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import { useLocale } from '@/composables/useLocale';
import {
    demoBuild,
    demoFindings,
    demoRecipes,
    demoUpgrades,
} from '@/data/demo-analysis';
import type { AnalysisSection } from '@/types/analysis-ui';

const props = defineProps<{
    section: AnalysisSection;
    externalLinksEnabled: boolean;
}>();
const { tx } = useLocale();

const navigation = computed(() => [
    { key: 'overview', tr: 'Build özeti', en: 'Build overview', count: null },
    {
        key: 'findings',
        tr: 'Bulgular',
        en: 'Findings',
        count: demoFindings.length,
    },
    {
        key: 'upgrades',
        tr: 'Yükseltme planı',
        en: 'Upgrade plan',
        count: demoUpgrades.length,
    },
    {
        key: 'trade',
        tr: 'Trade tarifleri',
        en: 'Trade recipes',
        count: demoRecipes.length,
    },
    {
        key: 'provenance',
        tr: 'Kaynak ve policy',
        en: 'Source and policy',
        count: null,
    },
    { key: 'states', tr: 'Durum örnekleri', en: 'State examples', count: null },
]);

const pageTitle = computed(() => {
    const item = navigation.value.find((entry) => entry.key === props.section);

    return item ? tx({ tr: item.tr, en: item.en }) : 'Lootwright';
});

const severityGroups = computed(() => [
    {
        key: 'warning',
        title: tx({ tr: 'Uyarılar', en: 'Warnings' }),
        items: demoFindings.filter((finding) => finding.severity === 'warning'),
    },
    {
        key: 'opportunity',
        title: tx({ tr: 'Fırsatlar', en: 'Opportunities' }),
        items: demoFindings.filter(
            (finding) => finding.severity === 'opportunity',
        ),
    },
    {
        key: 'information',
        title: tx({ tr: 'Bilgi', en: 'Information' }),
        items: demoFindings.filter(
            (finding) => finding.severity === 'information',
        ),
    },
]);
</script>

<template>
    <Head :title="pageTitle" />
    <AppShell current="demo" :contained="false">
        <div class="workspace-shell">
            <aside class="workspace-sidebar">
                <div class="build-identity">
                    <EditionBadge edition="poe1" compact />
                    <span class="demo-chip">FIXTURE DEMO</span>
                    <h2>{{ demoBuild.buildName }}</h2>
                    <p>{{ demoBuild.className }} · Lv {{ demoBuild.level }}</p>
                </div>
                <nav
                    :aria-label="
                        tx({ tr: 'Analiz bölümleri', en: 'Analysis sections' })
                    "
                >
                    <a
                        v-for="item in navigation"
                        :key="item.key"
                        :href="`/analyses/demo/${item.key}`"
                        :aria-current="
                            section === item.key ? 'page' : undefined
                        "
                    >
                        <span>{{ tx({ tr: item.tr, en: item.en }) }}</span>
                        <small v-if="item.count !== null">{{
                            item.count
                        }}</small>
                    </a>
                </nav>
                <div class="sidebar-meta">
                    <span>ANALYSIS V1</span>
                    <span>RULESET {{ demoBuild.ruleset }}</span>
                    <a href="/analyses/demo/provenance">{{
                        tx({ tr: 'Kaynağı doğrula', en: 'Verify source' })
                    }}</a>
                </div>
            </aside>

            <div class="workspace-content">
                <header class="workspace-heading">
                    <div>
                        <p class="kicker">{{ pageTitle }}</p>
                        <h1>{{ demoBuild.buildName }}</h1>
                        <p>
                            {{ demoBuild.className }} · {{ demoBuild.league }} ·
                            {{ demoBuild.patch }}
                        </p>
                    </div>
                    <div class="workspace-status">
                        <span class="status-chip is-confirmed">{{
                            tx({
                                tr: 'Deterministik tamamlandı',
                                en: 'Deterministic complete',
                            })
                        }}</span>
                        <span class="status-chip is-neutral">{{
                            tx({
                                tr: 'AI açıklaması kapalı',
                                en: 'AI explanation off',
                            })
                        }}</span>
                    </div>
                </header>

                <StatusBanner
                    tone="info"
                    :title="tx({ tr: 'Fixture verisi', en: 'Fixture data' })"
                    :body="
                        tx({
                            tr: 'Bu çalışma alanındaki tüm build gerçekleri ve sonuçlar test fixture verisidir; güncel oyun veya piyasa iddiası değildir.',
                            en: 'All build facts and results in this workspace are test fixtures, not claims about the current game or market.',
                        })
                    "
                />

                <template v-if="section === 'overview'">
                    <section class="overview-strip" aria-label="Build summary">
                        <div>
                            <span>{{
                                tx({ tr: 'Edition', en: 'Edition' })
                            }}</span
                            ><strong>PoE 1</strong>
                        </div>
                        <div>
                            <span>{{
                                tx({
                                    tr: 'Analiz sürümü',
                                    en: 'Analysis version',
                                })
                            }}</span
                            ><strong>V1</strong>
                        </div>
                        <div>
                            <span>{{
                                tx({ tr: 'Bulgular', en: 'Findings' })
                            }}</span
                            ><strong>{{ demoFindings.length }}</strong>
                        </div>
                        <div>
                            <span>{{
                                tx({ tr: 'Yükseltmeler', en: 'Upgrades' })
                            }}</span
                            ><strong>{{ demoUpgrades.length }}</strong>
                        </div>
                        <ConfidenceMeter
                            :value="demoBuild.confidence"
                            :label="
                                tx({
                                    tr: 'Genel güven',
                                    en: 'Overall confidence',
                                })
                            "
                        />
                    </section>

                    <div class="overview-columns">
                        <section aria-labelledby="skills-title">
                            <div class="section-title-row">
                                <div>
                                    <p class="kicker">BUILD FACTS</p>
                                    <h2 id="skills-title">
                                        {{
                                            tx({
                                                tr: 'Beceriler',
                                                en: 'Skills',
                                            })
                                        }}
                                    </h2>
                                </div>
                                <span
                                    >{{ demoBuild.skills.length }}
                                    {{
                                        tx({
                                            tr: 'kanıtlı grup',
                                            en: 'proven groups',
                                        })
                                    }}</span
                                >
                            </div>
                            <ul class="fact-list">
                                <li
                                    v-for="skill in demoBuild.skills"
                                    :key="skill.name"
                                >
                                    <div>
                                        <strong>{{ skill.name }}</strong
                                        ><span>{{ skill.role }}</span>
                                    </div>
                                    <ConfidenceMeter
                                        :value="skill.confidence"
                                    />
                                </li>
                            </ul>
                        </section>

                        <section aria-labelledby="defences-title">
                            <div class="section-title-row">
                                <div>
                                    <p class="kicker">NORMALIZED VALUES</p>
                                    <h2 id="defences-title">
                                        {{
                                            tx({
                                                tr: 'Savunmalar',
                                                en: 'Defences',
                                            })
                                        }}
                                    </h2>
                                </div>
                            </div>
                            <dl class="defence-list">
                                <div
                                    v-for="defence in demoBuild.defenses"
                                    :key="defence.label"
                                >
                                    <dt>{{ defence.label }}</dt>
                                    <dd>{{ defence.value }}</dd>
                                    <span
                                        class="status-dot"
                                        :class="`is-${defence.status}`"
                                        ><span class="sr-only">{{
                                            defence.status
                                        }}</span></span
                                    >
                                </div>
                            </dl>
                        </section>
                    </div>

                    <section
                        class="equipment-section"
                        aria-labelledby="equipment-title"
                    >
                        <div class="section-title-row">
                            <div>
                                <p class="kicker">EQUIPMENT MAP</p>
                                <h2 id="equipment-title">
                                    {{
                                        tx({
                                            tr: 'Ekipman slotları',
                                            en: 'Equipment slots',
                                        })
                                    }}
                                </h2>
                            </div>
                            <p>
                                {{
                                    tx({
                                        tr: 'Renk tek başına durum taşımaz.',
                                        en: 'Color never carries state alone.',
                                    })
                                }}
                            </p>
                        </div>
                        <ul class="equipment-grid">
                            <li
                                v-for="item in demoBuild.itemSlots"
                                :key="item.slot"
                                :class="`is-${item.state}`"
                            >
                                <span>{{ item.slot }}</span>
                                <strong>{{ item.label }}</strong>
                                <small>{{ item.state }}</small>
                            </li>
                        </ul>
                    </section>

                    <section class="ai-wording-note">
                        <div class="ai-label">AI WORDING</div>
                        <div>
                            <h2>
                                {{
                                    tx({
                                        tr: 'AI açıklaması kullanılmadı',
                                        en: 'No AI wording was used',
                                    })
                                }}
                            </h2>
                            <p>
                                {{
                                    tx({
                                        tr: 'Bu sayfadaki metin yerel şablonlardan gelir. Hesaplamalar her durumda deterministik engine tarafından yapılır.',
                                        en: 'Text on this page comes from local templates. Calculations are always produced by the deterministic engine.',
                                    })
                                }}
                            </p>
                        </div>
                    </section>
                </template>

                <template v-else-if="section === 'findings'">
                    <section class="results-intro">
                        <div>
                            <p class="kicker">DETERMINISTIC FINDINGS</p>
                            <h2>
                                {{
                                    tx({
                                        tr: 'Önem derecesine göre bulgular',
                                        en: 'Findings by severity',
                                    })
                                }}
                            </h2>
                            <p>
                                {{
                                    tx({
                                        tr: 'Her bulgu “Neden?” altında girdi, kural, kaynak ve sınırlama zincirini gösterir.',
                                        en: 'Every finding exposes its input, rule, source, and limitation chain under “Why?”.',
                                    })
                                }}
                            </p>
                        </div>
                        <dl class="result-counts">
                            <div>
                                <dt>Warning</dt>
                                <dd>1</dd>
                            </div>
                            <div>
                                <dt>Opportunity</dt>
                                <dd>1</dd>
                            </div>
                            <div>
                                <dt>Information</dt>
                                <dd>1</dd>
                            </div>
                        </dl>
                    </section>
                    <section
                        v-for="group in severityGroups"
                        :key="group.key"
                        class="finding-group"
                        :aria-labelledby="`group-${group.key}`"
                    >
                        <header>
                            <h2 :id="`group-${group.key}`">
                                {{ group.title }}
                            </h2>
                            <span>{{ group.items.length }}</span>
                        </header>
                        <FindingRow
                            v-for="finding in group.items"
                            :key="finding.code"
                            :finding="finding"
                        />
                    </section>
                </template>

                <template v-else-if="section === 'upgrades'">
                    <section class="results-intro">
                        <div>
                            <p class="kicker">PRIORITIZED PLAN</p>
                            <h2>
                                {{
                                    tx({
                                        tr: 'Yükseltme sırası',
                                        en: 'Upgrade order',
                                    })
                                }}
                            </h2>
                            <p>
                                {{
                                    tx({
                                        tr: 'Sıralama fixture hedefi, kanıtlanmış bulgular ve slot bağımlılıklarıyla belirlenir. Canlı fiyat kullanılmaz.',
                                        en: 'Ranking uses the fixture goal, proven findings, and slot dependencies. No live price is used.',
                                    })
                                }}
                            </p>
                        </div>
                    </section>
                    <div class="upgrade-list">
                        <UpgradeRow
                            v-for="upgrade in demoUpgrades"
                            :key="upgrade.code"
                            :upgrade="upgrade"
                        />
                    </div>
                    <StatusBanner
                        tone="neutral"
                        :title="
                            tx({
                                tr: 'Maliyet bilinmiyor',
                                en: 'Cost is unknown',
                            })
                        "
                        :body="
                            tx({
                                tr: 'Bütçe bantları yalnızca filtre gevşetme sırasını etkiler; ürün fiyat veya bulunabilirlik tahmini yapmaz.',
                                en: 'Budget bands affect only filter-relaxation order; the product makes no price or availability estimate.',
                            })
                        "
                    />
                </template>

                <template v-else-if="section === 'trade'">
                    <StatusBanner
                        tone="warning"
                        :title="
                            tx({
                                tr: 'Canlı arama değil',
                                en: 'Not a live search',
                            })
                        "
                        :body="
                            tx({
                                tr: 'Exact filter etiketleri fixture ruleset sözlüğündendir. İlan, fiyat, Trade ID veya encoded arama URL’si yoktur.',
                                en: 'Exact filter labels come from the fixture ruleset vocabulary. There are no listings, prices, Trade IDs, or encoded search URLs.',
                            })
                        "
                    />
                    <ManualTradeRecipeCard
                        v-for="recipe in demoRecipes"
                        :key="recipe.slot"
                        :recipe="recipe"
                        :external-link-enabled="externalLinksEnabled"
                    />
                </template>

                <template v-else-if="section === 'provenance'">
                    <section class="provenance-header">
                        <div>
                            <p class="kicker">IMMUTABLE CONTEXT</p>
                            <h2>
                                {{
                                    tx({
                                        tr: 'Kaynak ve Policy Gate',
                                        en: 'Source and Policy Gate',
                                    })
                                }}
                            </h2>
                        </div>
                        <span class="status-chip is-confirmed">{{
                            tx({
                                tr: 'Checksum doğrulandı',
                                en: 'Checksum verified',
                            })
                        }}</span>
                    </section>
                    <dl class="provenance-ledger">
                        <div>
                            <dt>
                                {{
                                    tx({
                                        tr: 'Oyun edition',
                                        en: 'Game edition',
                                    })
                                }}
                            </dt>
                            <dd><EditionBadge edition="poe1" compact /></dd>
                        </div>
                        <div>
                            <dt>
                                {{
                                    tx({
                                        tr: 'Analiz sürümü',
                                        en: 'Analysis version',
                                    })
                                }}
                            </dt>
                            <dd><code>1</code></dd>
                        </div>
                        <div>
                            <dt>Adapter</dt>
                            <dd><code>pob1-fixture</code></dd>
                        </div>
                        <div>
                            <dt>Parser</dt>
                            <dd><code>1.0.0</code></dd>
                        </div>
                        <div>
                            <dt>Ruleset ID</dt>
                            <dd>
                                <code
                                    >01890f47-0f7d-7a2b-ac3d-1234567890ab</code
                                >
                            </dd>
                        </div>
                        <div>
                            <dt>Ruleset version</dt>
                            <dd><code>1.4.2-fixture</code></dd>
                        </div>
                        <div>
                            <dt>Ruleset SHA-256</dt>
                            <dd>
                                <code
                                    >bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb</code
                                >
                            </dd>
                        </div>
                        <div>
                            <dt>Source</dt>
                            <dd><code>LOOTWRIGHT-001 / fixture-1</code></dd>
                        </div>
                    </dl>
                    <section
                        class="policy-table"
                        aria-labelledby="policy-title"
                    >
                        <div class="section-title-row">
                            <div>
                                <p class="kicker">POLICY DECISIONS</p>
                                <h2 id="policy-title">
                                    {{
                                        tx({
                                            tr: 'Bu analizdeki yetkiler',
                                            en: 'Capabilities for this analysis',
                                        })
                                    }}
                                </h2>
                            </div>
                        </div>
                        <div class="table-scroll" tabindex="0">
                            <table>
                                <thead>
                                    <tr>
                                        <th>
                                            {{
                                                tx({
                                                    tr: 'İşlem',
                                                    en: 'Operation',
                                                })
                                            }}
                                        </th>
                                        <th>
                                            {{
                                                tx({
                                                    tr: 'Karar',
                                                    en: 'Decision',
                                                })
                                            }}
                                        </th>
                                        <th>
                                            {{
                                                tx({
                                                    tr: 'Neden',
                                                    en: 'Reason',
                                                })
                                            }}
                                        </th>
                                        <th>Policy</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <code
                                                >ruleset.deterministic_analysis</code
                                            >
                                        </td>
                                        <td>
                                            <span
                                                class="status-chip is-confirmed"
                                                >ALLOW</span
                                            >
                                        </td>
                                        <td>fixture evidence active</td>
                                        <td>1.0.0</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <code
                                                >trade.manual_recipe.generate</code
                                            >
                                        </td>
                                        <td>
                                            <span
                                                class="status-chip is-confirmed"
                                                >ALLOW</span
                                            >
                                        </td>
                                        <td>manual actions only</td>
                                        <td>1.0.0</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <code
                                                >trade.live_listing.fetch</code
                                            >
                                        </td>
                                        <td>
                                            <span class="status-chip is-danger"
                                                >DENY</span
                                            >
                                        </td>
                                        <td>explicit denial</td>
                                        <td>1.0.0</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <code>ai.explanation.generate</code>
                                        </td>
                                        <td>
                                            <span class="status-chip is-warning"
                                                >REVIEW</span
                                            >
                                        </td>
                                        <td>provider disabled</td>
                                        <td>1.0.0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                    <div class="page-actions">
                        <a
                            class="button is-secondary"
                            href="/api/analyses/00000000-0000-7000-8000-000000000000/export"
                            aria-disabled="true"
                            @click.prevent
                            >{{
                                tx({
                                    tr: 'Demo export devre dışı',
                                    en: 'Demo export disabled',
                                })
                            }}</a
                        >
                    </div>
                </template>

                <template v-else>
                    <section class="states-intro">
                        <p class="kicker">OPERATIONAL STATES</p>
                        <h2>
                            {{
                                tx({
                                    tr: 'Her durum için açık bir sonraki adım',
                                    en: 'A clear next step for every state',
                                })
                            }}
                        </h2>
                        <p>
                            {{
                                tx({
                                    tr: 'Bu fixture laboratuvarı üretim ekranlarının yükleme, boş, kısmi ve hata sınırlarını gösterir.',
                                    en: 'This fixture lab shows the loading, empty, partial, and error boundaries used by production screens.',
                                })
                            }}
                        </p>
                    </section>
                    <div class="state-showcase">
                        <section class="state-sample">
                            <span class="state-name">LOADING</span>
                            <div class="skeleton-lines" aria-label="Loading">
                                <span></span><span></span><span></span>
                            </div>
                        </section>
                        <StatusBanner
                            tone="neutral"
                            :title="
                                tx({
                                    tr: 'Henüz bulgu yok',
                                    en: 'No findings yet',
                                })
                            "
                            :body="
                                tx({
                                    tr: 'Parser tamamlandığında bu alan kanıt zincirlerini gösterecek.',
                                    en: 'This area will show evidence chains when parsing completes.',
                                })
                            "
                        />
                        <StatusBanner
                            tone="warning"
                            :title="
                                tx({ tr: 'Kısmi sonuç', en: 'Partial result' })
                            "
                            :body="
                                tx({
                                    tr: 'Bir support çözümlenemedi; doğrulanmış savunma bulguları kullanılabilir.',
                                    en: 'One support is unresolved; proven defence findings remain available.',
                                })
                            "
                        />
                        <StatusBanner
                            tone="danger"
                            :title="
                                tx({
                                    tr: 'Ruleset eskidi',
                                    en: 'Ruleset is stale',
                                })
                            "
                            :body="
                                tx({
                                    tr: 'Seçilen checksum artık aktif kimlikle eşleşmiyor. Farklı bir sürüme sessiz geçiş yapılmadı.',
                                    en: 'The selected checksum no longer matches the active identity. No silent version fallback occurred.',
                                })
                            "
                            action-label="Yeni analiz"
                            action-href="/analyses/new"
                        />
                        <StatusBanner
                            tone="neutral"
                            :title="tx({ tr: 'AI kapalı', en: 'AI disabled' })"
                            :body="
                                tx({
                                    tr: 'Deterministik sonuç kullanılabilir; açıklama yerel şablonla gösteriliyor.',
                                    en: 'The deterministic result remains available with local template wording.',
                                })
                            "
                        />
                        <StatusBanner
                            tone="danger"
                            :title="
                                tx({
                                    tr: 'Policy Gate reddetti',
                                    en: 'Policy Gate denied',
                                })
                            "
                            :body="
                                tx({
                                    tr: 'İstenen capability için aktif allow kaydı yok. Harici çağrı yapılmadı.',
                                    en: 'No active allow record exists for the capability. No external call was made.',
                                })
                            "
                        />
                        <StatusBanner
                            tone="danger"
                            :title="
                                tx({
                                    tr: 'Analiz tamamlanamadı',
                                    en: 'Analysis could not complete',
                                })
                            "
                            :body="
                                tx({
                                    tr: 'İstek kimliğini koru ve tekrar dene. Ham girdiyi loglara yapıştırma.',
                                    en: 'Keep the request ID and retry. Do not paste raw input into logs.',
                                })
                            "
                        />
                    </div>
                </template>
            </div>
        </div>
    </AppShell>
</template>

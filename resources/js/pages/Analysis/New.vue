<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import AppShell from '@/components/app/AppShell.vue';
import EditionBadge from '@/components/app/EditionBadge.vue';
import StatusBanner from '@/components/app/StatusBanner.vue';
import { useLocale } from '@/composables/useLocale';
import type { GameEdition } from '@/types/analysis-ui';

type SourceType = 'pob' | 'item';

const { tx } = useLocale();
const currentStep = ref(1);
const errors = ref<string[]>([]);
const submitted = ref(false);

const form = reactive({
    edition: 'poe1' as GameEdition,
    sourceType: 'pob' as SourceType,
    artifact: '',
    className: '',
    buildName: '',
    goal: '',
    content: 'mapping',
    budgetAmount: '',
    budgetCurrency: 'CHAOS',
    storeNormalized: true,
    aiExplanation: false,
    aiCache: false,
    consent: false,
});

const steps = computed(() => [
    tx({ tr: 'Oyun ve girdi', en: 'Game and input' }),
    tx({ tr: 'Build hedefi', en: 'Build goal' }),
    tx({ tr: 'Gizlilik', en: 'Privacy' }),
    tx({ tr: 'Doğrula', en: 'Validate' }),
]);

const mismatchDetected = computed(
    () =>
        (form.edition === 'poe1' &&
            /PathOfBuilding2|poe2/i.test(form.artifact)) ||
        (form.edition === 'poe2' &&
            /PathOfBuilding(?!2)|poe1/i.test(form.artifact)),
);

function validateStep(step: number): boolean {
    const nextErrors: string[] = [];

    if (step === 1) {
        if (form.artifact.trim().length < 12) {
            nextErrors.push(
                tx({
                    tr: 'Lütfen en az 12 karakterlik bir build girdisi yapıştır.',
                    en: 'Paste a build input with at least 12 characters.',
                }),
            );
        }

        if (mismatchDetected.value) {
            nextErrors.push(
                tx({
                    tr: 'Seçilen edition ile girdideki edition işareti çelişiyor.',
                    en: 'The selected edition conflicts with the edition marker in the input.',
                }),
            );
        }
    }

    if (step === 2 && form.goal.trim().length < 12) {
        nextErrors.push(
            tx({
                tr: 'Hedefini en az 12 karakterle açıkla.',
                en: 'Describe your goal with at least 12 characters.',
            }),
        );
    }

    if (step === 3 && !form.consent) {
        nextErrors.push(
            tx({
                tr: 'İşleme ve kısa süreli saklama açıklamasını onayla.',
                en: 'Confirm the processing and short-term storage notice.',
            }),
        );
    }

    errors.value = nextErrors;

    return nextErrors.length === 0;
}

function next(): void {
    if (!validateStep(currentStep.value)) {
        return;
    }

    currentStep.value = Math.min(4, currentStep.value + 1);
    document.querySelector<HTMLElement>('.wizard-panel')?.focus();
}

function previous(): void {
    errors.value = [];
    currentStep.value = Math.max(1, currentStep.value - 1);
}

function submitPreview(): void {
    if (!validateStep(3)) {
        currentStep.value = 3;

        return;
    }

    submitted.value = true;
}
</script>

<template>
    <Head :title="tx({ tr: 'Yeni analiz', en: 'New analysis' })" />

    <AppShell current="new">
        <header class="page-heading is-split">
            <div>
                <p class="kicker">
                    {{ tx({ tr: 'Yeni çalışma', en: 'New workspace' }) }}
                </p>
                <h1>
                    {{
                        tx({
                            tr: 'Build girdini hazırla',
                            en: 'Prepare your build input',
                        })
                    }}
                </h1>
                <p>
                    {{
                        tx({
                            tr: 'Bu önizleme akışı gerçek bir analiz çağrısı yapmaz. Girdi yalnızca tarayıcıdaki form durumunda tutulur.',
                            en: 'This preview flow makes no real analysis call. Input remains only in the browser form state.',
                        })
                    }}
                </p>
            </div>
            <EditionBadge :edition="form.edition" />
        </header>

        <ol
            class="wizard-steps"
            :aria-label="tx({ tr: 'Analiz adımları', en: 'Analysis steps' })"
        >
            <li
                v-for="(step, index) in steps"
                :key="step"
                :class="{
                    'is-current': currentStep === index + 1,
                    'is-complete': currentStep > index + 1,
                }"
                :aria-current="currentStep === index + 1 ? 'step' : undefined"
            >
                <span>{{ index + 1 }}</span>
                {{ step }}
            </li>
        </ol>

        <div
            v-if="errors.length"
            class="form-errors"
            role="alert"
            aria-live="assertive"
        >
            <strong>{{
                tx({
                    tr: 'Devam etmeden önce düzelt:',
                    en: 'Fix before continuing:',
                })
            }}</strong>
            <ul>
                <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
        </div>

        <section
            v-if="!submitted"
            class="wizard-panel"
            tabindex="-1"
            aria-live="polite"
        >
            <fieldset v-if="currentStep === 1">
                <legend>
                    {{
                        tx({
                            tr: 'Edition ve girdi türü',
                            en: 'Edition and input type',
                        })
                    }}
                </legend>
                <div class="edition-choice-grid">
                    <label :class="{ 'is-selected': form.edition === 'poe1' }">
                        <input
                            v-model="form.edition"
                            type="radio"
                            value="poe1"
                        />
                        <EditionBadge edition="poe1" />
                        <strong>{{
                            tx({ tr: 'PoE1 analizi', en: 'PoE1 analysis' })
                        }}</strong>
                        <span>{{
                            tx({
                                tr: 'MVP deterministik akış',
                                en: 'MVP deterministic workflow',
                            })
                        }}</span>
                    </label>
                    <label :class="{ 'is-selected': form.edition === 'poe2' }">
                        <input
                            v-model="form.edition"
                            type="radio"
                            value="poe2"
                        />
                        <EditionBadge edition="poe2" />
                        <strong>{{
                            tx({
                                tr: 'PoE2 format inceleme',
                                en: 'PoE2 format review',
                            })
                        }}</strong>
                        <span>{{
                            tx({
                                tr: 'Analiz henüz aktif değil',
                                en: 'Analysis is not active yet',
                            })
                        }}</span>
                    </label>
                </div>

                <div
                    class="segmented-control"
                    role="group"
                    :aria-label="tx({ tr: 'Girdi türü', en: 'Input type' })"
                >
                    <button
                        type="button"
                        :aria-pressed="form.sourceType === 'pob'"
                        @click="form.sourceType = 'pob'"
                    >
                        {{ form.edition === 'poe2' ? 'PoB2' : 'PoB' }}
                    </button>
                    <button
                        type="button"
                        :aria-pressed="form.sourceType === 'item'"
                        @click="form.sourceType = 'item'"
                    >
                        {{ tx({ tr: 'Eşya metni', en: 'Item text' }) }}
                    </button>
                </div>

                <label class="field">
                    <span>{{
                        form.sourceType === 'pob'
                            ? tx({
                                  tr: 'Paylaşım kodu, PoBB.in bağlantısı veya XML',
                                  en: 'Share code, PoBB.in link, or XML',
                              })
                            : tx({
                                  tr: 'Kopyalanmış eşya metni',
                                  en: 'Copied item text',
                              })
                    }}</span>
                    <textarea
                        v-model="form.artifact"
                        rows="9"
                        maxlength="1048576"
                        spellcheck="false"
                        :placeholder="
                            form.sourceType === 'pob'
                                ? 'eNrt... / https://pobb.in/... / <PathOfBuilding>'
                                : 'Rarity: Rare…'
                        "
                    ></textarea>
                    <small
                        >{{ form.artifact.length.toLocaleString() }} /
                        1,048,576</small
                    >
                </label>

                <StatusBanner
                    v-if="form.edition === 'poe2'"
                    tone="warning"
                    :title="
                        tx({
                            tr: 'PoE2 analizi kapalı',
                            en: 'PoE2 analysis is inactive',
                        })
                    "
                    :body="
                        tx({
                            tr: 'PoB2 formatı incelenebilir; ruleset, bulgu, yükseltme ve Trade tarifi üretilmez.',
                            en: 'PoB2 format can be reviewed, but no ruleset, finding, upgrade, or Trade recipe is produced.',
                        })
                    "
                />
            </fieldset>

            <fieldset v-else-if="currentStep === 2">
                <legend>
                    {{
                        tx({
                            tr: 'Build ve hedef bağlamı',
                            en: 'Build and goal context',
                        })
                    }}
                </legend>
                <div class="form-grid two-columns">
                    <label class="field">
                        <span>{{ tx({ tr: 'Sınıf', en: 'Class' }) }}</span>
                        <input
                            v-model="form.className"
                            type="text"
                            maxlength="80"
                            placeholder="Elementalist"
                        />
                    </label>
                    <label class="field">
                        <span>{{
                            tx({ tr: 'Build adı', en: 'Build name' })
                        }}</span>
                        <input
                            v-model="form.buildName"
                            type="text"
                            maxlength="120"
                            placeholder="Arc Ignite"
                        />
                    </label>
                </div>
                <label class="field">
                    <span>{{
                        tx({
                            tr: 'Ne elde etmek istiyorsun?',
                            en: 'What are you trying to achieve?',
                        })
                    }}</span>
                    <textarea
                        v-model="form.goal"
                        rows="5"
                        maxlength="500"
                        :placeholder="
                            tx({
                                tr: 'Haritalamada daha dayanıklı olmak istiyorum; ana beceriyi değiştirme.',
                                en: 'I want more mapping durability without changing the main skill.',
                            })
                        "
                    ></textarea>
                    <small>{{
                        tx({
                            tr: 'Basit hedefler önce yerel parser ile değerlendirilir.',
                            en: 'Simple goals are evaluated by the local parser first.',
                        })
                    }}</small>
                </label>
                <div class="form-grid three-columns">
                    <label class="field">
                        <span>{{
                            tx({ tr: 'İçerik hedefi', en: 'Content goal' })
                        }}</span>
                        <select v-model="form.content">
                            <option value="mapping">
                                {{ tx({ tr: 'Haritalama', en: 'Mapping' }) }}
                            </option>
                            <option value="bossing">
                                {{ tx({ tr: 'Boss', en: 'Bossing' }) }}
                            </option>
                            <option value="balanced">
                                {{ tx({ tr: 'Dengeli', en: 'Balanced' }) }}
                            </option>
                        </select>
                    </label>
                    <label class="field">
                        <span>{{
                            tx({ tr: 'Bütçe miktarı', en: 'Budget amount' })
                        }}</span>
                        <input
                            v-model="form.budgetAmount"
                            inputmode="decimal"
                            pattern="[0-9.]*"
                            placeholder="10"
                        />
                    </label>
                    <label class="field">
                        <span>{{
                            tx({ tr: 'Bütçe birimi', en: 'Budget currency' })
                        }}</span>
                        <select v-model="form.budgetCurrency">
                            <option value="CHAOS">CHAOS</option>
                            <option value="DIVINE">DIVINE</option>
                        </select>
                    </label>
                </div>
                <p class="form-note">
                    {{
                        tx({
                            tr: 'Bütçe yalnızca gevşetme bağlamıdır. Lootwright fiyat tahmini yapmaz.',
                            en: 'Budget is only relaxation context. Lootwright does not estimate prices.',
                        })
                    }}
                </p>
            </fieldset>

            <fieldset v-else-if="currentStep === 3">
                <legend>
                    {{
                        tx({
                            tr: 'Gizlilik ve işleme tercihleri',
                            en: 'Privacy and processing choices',
                        })
                    }}
                </legend>
                <div class="choice-list">
                    <label>
                        <input v-model="form.storeNormalized" type="checkbox" />
                        <span>
                            <strong>{{
                                tx({
                                    tr: 'Normalize sonucu sakla',
                                    en: 'Store normalized result',
                                })
                            }}</strong>
                            <small>{{
                                tx({
                                    tr: 'Ham girdi parse sonrası silinir; normalize snapshot şifreli tutulur.',
                                    en: 'Raw input is deleted after parsing; the normalized snapshot is encrypted.',
                                })
                            }}</small>
                        </span>
                    </label>
                    <label>
                        <input v-model="form.aiExplanation" type="checkbox" />
                        <span>
                            <strong>{{
                                tx({
                                    tr: 'İsteğe bağlı AI açıklaması',
                                    en: 'Optional AI explanation',
                                })
                            }}</strong>
                            <small>{{
                                tx({
                                    tr: 'AI hesap yapmaz ve sonuçları değiştiremez. Sağlayıcı şu anda policy nedeniyle kapalıdır.',
                                    en: 'AI does not calculate or change results. The provider is currently disabled by policy.',
                                })
                            }}</small>
                        </span>
                    </label>
                    <label :class="{ 'is-disabled': !form.aiExplanation }">
                        <input
                            v-model="form.aiCache"
                            type="checkbox"
                            :disabled="!form.aiExplanation"
                        />
                        <span>
                            <strong>{{
                                tx({
                                    tr: 'Doğrulanmış açıklama cache izni',
                                    en: 'Validated explanation cache permission',
                                })
                            }}</strong>
                            <small>{{
                                tx({
                                    tr: 'Yalnızca şema doğrulamalı yapılandırılmış çıktı; ham prompt saklanmaz.',
                                    en: 'Schema-validated structured output only; raw prompts are not stored.',
                                })
                            }}</small>
                        </span>
                    </label>
                    <label class="consent-choice">
                        <input v-model="form.consent" type="checkbox" />
                        <span>
                            <strong>{{
                                tx({
                                    tr: 'İşleme açıklamasını okudum',
                                    en: 'I have read the processing notice',
                                })
                            }}</strong>
                            <small>{{
                                tx({
                                    tr: 'Girdiyi isteyerek gönderiyorum ve silme kontrollerini anlıyorum.',
                                    en: 'I submit this input deliberately and understand the deletion controls.',
                                })
                            }}</small>
                        </span>
                    </label>
                </div>
                <a class="text-link" href="/privacy">{{
                    tx({
                        tr: 'Gizlilik ve silme ayrıntıları',
                        en: 'Privacy and deletion details',
                    })
                }}</a>
            </fieldset>

            <fieldset v-else>
                <legend>
                    {{
                        tx({
                            tr: 'Gönderim öncesi doğrulama',
                            en: 'Pre-submission validation',
                        })
                    }}
                </legend>
                <dl class="review-grid">
                    <div>
                        <dt>Edition</dt>
                        <dd>
                            <EditionBadge :edition="form.edition" compact />
                        </dd>
                    </div>
                    <div>
                        <dt>{{ tx({ tr: 'Girdi', en: 'Input' }) }}</dt>
                        <dd>
                            {{
                                form.sourceType === 'pob'
                                    ? 'PoB / PoB2'
                                    : tx({ tr: 'Eşya metni', en: 'Item text' })
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>{{ tx({ tr: 'Karakter', en: 'Character' }) }}</dt>
                        <dd>
                            {{
                                form.className ||
                                tx({ tr: 'Belirtilmedi', en: 'Not provided' })
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>{{ tx({ tr: 'İçerik', en: 'Content' }) }}</dt>
                        <dd>{{ form.content }}</dd>
                    </div>
                    <div>
                        <dt>{{ tx({ tr: 'Bütçe', en: 'Budget' }) }}</dt>
                        <dd>
                            {{
                                form.budgetAmount
                                    ? `${form.budgetAmount} ${form.budgetCurrency}`
                                    : tx({
                                          tr: 'Belirtilmedi',
                                          en: 'Not provided',
                                      })
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>AI</dt>
                        <dd>
                            {{
                                form.aiExplanation
                                    ? tx({
                                          tr: 'İstek var, policy kapalı',
                                          en: 'Opted in, policy disabled',
                                      })
                                    : tx({ tr: 'Kapalı', en: 'Off' })
                            }}
                        </dd>
                    </div>
                </dl>
                <div class="validation-ledger">
                    <p>
                        <span aria-hidden="true">✓</span
                        >{{
                            tx({
                                tr: 'Girdi boyutu sınır içinde',
                                en: 'Input size is within limit',
                            })
                        }}
                    </p>
                    <p>
                        <span aria-hidden="true">✓</span
                        >{{
                            tx({
                                tr: 'Edition seçimi açık',
                                en: 'Edition selection is explicit',
                            })
                        }}
                    </p>
                    <p>
                        <span aria-hidden="true">✓</span
                        >{{
                            tx({
                                tr: 'Otomatik Trade veya harici fetch yok',
                                en: 'No automated Trade or external fetch',
                            })
                        }}
                    </p>
                </div>
            </fieldset>

            <div class="wizard-actions">
                <button
                    v-if="currentStep > 1"
                    type="button"
                    class="button is-secondary"
                    @click="previous"
                >
                    {{ tx({ tr: 'Geri', en: 'Back' }) }}
                </button>
                <button
                    v-if="currentStep < 4"
                    type="button"
                    class="button is-primary"
                    @click="next"
                >
                    {{ tx({ tr: 'Devam', en: 'Continue' }) }}
                </button>
                <button
                    v-else
                    type="button"
                    class="button is-primary"
                    @click="submitPreview"
                >
                    {{
                        tx({
                            tr: 'Fixture incelemesini hazırla',
                            en: 'Prepare fixture review',
                        })
                    }}
                </button>
            </div>
        </section>

        <section
            v-else
            class="submission-success"
            role="status"
            aria-live="polite"
        >
            <span class="success-mark" aria-hidden="true">✓</span>
            <div>
                <p class="kicker">
                    {{
                        tx({
                            tr: 'Yerel doğrulama tamamlandı',
                            en: 'Local validation complete',
                        })
                    }}
                </p>
                <h2>
                    {{
                        tx({
                            tr: 'Fixture import incelemesi hazır',
                            en: 'Fixture import review is ready',
                        })
                    }}
                </h2>
                <p>
                    {{
                        tx({
                            tr: 'Bu demo hiçbir girdi göndermedi ve kalıcı kayıt oluşturmadı.',
                            en: 'This demo sent no input and created no persistent record.',
                        })
                    }}
                </p>
                <a class="button is-primary" href="/analyses/demo/import">
                    {{
                        tx({
                            tr: 'Import incelemesine geç',
                            en: 'Continue to import review',
                        })
                    }}
                </a>
            </div>
        </section>
    </AppShell>
</template>

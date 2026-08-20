<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import AppShell from '@/components/app/AppShell.vue';

type Flow = 'plan' | 'analyse' | 'upgrade';
type GameEdition = 'poe1' | 'poe2';
type Ascendancy = {
    id: string;
    name: string;
    type?: 'regular' | 'alternate' | 'secondary';
    availability?: string;
    requires_base_ascendancy?: string | null;
};
type CharacterClass = {
    id: string;
    name: string;
    availability?: string;
    ascendancies: Ascendancy[];
};
type Catalog = {
    game: GameEdition;
    patch: string;
    version?: string;
    early_access?: boolean;
    verified_at: string;
    source: string;
    classes: CharacterClass[];
};

const page = usePage<{
    auth: { user: { email_verified_at: string | null } | null };
}>();
const catalog = ref<Catalog | null>(null);
const catalogLoading = ref(false);
const currentStep = ref(1);
const heading = ref<HTMLHeadingElement>();
const errors = ref<Record<string, string>>({});
const submitting = ref(false);
const dirty = ref(false);
const importConflict = ref('');
const resultId = ref('');
const secondarySkillText = ref('');
const preservedItemText = ref('');
const replaceableSlotText = ref('');
const steps = [
    'Başlangıç',
    'Karakter',
    'Build bilgileri',
    'Hedefler',
    'Bütçe',
    'Gizlilik',
    'Kontrol',
];
const form = reactive({
    flow: 'plan' as Flow,
    game: 'poe1' as GameEdition,
    character_class: '',
    ascendancy: '',
    alternate_ascendancy: '',
    secondary_progression: '',
    character_level: 1,
    league: 'standard',
    mode: 'trade',
    difficulty: 'softcore',
    build_name: '',
    main_skill: '',
    secondary_skills: [] as string[],
    archetype: '',
    pob: '',
    item_text: '',
    equipment_slot: '',
    goals: ['mapping'] as string[],
    play_style: 'balanced',
    priority: 'budget_efficiency',
    problem: '',
    description: '',
    budget_amount: '',
    budget_currency: 'DIVINE',
    preserved_items: [] as string[],
    replaceable_slots: [] as string[],
    avoid: '',
    must_keep: '',
    notes: '',
    storage_consent: false,
    ai_explanation_opt_in: false,
});
const selectedClass = computed(() =>
    catalog.value?.classes.find((item) => item.id === form.character_class),
);
const regularAscendancies = computed(
    () =>
        selectedClass.value?.ascendancies.filter(
            (item) =>
                (item.type ?? 'regular') === 'regular' &&
                (item.availability ?? 'available') === 'available',
        ) ?? [],
);
const alternateAscendancies = computed(
    () =>
        selectedClass.value?.ascendancies.filter(
            (item) =>
                item.type === 'alternate' &&
                (item.availability ?? 'available') === 'available' &&
                item.requires_base_ascendancy === form.ascendancy,
        ) ?? [],
);
const isAuthenticated = computed(() => page.props.auth.user !== null);
const isVerified = computed(
    () => page.props.auth.user?.email_verified_at !== null,
);

async function loadCatalog(game: GameEdition): Promise<void> {
    catalogLoading.value = true;

    try {
        const response = await fetch(`/api/catalog/${game}/character-options`);
        catalog.value = response.ok ? await response.json() : null;
    } finally {
        catalogLoading.value = false;
    }
}

watch(
    () => form.game,
    async (game, previous) => {
        if (game !== previous) {
            form.character_class = '';
            form.ascendancy = '';
            form.alternate_ascendancy = '';
            form.secondary_progression = '';
            form.league = 'standard';
            form.main_skill = '';
            form.secondary_skills = [];
            secondarySkillText.value = '';
            form.archetype = '';
            form.item_text = '';
            form.equipment_slot = '';
            form.preserved_items = [];
            preservedItemText.value = '';
            form.replaceable_slots = [];
            replaceableSlotText.value = '';
            form.pob = '';
            importConflict.value =
                'Oyun değişti; sınıf, Ascendancy, league, skill, item ve import alanları güvenlik için temizlendi.';
        }

        await loadCatalog(game);
    },
);

watch(
    () => form.character_class,
    () => {
        if (
            !selectedClass.value?.ascendancies.some(
                (item) => item.id === form.ascendancy,
            )
        ) {
            form.ascendancy = '';
        }

        if (
            !alternateAscendancies.value.some(
                (item) => item.id === form.alternate_ascendancy,
            )
        ) {
            form.alternate_ascendancy = '';
        }

        dirty.value = true;
    },
);
watch(
    () => form.ascendancy,
    () => {
        if (
            !alternateAscendancies.value.some(
                (item) => item.id === form.alternate_ascendancy,
            )
        ) {
            form.alternate_ascendancy = '';
        }
    },
);
watch(
    form,
    () => {
        dirty.value = true;
    },
    { deep: true },
);

function validate(step: number): boolean {
    const next: Record<string, string> = {};

    if (step === 2) {
        if (
            !form.character_class ||
            selectedClass.value?.availability === 'planned'
        ) {
            next.character_class = 'Oynanabilir bir sınıf seçmelisin.';
        }

        if (form.character_level < 1 || form.character_level > 100) {
            next.character_level = 'Seviye 1 ile 100 arasında olmalı.';
        }

        if (
            form.ascendancy &&
            !regularAscendancies.value.some(
                (item) => item.id === form.ascendancy,
            )
        ) {
            next.ascendancy = 'Ascendancy seçilen sınıfa ait değil.';
        }
    }

    if (step === 3 && form.flow === 'analyse' && form.pob.trim().length < 12) {
        next.pob = 'Var olan build analizi için PoB kodu gereklidir.';
    }

    if (
        step === 3 &&
        form.flow === 'upgrade' &&
        !form.item_text.trim() &&
        !form.equipment_slot
    ) {
        next.item_text = 'Item metni veya ekipman slotu gereklidir.';
    }

    if (step === 4 && form.goals.length === 0) {
        next.goals = 'En az bir hedef seçmelisin.';
    }

    if (step === 5 && form.budget_amount && Number(form.budget_amount) < 0) {
        next.budget_amount = 'Bütçe negatif olamaz.';
    }

    if (step === 6 && !form.storage_consent) {
        next.storage_consent = 'Veri işleme açıklamasını onaylamalısın.';
    }

    errors.value = next;

    return Object.keys(next).length === 0;
}

async function move(step: number): Promise<void> {
    if (step > currentStep.value && !validate(currentStep.value)) {
        return;
    }

    currentStep.value = step;
    await nextTick();
    heading.value?.focus();
}
function toggleGoal(goal: string): void {
    form.goals = form.goals.includes(goal)
        ? form.goals.filter((item) => item !== goal)
        : [...form.goals, goal];
}

function commaSeparated(value: string): string[] {
    return value
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);
}

async function importPob(): Promise<void> {
    errors.value = {};
    importConflict.value = '';
    const response = await fetch('/api/build-imports/pob', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'Idempotency-Key': crypto.randomUUID().replaceAll('-', ''),
        },
        body: JSON.stringify({
            input: form.pob,
            persist: false,
            expected_game: form.game,
        }),
    });
    const body = await response.json();

    if (!response.ok) {
        if (body.status === 'edition_mismatch') {
            importConflict.value = `PoB ${body.detected_game} olarak algılandı; seçili ${body.expected_game} analiziyle birleştirilmedi.`;
        } else {
            errors.value.pob = 'PoB güvenli biçimde okunamadı.';
        }

        return;
    }

    const imported = body.import?.canonical_build;

    if (!imported) {
        return;
    }

    const detectedGame = imported.edition ?? body.import?.detected_game;

    if (detectedGame && detectedGame !== form.game) {
        importConflict.value = `PoB ${detectedGame} olarak algılandı; seçili ${form.game} analiziyle birleştirilmedi.`;

        return;
    }

    const importedClass = imported.character_class_id
        ? String(imported.character_class_id).split('.').at(-1)
        : '';
    const importedAscendancy = imported.ascendancy_id
        ? String(imported.ascendancy_id).split('.').at(-1)
        : '';
    const conflicts: string[] = [];

    if (
        form.character_class &&
        importedClass &&
        form.character_class !== importedClass
    ) {
        conflicts.push('sınıf');
    }

    if (
        form.ascendancy &&
        importedAscendancy &&
        form.ascendancy !== importedAscendancy
    ) {
        conflicts.push('Ascendancy');
    }

    if (conflicts.length) {
        importConflict.value = `PoB ile seçimin ${conflicts.join(' ve ')} alanında çelişiyor. Mevcut seçimin korunuyor.`;
    } else {
        if (importedClass) {
            form.character_class = importedClass;
        }

        if (importedAscendancy) {
            form.ascendancy = importedAscendancy;
        }

        if (imported.character_level) {
            form.character_level = imported.character_level;
        }

        const firstSkill = imported.skills?.[0]?.name;

        if (firstSkill) {
            form.main_skill = firstSkill;
        }
    }
}

function safeDraft(): Record<string, unknown> {
    return Object.fromEntries(
        Object.entries(form).filter(
            ([key]) => key !== 'pob' && key !== 'item_text',
        ),
    );
}
async function saveDraft(): Promise<void> {
    if (!isAuthenticated.value) {
        return;
    }

    await fetch('/api/analysis-draft', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN':
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? '',
        },
        body: JSON.stringify({
            game: form.game,
            flow: form.flow,
            current_step: currentStep.value,
            safe_fields: safeDraft(),
        }),
    });
    dirty.value = false;
}

async function submit(): Promise<void> {
    if (!validate(6)) {
        return;
    }

    if (!isAuthenticated.value) {
        window.location.href = `/login?intended=${encodeURIComponent('/analyses/new')}`;

        return;
    }

    if (!isVerified.value) {
        window.location.href = '/email/verify';

        return;
    }

    submitting.value = true;
    errors.value = {};
    const response = await fetch('/api/analyses/wizard', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN':
                document.querySelector<HTMLMetaElement>(
                    'meta[name="csrf-token"]',
                )?.content ?? '',
            'Idempotency-Key': crypto.randomUUID().replaceAll('-', ''),
        },
        body: JSON.stringify(form),
    });
    const body = await response.json();
    submitting.value = false;

    if (!response.ok) {
        errors.value = body.errors
            ? Object.fromEntries(
                  Object.entries(body.errors).map(([key, value]) => [
                      key,
                      Array.isArray(value) ? value[0] : String(value),
                  ]),
              )
            : { submit: body.message ?? 'Analiz gönderilemedi.' };

        return;
    }

    dirty.value = false;
    resultId.value = body.analysis_id;
}

function beforeUnload(event: BeforeUnloadEvent): void {
    if (dirty.value && !resultId.value) {
        event.preventDefault();
    }
}
onMounted(async () => {
    await loadCatalog(form.game);

    if (isAuthenticated.value) {
        const draftResponse = await fetch('/api/analysis-draft', {
            headers: { Accept: 'application/json' },
        });
        const body = await draftResponse.json();

        if (draftResponse.ok && body.draft?.safe_fields) {
            const draftGame = body.draft.game_edition;

            if (draftGame === 'poe1' || draftGame === 'poe2') {
                form.game = draftGame;
                await loadCatalog(draftGame);
            }

            const fields =
                typeof body.draft.safe_fields === 'string'
                    ? JSON.parse(body.draft.safe_fields)
                    : body.draft.safe_fields;

            for (const [key, value] of Object.entries(fields)) {
                if (key in form && key !== 'pob' && key !== 'item_text') {
                    Object.assign(form, { [key]: value });
                }
            }

            secondarySkillText.value = form.secondary_skills.join(', ');
            preservedItemText.value = form.preserved_items.join(', ');
            replaceableSlotText.value = form.replaceable_slots.join(', ');

            currentStep.value = Number(body.draft.current_step) || 1;
            dirty.value = false;
        }
    }

    window.addEventListener('beforeunload', beforeUnload);
});
onBeforeUnmount(() => window.removeEventListener('beforeunload', beforeUnload));
</script>

<template>
    <Head title="Yeni PoE analizi" />
    <AppShell current="new" :contained="false">
        <header class="page-heading wizard-heading">
            <div>
                <p class="kicker">
                    {{ form.game === 'poe1' ? 'PoE 1' : 'PoE 2' }} · Sürüm
                    {{ catalog?.version ?? catalog?.patch }}
                </p>
                <h1 ref="heading" tabindex="-1">
                    Karakter planı ve analiz sihirbazı
                </h1>
                <p>
                    Sınıf, hedef ve bütçe kararlarını oyun-sürümlü doğrulanmış
                    katalogla kaydet.
                </p>
            </div>
            <button
                v-if="isAuthenticated"
                type="button"
                class="button is-secondary"
                @click="saveDraft"
            >
                Güvenli taslağı kaydet
            </button>
        </header>
        <ol class="wizard-steps seven" aria-label="Analiz adımları">
            <li
                v-for="(step, index) in steps"
                :key="step"
                :class="{
                    'is-current': currentStep === index + 1,
                    'is-complete': currentStep > index + 1,
                }"
                :aria-current="currentStep === index + 1 ? 'step' : undefined"
            >
                <span>{{ index + 1 }}</span
                >{{ step }}
            </li>
        </ol>
        <div
            v-if="Object.keys(errors).length"
            class="form-error"
            role="alert"
            tabindex="-1"
        >
            <strong>Bu adımı tamamlamak için:</strong>
            <ul>
                <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
        </div>
        <section v-if="resultId" class="submission-success" role="status">
            <div>
                <p class="kicker">Analiz oluşturuldu</p>
                <h2>İstek gerçek workflowa alındı</h2>
                <p>
                    Kimlik: <code>{{ resultId }}</code>
                </p>
                <a class="button is-primary" :href="`/analyses/${resultId}`"
                    >Analizi görüntüle</a
                >
            </div>
        </section>
        <form v-else class="wizard-panel" @submit.prevent="submit">
            <fieldset v-if="currentStep === 1">
                <legend>Ne yapmak istiyorsun?</legend>
                <div class="choice-list" aria-label="Oyun seçimi">
                    <label
                        v-for="game in [
                            { id: 'poe1', label: 'Path of Exile 1' },
                            {
                                id: 'poe2',
                                label: 'Path of Exile 2 · Early Access',
                            },
                        ]"
                        :key="game.id"
                    >
                        <input
                            v-model="form.game"
                            type="radio"
                            name="game"
                            :value="game.id"
                        />
                        <span
                            ><strong>{{ game.label }}</strong
                            ><small
                                >Oyun katalogları ve importlar birbirinden izole
                                edilir.</small
                            ></span
                        >
                    </label>
                </div>
                <div class="choice-list">
                    <label
                        v-for="option in [
                            { id: 'plan', label: 'Sıfırdan build planla' },
                            {
                                id: 'analyse',
                                label: 'Var olan buildi analiz et',
                            },
                            {
                                id: 'upgrade',
                                label: 'Item veya slot yükseltmesi ara',
                            },
                        ]"
                        :key="option.id"
                        ><input
                            v-model="form.flow"
                            type="radio"
                            name="flow"
                            :value="option.id"
                        /><span
                            ><strong>{{ option.label }}</strong
                            ><small
                                >{{
                                    form.game === 'poe1'
                                        ? 'Path of Exile 1'
                                        : 'Path of Exile 2'
                                }}, PC</small
                            ></span
                        ></label
                    >
                </div>
                <p v-if="catalog?.early_access" class="form-warning">
                    PoE 2 Early Access kataloğu sürüm kontrollüdür; planned
                    sınıflar seçilemez.
                </p>
            </fieldset>
            <fieldset v-else-if="currentStep === 2">
                <legend>Karakter</legend>
                <div class="form-grid three-columns">
                    <label class="field"
                        ><span>Sınıf</span
                        ><select
                            v-model="form.character_class"
                            :aria-invalid="!!errors.character_class"
                        >
                            <option value="">Seç</option>
                            <option
                                v-for="item in catalog?.classes"
                                :key="item.id"
                                :value="item.id"
                                :disabled="item.availability === 'planned'"
                            >
                                {{ item.name
                                }}{{
                                    item.availability === 'planned'
                                        ? ' · Early Access sürümünde henüz oynanabilir değil'
                                        : ''
                                }}
                            </option>
                        </select></label
                    ><label class="field"
                        ><span>Ascendancy</span
                        ><select
                            v-model="form.ascendancy"
                            :disabled="!selectedClass"
                        >
                            <option value="">Henüz karar vermedim</option>
                            <option
                                v-for="item in regularAscendancies"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.name }}
                            </option>
                        </select></label
                    ><label v-if="alternateAscendancies.length" class="field"
                        ><span>Alternatif Ascendancy</span
                        ><select v-model="form.alternate_ascendancy">
                            <option value="">Seçme</option>
                            <option
                                v-for="item in alternateAscendancies"
                                :key="item.id"
                                :value="item.id"
                            >
                                {{ item.name }}
                            </option>
                        </select></label
                    ><label class="field"
                        ><span>Karakter seviyesi</span
                        ><input
                            v-model.number="form.character_level"
                            type="number"
                            min="1"
                            max="100" /></label
                    ><label class="field"
                        ><span>League</span
                        ><select v-model="form.league">
                            <option value="standard">Standard</option>
                        </select></label
                    ><label class="field"
                        ><span>Oyun modu</span
                        ><select v-model="form.mode">
                            <option value="trade">Trade</option>
                            <option value="ssf">SSF</option>
                        </select></label
                    ><label class="field"
                        ><span>Zorluk</span
                        ><select v-model="form.difficulty">
                            <option value="softcore">Softcore</option>
                            <option value="hardcore">Hardcore</option>
                        </select></label
                    ><label class="field"
                        ><span>Ruthless</span
                        ><select disabled>
                            <option>Bu sürümde desteklenmiyor</option>
                        </select></label
                    >
                </div>
            </fieldset>
            <fieldset v-else-if="currentStep === 3">
                <legend>Build bilgileri</legend>
                <div class="form-grid two-columns">
                    <label class="field"
                        ><span>Build adı (opsiyonel)</span
                        ><input
                            v-model="form.build_name"
                            maxlength="120" /></label
                    ><label class="field"
                        ><span>Ana skill</span
                        ><input
                            v-model="form.main_skill"
                            maxlength="120" /></label
                    ><label class="field"
                        ><span>İkincil skilller (virgülle ayır)</span
                        ><input
                            v-model="secondarySkillText"
                            maxlength="500"
                            @change="
                                form.secondary_skills =
                                    commaSeparated(secondarySkillText)
                            " /></label
                    ><label class="field"
                        ><span>Arketip</span
                        ><input
                            v-model="form.archetype"
                            maxlength="120" /></label
                    ><label v-if="form.flow === 'upgrade'" class="field"
                        ><span>Ekipman slotu</span
                        ><select v-model="form.equipment_slot">
                            <option value="">Seç</option>
                            <option
                                v-for="slot in [
                                    'weapon',
                                    'helmet',
                                    'body_armour',
                                    'gloves',
                                    'boots',
                                    'amulet',
                                    'ring',
                                    'belt',
                                ]"
                                :key="slot"
                                :value="slot"
                            >
                                {{ slot }}
                            </option>
                        </select></label
                    >
                </div>
                <label v-if="form.flow === 'analyse'" class="field"
                    ><span>PoB kodu veya pasted pobb.in bağlantısı</span
                    ><textarea
                        v-model="form.pob"
                        rows="7"
                        maxlength="1048576"
                        autocomplete="off"
                    ></textarea
                    ><small
                        >Bu ham girdi localStorage veya taslağa yazılmaz.</small
                    ><button
                        type="button"
                        class="button is-secondary"
                        @click="importPob"
                    >
                        PoB alanlarını doldur
                    </button></label
                >
                <p v-if="importConflict" class="form-warning" role="status">
                    {{ importConflict }}
                </p>
                <label v-if="form.flow === 'upgrade'" class="field"
                    ><span>Item metni</span
                    ><textarea
                        v-model="form.item_text"
                        rows="7"
                        maxlength="65536"
                        autocomplete="off"
                    ></textarea
                    ><small>Ham item metni taslakta saklanmaz.</small></label
                >
            </fieldset>
            <fieldset v-else-if="currentStep === 4">
                <legend>Hedefler</legend>
                <div class="goal-grid">
                    <label
                        v-for="goal in [
                            'mapping',
                            'bossing',
                            'league_starter',
                            'all_rounder',
                            'delve',
                            'expedition',
                            'heist',
                            'sanctum',
                            'simulacrum',
                            'defence',
                            'speed',
                        ]"
                        :key="goal"
                        ><input
                            type="checkbox"
                            :checked="form.goals.includes(goal)"
                            @change="toggleGoal(goal)"
                        />{{ goal.replaceAll('_', ' ') }}</label
                    >
                </div>
                <div class="form-grid two-columns">
                    <label class="field"
                        ><span>Oynanış stili</span
                        ><select v-model="form.play_style">
                            <option value="balanced">Dengeli</option>
                            <option value="active">Aktif</option>
                            <option value="relaxed">Rahat</option>
                        </select></label
                    ><label class="field"
                        ><span>Ana öncelik</span
                        ><select v-model="form.priority">
                            <option
                                v-for="value in [
                                    'damage',
                                    'defence',
                                    'clear_speed',
                                    'boss_damage',
                                    'budget_efficiency',
                                ]"
                                :key="value"
                                :value="value"
                            >
                                {{ value.replaceAll('_', ' ') }}
                            </option>
                        </select></label
                    >
                </div>
                <label class="field"
                    ><span>Mevcut sorun</span
                    ><textarea
                        v-model="form.problem"
                        rows="3"
                        maxlength="500"
                    ></textarea></label
                ><label class="field"
                    ><span>Serbest açıklama</span
                    ><textarea
                        v-model="form.description"
                        rows="4"
                        maxlength="1000"
                    ></textarea>
                </label>
            </fieldset>
            <fieldset v-else-if="currentStep === 5">
                <legend>Bütçe ve kısıtlar</legend>
                <div class="form-grid two-columns">
                    <label class="field"
                        ><span>Bütçe miktarı</span
                        ><input
                            v-model="form.budget_amount"
                            inputmode="decimal"
                            pattern="[0-9]+([.][0-9]{1,4})?" /></label
                    ><label class="field"
                        ><span>Currency</span
                        ><select v-model="form.budget_currency">
                            <option value="DIVINE">DIVINE</option>
                            <option value="CHAOS">CHAOS</option>
                        </select></label
                    >
                </div>
                <div class="form-grid two-columns">
                    <label class="field"
                        ><span>Korunması gereken itemlar (virgülle ayır)</span
                        ><input
                            v-model="preservedItemText"
                            maxlength="1000"
                            @change="
                                form.preserved_items =
                                    commaSeparated(preservedItemText)
                            " /></label
                    ><label class="field"
                        ><span>Değiştirilebilecek slotlar (virgülle ayır)</span
                        ><input
                            v-model="replaceableSlotText"
                            maxlength="500"
                            @change="
                                form.replaceable_slots =
                                    commaSeparated(replaceableSlotText)
                            "
                    /></label>
                </div>
                <label class="field"
                    ><span
                        >Kullanılmasını istemediğin skill, item veya
                        mekanikler</span
                    ><textarea v-model="form.avoid" rows="3"></textarea></label
                ><label class="field"
                    ><span>Vazgeçemeyeceğin özellikler</span
                    ><textarea
                        v-model="form.must_keep"
                        rows="3"
                    ></textarea></label
                ><label class="field"
                    ><span>Ek not</span
                    ><textarea v-model="form.notes" rows="3"></textarea>
                </label>
            </fieldset>
            <fieldset v-else-if="currentStep === 6">
                <legend>Gizlilik ve AI onayı</legend>
                <div class="privacy-ledger">
                    <section>
                        <h2>Saklanan</h2>
                        <p>
                            Yapılandırılmış seçimler, normalize edilmiş sonuç,
                            kaynak kimliği ve süreli taslak.
                        </p>
                    </section>
                    <section>
                        <h2>Saklanmayan</h2>
                        <p>
                            Ham PoB ve item metni taslakta, localStorage'da,
                            logda veya admin auditinde tutulmaz.
                        </p>
                    </section>
                    <section>
                        <h2>AI sağlayıcısı</h2>
                        <p>
                            Yalnız açık onayla, minimize edilmiş normalize
                            alanlar. AI piyasa veya oyun gerçeği oluşturamaz.
                        </p>
                    </section>
                </div>
                <div class="choice-list">
                    <label
                        ><input
                            v-model="form.ai_explanation_opt_in"
                            type="checkbox"
                        /><span
                            ><strong
                                >İsteğe bağlı AI açıklamasına izin ver</strong
                            ><small>Deterministik sonuç değişmez.</small></span
                        ></label
                    ><label class="consent-choice"
                        ><input
                            v-model="form.storage_consent"
                            type="checkbox"
                        /><span
                            ><strong
                                >İşleme ve süreli saklama açıklamasını
                                okudum</strong
                            ><small
                                >Verilerimi profil gizlilik sayfasından
                                silebilirim.</small
                            ></span
                        ></label
                    >
                </div>
            </fieldset>
            <fieldset v-else>
                <legend>Kontrol ve gönderim</legend>
                <dl class="review-grid">
                    <div>
                        <dt>Akış</dt>
                        <dd>{{ form.flow }}</dd>
                    </div>
                    <div>
                        <dt>Oyun</dt>
                        <dd>
                            {{
                                form.game === 'poe1'
                                    ? 'Path of Exile 1'
                                    : 'Path of Exile 2 · Early Access'
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt>Karakter</dt>
                        <dd>
                            {{ selectedClass?.name }} ·
                            {{ form.ascendancy || 'Kararsız'
                            }}{{
                                form.alternate_ascendancy
                                    ? ` · ${form.alternate_ascendancy}`
                                    : ''
                            }}
                            · Lv
                            {{ form.character_level }}
                        </dd>
                    </div>
                    <div>
                        <dt>League</dt>
                        <dd>
                            Standard · {{ form.mode }} · {{ form.difficulty }}
                        </dd>
                    </div>
                    <div>
                        <dt>Hedefler</dt>
                        <dd>{{ form.goals.join(', ') }}</dd>
                    </div>
                    <div>
                        <dt>Bütçe</dt>
                        <dd>
                            {{
                                form.budget_amount
                                    ? `${form.budget_amount} ${form.budget_currency}`
                                    : 'Belirtilmedi'
                            }}
                        </dd>
                    </div>
                </dl>
                <p v-if="!isAuthenticated" class="form-warning">
                    Kalıcı analiz için giriş yapmalısın. Gönder dediğinde giriş
                    ekranına yönlendirileceksin.
                </p>
                <p v-else-if="!isVerified" class="form-warning">
                    Analiz göndermek için e-postanı doğrulamalısın.
                </p>
            </fieldset>
            <div class="wizard-actions">
                <button
                    v-if="currentStep > 1"
                    type="button"
                    class="button is-secondary"
                    @click="move(currentStep - 1)"
                >
                    Geri</button
                ><button
                    v-if="currentStep < 7"
                    type="button"
                    class="button is-primary"
                    @click="move(currentStep + 1)"
                >
                    Devam</button
                ><button
                    v-else
                    type="submit"
                    class="button is-primary"
                    :disabled="submitting"
                >
                    {{ submitting ? 'Gönderiliyor…' : 'Analizi oluştur' }}
                </button>
            </div>
        </form>
    </AppShell>
</template>

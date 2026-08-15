import type { DemoFinding, DemoRecipe, DemoUpgrade } from '@/types/analysis-ui';

export const demoBuild = {
    edition: 'poe1' as const,
    className: 'Elementalist',
    buildName: 'Arc Ignite Mapper',
    level: 92,
    league: 'Fixture League',
    patch: '3.27.0-fixture',
    ruleset: '1.4.2-fixture',
    parser: 'pob1-fixture 1.0.0',
    confidence: 87,
    skills: [
        { name: 'Arc', role: 'Main skill', confidence: 96 },
        { name: 'Flame Dash', role: 'Movement', confidence: 93 },
        { name: 'Determination', role: 'Reservation', confidence: 78 },
    ],
    defenses: [
        { label: 'Maximum Life', value: '3,842', status: 'warning' },
        { label: 'Fire Resistance', value: '75%', status: 'confirmed' },
        { label: 'Cold Resistance', value: '71%', status: 'warning' },
        { label: 'Lightning Resistance', value: '75%', status: 'confirmed' },
    ],
    itemSlots: [
        { slot: 'Helmet', state: 'upgrade', label: 'Rare Hubris Circlet' },
        { slot: 'Body Armour', state: 'stable', label: 'Rare Vaal Regalia' },
        { slot: 'Gloves', state: 'upgrade', label: 'Rare Sorcerer Gloves' },
        { slot: 'Boots', state: 'dependency', label: 'Rare Two-Toned Boots' },
        { slot: 'Amulet', state: 'stable', label: 'Rare Citrine Amulet' },
        { slot: 'Ring 1', state: 'upgrade', label: 'Rare Amethyst Ring' },
    ],
};

export const demoFindings: DemoFinding[] = [
    {
        code: 'defence.cold_resistance_gap',
        severity: 'warning',
        category: 'Defences',
        title: {
            tr: 'Soğuk direnci hedefin altında',
            en: 'Cold resistance is below target',
        },
        summary: {
            tr: 'Normalize edilmiş snapshot, mevcut soğuk direncini fixture hedefinden 4 puan aşağıda gösteriyor.',
            en: 'The normalized snapshot places cold resistance 4 points below the fixture target.',
        },
        why: {
            tr: 'Harita modları direnci düşürdüğünde bu açık büyüyebilir. Bu bir fiyat veya hayatta kalma tahmini değildir.',
            en: 'Map modifiers can widen this gap. This is not a price or survival estimate.',
        },
        limitation: {
            tr: 'Geçici bufflar ve koşullu flask etkileri fixture analizinde kanıtlanmadı.',
            en: 'Temporary buffs and conditional flask effects are not proven by this fixture analysis.',
        },
        confidence: 94,
        evidence: [
            {
                input: 'defences.cold_resistance = 71',
                rule: 'poe1.defence.elemental_resistance.minimum',
                source: 'LOOTWRIGHT-001 / fixture-1',
            },
        ],
    },
    {
        code: 'defence.life_pool_opportunity',
        severity: 'opportunity',
        category: 'Defences',
        title: {
            tr: 'Can havuzunda iyileştirme alanı var',
            en: 'Life pool has room to improve',
        },
        summary: {
            tr: 'Mevcut fixture hedefi ve kullanıcı hedefi, nadir ekipmanlarda yaşam önceliğini destekliyor.',
            en: 'The fixture target and player goal support prioritizing life on rare equipment.',
        },
        why: {
            tr: 'Bu bulgu ekipman slotlarını sıralamak için kullanılıyor; belirli bir hasarı tanklayacağınızı iddia etmiyor.',
            en: 'This finding helps rank equipment slots. It does not claim you can survive a specific hit.',
        },
        limitation: {
            tr: 'Fixture kuralları yalnızca normalize edilmiş maksimum yaşam girdisini değerlendiriyor.',
            en: 'Fixture rules evaluate only the normalized maximum-life input.',
        },
        confidence: 86,
        evidence: [
            {
                input: 'defences.maximum_life = 3842',
                rule: 'poe1.defence.maximum_life.fixture_band',
                source: 'LOOTWRIGHT-001 / fixture-1',
            },
        ],
    },
    {
        code: 'skills.support_link_unresolved',
        severity: 'information',
        category: 'Skills',
        title: {
            tr: 'Bir destek bağlantısı çözümlenemedi',
            en: 'One support link is unresolved',
        },
        summary: {
            tr: 'Parser, ana beceri grubundaki bir desteği onaylı fixture sözlüğüne eşleyemedi.',
            en: 'The parser could not map one main-skill support to the approved fixture vocabulary.',
        },
        why: {
            tr: 'Lootwright yakın bir isim tahmin etmek yerine bu gereksinimi açık bırakıyor.',
            en: 'Lootwright leaves the requirement open instead of guessing a nearby name.',
        },
        limitation: {
            tr: 'Açıklama gelene kadar bu bağlantı öneri puanına dahil edilmez.',
            en: 'The link is excluded from recommendation scoring until clarified.',
        },
        confidence: 61,
        evidence: [
            {
                input: 'skills.main.support[4] = unresolved',
                rule: 'pob1.skills.support.resolve_exact',
                source: 'USER-PASTED-POB / fixture-1',
            },
        ],
    },
];

export const demoUpgrades: DemoUpgrade[] = [
    {
        code: 'upgrade.helmet_resistance_life',
        rank: 1,
        slot: 'Helmet',
        title: {
            tr: 'Kaskta soğuk direnci ve yaşamı birlikte düzelt',
            en: 'Fix cold resistance and life together on the helmet',
        },
        reason: {
            tr: 'Tek slotta iki kanıtlanmış savunma açığını hedefler ve bot slotunu hareket hızı için serbest bırakır.',
            en: 'Targets two proven defence gaps in one slot and leaves boots free for movement speed.',
        },
        limitation: {
            tr: 'Canlı ilan, fiyat ve bulunabilirlik verisi kullanılmadı.',
            en: 'No live listing, price, or availability data was used.',
        },
        budgetBand: {
            tr: 'Kullanıcı bütçesi: 10 CHAOS',
            en: 'Player budget: 10 CHAOS',
        },
        dependencies: ['Boots: keep at least 25% movement speed'],
        findingCodes: [
            'defence.cold_resistance_gap',
            'defence.life_pool_opportunity',
        ],
        confidence: 89,
    },
    {
        code: 'upgrade.ring_resistance',
        rank: 2,
        slot: 'Ring 1',
        title: {
            tr: 'Yüzükte esnek direnç yedeği oluştur',
            en: 'Create a flexible resistance reserve on the ring',
        },
        reason: {
            tr: 'Kask tarifi katılaşırsa direnç hedefini ikinci bir slota dağıtır.',
            en: 'Distributes the resistance target to a second slot if the helmet recipe becomes too strict.',
        },
        limitation: {
            tr: 'Bu bir alternatif yol; iki yükseltmenin birlikte satın alınması önerilmiyor.',
            en: 'This is an alternative path. It does not recommend purchasing both upgrades together.',
        },
        budgetBand: {
            tr: 'Geniş bant: fiyat tahmini yok',
            en: 'Broad band: no price estimate',
        },
        dependencies: ['Helmet: relax cold resistance if ring carries the gap'],
        findingCodes: ['defence.cold_resistance_gap'],
        confidence: 78,
    },
];

export const demoRecipes: DemoRecipe[] = [
    {
        slot: 'Helmet',
        category: 'Armour > Helmet',
        baseFamily: 'Energy Shield Helmet',
        budget: '10 CHAOS (player context, not a price estimate)',
        confidence: 89,
        dependencies: ['Boots: minimum 25% movement speed'],
        strict: {
            required: [
                {
                    label: '+# to maximum Life',
                    minimum: '90',
                    reason: {
                        tr: 'Can havuzu bulgusunu hedefler.',
                        en: 'Targets the life-pool finding.',
                    },
                    findingCode: 'defence.life_pool_opportunity',
                },
                {
                    label: '+#% to Cold Resistance',
                    minimum: '35',
                    reason: {
                        tr: 'Kanıtlanmış direnç açığını kapatır.',
                        en: 'Closes the proven resistance gap.',
                    },
                    findingCode: 'defence.cold_resistance_gap',
                },
            ],
            optional: [
                {
                    label: '+#% to Fire Resistance',
                    minimum: '20',
                    weight: 40,
                    reason: {
                        tr: 'Diğer slotlarda esneklik sağlar.',
                        en: 'Creates flexibility on other slots.',
                    },
                    findingCode: 'defence.cold_resistance_gap',
                },
            ],
            excluded: [
                {
                    label: 'Corrupted',
                    reason: {
                        tr: 'Fixture kuralı değiştirilebilir tabanı korur.',
                        en: 'The fixture rule preserves a modifiable base.',
                    },
                    findingCode: 'defence.life_pool_opportunity',
                },
            ],
        },
        broad: {
            required: [
                {
                    label: '+# to maximum Life',
                    minimum: '70',
                    reason: {
                        tr: 'Daha geniş fallback aralığı.',
                        en: 'A broader fallback range.',
                    },
                    findingCode: 'defence.life_pool_opportunity',
                },
            ],
            optional: [
                {
                    label: '+#% to Cold Resistance',
                    minimum: '25',
                    weight: 70,
                    reason: {
                        tr: 'Direnci zorunludan ağırlıklıya gevşetir.',
                        en: 'Relaxes resistance from required to weighted.',
                    },
                    findingCode: 'defence.cold_resistance_gap',
                },
            ],
            excluded: [],
        },
    },
];

export const demoRecipeText = [
    'Lootwright manual Trade recipe',
    'Edition: PoE1',
    'Realm: PC',
    'League: Fixture League',
    'Slot: Helmet',
    'Category: Armour > Helmet',
    'Base family: Energy Shield Helmet',
    'Strict required:',
    '- +# to maximum Life: minimum 90',
    '- +#% to Cold Resistance: minimum 35',
    'Weighted optional:',
    '- +#% to Fire Resistance: minimum 20, weight 40',
    'Excluded:',
    '- Corrupted',
    'Ruleset: 1.4.2-fixture',
    'Source: LOOTWRIGHT-001 / fixture-1',
    'Confidence: 89%',
].join('\n');

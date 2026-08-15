import type { LocalizedCopy } from '@/composables/useLocale';

export interface InformationSection {
    title: LocalizedCopy;
    paragraphs: LocalizedCopy[];
    bullets?: LocalizedCopy[];
}

export interface InformationPage {
    eyebrow: string;
    title: LocalizedCopy;
    summary: LocalizedCopy;
    sections: InformationSection[];
}

export const informationPages: Record<string, InformationPage> = {
    privacy: {
        eyebrow: 'PRIVACY BASELINE',
        title: {
            tr: 'Gizlilik, varsayılan davranıştır',
            en: 'Privacy is the default behavior',
        },
        summary: {
            tr: 'Lootwright yalnızca isteyerek gönderdiğin build içeriğini ve iş akışı için gereken minimum metadata’yı işler.',
            en: 'Lootwright processes only the build content you deliberately submit and the minimum metadata required for the workflow.',
        },
        sections: [
            {
                title: { tr: 'Ham girdi', en: 'Raw input' },
                paragraphs: [
                    {
                        tr: 'Kuyruk handoff’u gereken analizlerde ham içerik şifreli özel depoda kısa süre tutulur. Parse veya terminal redden sonra silinir; en uzun süre bir saattir.',
                        en: 'When queue handoff is required, raw content is held briefly in encrypted private storage. It is deleted after parsing or terminal rejection, with a one-hour ceiling.',
                    },
                ],
            },
            {
                title: {
                    tr: 'Anonim privacy session',
                    en: 'Anonymous privacy session',
                },
                paragraphs: [
                    {
                        tr: 'Anonim oturum bir UUIDv7 ve 256-bit gizli değerden oluşur. Veritabanı yalnızca gizli değerin SHA-256 hash’ini, durumu ve süre sonunu tutar; IP veya user-agent kimlik olarak saklanmaz.',
                        en: 'An anonymous session contains a UUIDv7 and a 256-bit secret. The database stores only the secret SHA-256 hash, status, and expiry; no IP or user agent is stored as identity.',
                    },
                ],
            },
            {
                title: { tr: 'İsteğe bağlı AI', en: 'Optional AI' },
                paragraphs: [
                    {
                        tr: 'AI varsayılan olarak kapalıdır. PoB, secret, session verisi veya gereksiz kişisel içerik sağlayıcıya gönderilmez. Ham prompt ve ham yanıt varsayılan olarak saklanmaz.',
                        en: 'AI is off by default. PoB, secrets, session data, and unnecessary personal content are not sent to a provider. Raw prompts and raw responses are not stored by default.',
                    },
                ],
            },
        ],
    },
    deletion: {
        eyebrow: 'DATA LIFECYCLE',
        title: {
            tr: 'Build ve analiz verilerini sil',
            en: 'Delete build and analysis data',
        },
        summary: {
            tr: 'Silme, build artifact’ını, normalize snapshot’ı, analizleri, bulguları, önerileri, tarifleri ve bağlı açıklamaları birincil depodan kaldırır.',
            en: 'Deletion removes the build artifact, normalized snapshot, analyses, findings, recommendations, recipes, and linked explanations from the primary store.',
        },
        sections: [
            {
                title: { tr: 'Neler kalır?', en: 'What remains?' },
                paragraphs: [
                    {
                        tr: 'Yalnızca kimlikle bağlanamayan toplu silme sayıları yasal ve operasyonel kanıt için kalabilir. Üretim backup purge ve restore-time deletion replay henüz release önkoşuludur.',
                        en: 'Only unlinkable aggregate deletion counts may remain for legal and operational evidence. Production backup purge and restore-time deletion replay remain release prerequisites.',
                    },
                ],
            },
            {
                title: { tr: 'Bu ekranın durumu', en: 'Status of this screen' },
                paragraphs: [
                    {
                        tr: 'Aşağıdaki kontrol fixture demonstrasyonudur ve gerçek veri silmez. Gerçek workspace kimliği bağlandığında aynı açıklıkta owner-scoped silme use case’ini çağıracaktır.',
                        en: 'The control below is a fixture demonstration and deletes no real data. Once a real workspace identity is connected, it will call the owner-scoped deletion use case with the same explicit boundary.',
                    },
                ],
            },
        ],
    },
    methodology: {
        eyebrow: 'METHOD / 01',
        title: {
            tr: 'Aynı girdi, aynı kanıt',
            en: 'Same input, same evidence',
        },
        summary: {
            tr: 'Lootwright’ın otoritesi AI değil; normalize girdi, immutable ruleset kimliği ve deterministik engine’dir.',
            en: 'Lootwright’s authority is not AI. It is the normalized input, immutable ruleset identity, and deterministic engine.',
        },
        sections: [
            {
                title: {
                    tr: '1. Parse ve normalize',
                    en: '1. Parse and normalize',
                },
                paragraphs: [
                    {
                        tr: 'Edition, adapter ve parser sürümü açıkça çözülür. Bilinmeyen veya çelişkili facts tahmin edilmez.',
                        en: 'Edition, adapter, and parser version are resolved explicitly. Unknown or conflicting facts are not guessed.',
                    },
                ],
            },
            {
                title: { tr: '2. Exact ruleset', en: '2. Exact ruleset' },
                paragraphs: [
                    {
                        tr: 'Oyun, patch, league, parser ve checksum eşleşmeden analiz çalışmaz. “Latest” sürüme sessiz geçiş yoktur.',
                        en: 'Analysis does not run until game, patch, league, parser, and checksum match. There is no silent fallback to “latest”.',
                    },
                ],
            },
            {
                title: {
                    tr: '3. Bulgu ve öneri',
                    en: '3. Finding and recommendation',
                },
                paragraphs: [
                    {
                        tr: 'Her bulgu girdi kanıtı ve rule reference taşır. Sıralama açık öncelikler, bağımlılıklar ve deterministik tie-break kuralları kullanır.',
                        en: 'Every finding carries input evidence and a rule reference. Ranking uses explicit priorities, dependencies, and deterministic tie-break rules.',
                    },
                ],
            },
            {
                title: { tr: '4. Manuel tarif', en: '4. Manual recipe' },
                paragraphs: [
                    {
                        tr: 'Exact filtre etiketleri yalnızca onaylı ruleset sözlüğünden gelir. Kullanıcı filtreleri resmî Trade arayüzünde elle uygular.',
                        en: 'Exact filter labels come only from the approved ruleset vocabulary. The player applies filters manually in the official Trade UI.',
                    },
                ],
            },
        ],
    },
    limitations: {
        eyebrow: 'LIMITS / FAIL CLOSED',
        title: {
            tr: 'Lootwright’ın bilmediği şeyler',
            en: 'What Lootwright does not know',
        },
        summary: {
            tr: 'Sınırlamalar, küçük puntolu feragatler değil; sonucun bir parçasıdır.',
            en: 'Limitations are not fine print. They are part of the result.',
        },
        sections: [
            {
                title: { tr: 'Piyasa', en: 'Market' },
                paragraphs: [
                    {
                        tr: 'Canlı ilan, fiyat, satıcı sırası veya bulunabilirlik bilgisi yoktur. Bütçe fiyat tahmini değildir.',
                        en: 'There are no live listings, prices, seller rankings, or availability claims. Budget is not a price estimate.',
                    },
                ],
            },
            {
                title: { tr: 'Oyun ve cihaz', en: 'Game and device' },
                paragraphs: [
                    {
                        tr: 'Lootwright oyun process’ini, memory’yi, dosyaları, ekranı, panoyu, network trafiğini veya logları incelemez. Klavye, mouse, chat, whisper veya purchase otomasyonu yapmaz.',
                        en: 'Lootwright does not inspect the game process, memory, files, screen, clipboard, network traffic, or logs. It does not automate keyboard, mouse, chat, whispers, or purchases.',
                    },
                ],
            },
            {
                title: { tr: 'Eksik bilgi', en: 'Missing information' },
                paragraphs: [
                    {
                        tr: 'Canonical bir terim kesin eşlenemiyorsa unresolved requirement gösterilir ve açıklama istenir. Benzer görünen bir modifier seçilmez.',
                        en: 'When a canonical term cannot be mapped exactly, an unresolved requirement is shown and clarification is requested. A similar-looking modifier is never selected.',
                    },
                ],
            },
            {
                title: { tr: 'PoE2', en: 'PoE2' },
                paragraphs: [
                    {
                        tr: 'PoE2 format reader ayrı bir beta boundary’dir. PoE2 ruleset, analiz ve Trade tarifi phase two onayı olmadan aktif değildir.',
                        en: 'The PoE2 format reader is a separate beta boundary. PoE2 rulesets, analysis, and Trade recipes remain inactive until phase-two approval.',
                    },
                ],
            },
        ],
    },
    affiliation: {
        eyebrow: 'INDEPENDENCE NOTICE',
        title: {
            tr: 'Bağımsız ve topluluk odaklı',
            en: 'Independent and community-focused',
        },
        summary: {
            tr: 'Lootwright, Grinding Gear Games tarafından onaylanmış, yetkilendirilmiş, desteklenmiş veya ortaklık kurulmuş bir ürün değildir.',
            en: 'Lootwright is not approved, authorized, supported, or partnered by Grinding Gear Games.',
        },
        sections: [
            {
                title: { tr: 'Zorunlu bildirim', en: 'Required notice' },
                paragraphs: [
                    {
                        tr: "This product isn't affiliated with or endorsed by Grinding Gear Games in any way.",
                        en: "This product isn't affiliated with or endorsed by Grinding Gear Games in any way.",
                    },
                ],
            },
            {
                title: { tr: 'Marka ve varlıklar', en: 'Brand and assets' },
                paragraphs: [
                    {
                        tr: 'Path of Exile adları yalnızca uyumluluğu doğru biçimde açıklamak için kullanılır. Lootwright GGG logolarını, artwork’ü, item art’ını, müziği, flavour text’i veya resmî UI’yi kopyalamaz.',
                        en: 'Path of Exile names are used only as reasonably necessary to describe compatibility. Lootwright does not copy GGG logos, artwork, item art, music, flavour text, or official UI.',
                    },
                ],
            },
        ],
    },
};

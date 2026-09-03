# Lootwright

Lootwright, oyuncunun sağladığı Path of Exile build verisini kanıta dayalı ve
deterministik olarak inceleyen Laravel 13 uygulamasıdır. Çıktı; bulgular,
öncelikli geliştirmeler ve insanın Trade sitesine elle girebileceği filtrelerdir.

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

## Ürün ve sürüm desteği

PoE1 için sınırlı kapsamlı PoB1 ayrıştırma, doğrulanmış ruleset çözümleme,
deterministik bulgular ve şifreli iş akışı kalıcılığı vardır. PoE2; PoB2,
kuralları, verisi, analiz motoru ve Trade sözlüğü ayrı olan bağımsız bir hedeftir;
PoE1'e geri dönüş yapmaz ve aktivasyon kanıtı olmadan herkese açılmaz.

## Build içe aktarma ve analiz

Kullanıcı tarafından açıkça gönderilen PoB XML/share-code ve desteklenen item
metni işlenir. URL'ler alınmaz. XXE, hatalı Unicode, aşırı boyut, decompression
bomb, derin XML ve sürüm/oyun uyuşmazlıkları reddedilir.

Akış: ayrıştırma → normalize etme → tam ruleset çözümleme → bulgular → upgrade
planlayıcı → manuel Trade filtresi → isteğe bağlı AI açıklaması.

## Trade, piyasa ve AI

Trade sitesi otomasyonu, alış işlemi ve belgelenmemiş API yoktur. Piyasa verisi
yalnızca onaylı, zaman damgalı bağlamsal gözlemdir; poe.ninja varsayılan olarak
kapalıdır. OpenAI isteğe bağlı ve varsayılan olarak kapalıdır; AI yalnızca niyet
sınıflandırabilir veya mevcut bulguları açıklayabilir, kural/fiyat/item uyduramaz.

## Özellik matrisi

| Özellik | Durum |
| --- | --- |
| Laravel uygulaması, hesaplar, sahiplik, RBAC, 2FA, audit | AVAILABLE |
| Güvenli PoB1 içe aktarma | AVAILABLE |
| PoE1 deterministik analiz | BETA |
| Bağımsız PoE2 adaptör ve motor | BETA |
| Upgrade planlayıcı ve manuel Trade tarifleri | BETA |
| Piyasa gözlemleri | EXPERIMENTAL |
| AI intent/açıklama | EXPERIMENTAL |
| PoE Wiki, uzak build alma, otomatik Trade | PLANNED |

AVAILABLE uygulanmış/testli; BETA kapsamı sınırlı kullanılabilir; EXPERIMENTAL
isteğe bağlı veya release-gated; PLANNED henüz sunulmaz.

## Veri, mimari ve Laravel Cloud

Tüm dış kaynaklar deny-by-default Policy/Provenance Gate'ten geçer. `src/`
framework'ten bağımsız domain ve motoru, `app/` Laravel altyapısını,
`resources/js` ise yalnızca sunumu içerir. Laravel Cloud üretim platformudur;
PostgreSQL sistem kaydıdır, yönetilen cache/queue/scheduler ve kalıcı özel obje
depolama yalnızca ihtiyaç ve inceleme sonrası açılır. `/up` canlılık, `/ready`
korumalı ayrıntılı kontroldür.

## Gizlilik ve sınırlamalar

Girdiler hostile kabul edilir; güvenlik testleri XSS, CSRF, IDOR, SSRF, SQL
enjeksiyonu, queue replay ve yetki yükseltmeyi kapsar. Ham veri ve AI içeriği
minimumda tutulur, gerektiğinde şifrelenir ve silinebilir. Mekanik kapsamı dardır;
dış sağlayıcılar varsayılan kapalıdır ve PoE2 aktivasyonu ayrıdır.

## Yerel kurulum

PHP 8.4, Composer 2, Node 24/npm 11 ve PostgreSQL gerekir:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate
npm ci
npm run build
composer run dev
```

Cloud kurulumu için `docs/deployment/laravel-cloud.md` izlenir; üretimde
yıkıcı komut çalıştırılmaz.

## Test ve katkı

Composer/npm kalite kapılarını, audit'leri, build'i ve doküman doğrulayıcıyı
çalıştırın. Canlı kabul için ayrılmış ortam ve `php artisan acceptance:gate`
zorunludur; fixture çalışma zamanı reddedilir. Katkıdan önce
`AGENTS.md`, `CONTRIBUTING.md` ve ilgili mimari/politika belgelerini okuyun.

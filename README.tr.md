# Lootwright

[English](README.md)

Lootwright; Path of Exile karakter dizilimlerini ileride izlenebilir ve
deterministik biçimde analiz etmek, ayrıca insanlar tarafından uygulanabilecek
eşya arama planları üretmek amacıyla geliştirilen açık kaynaklı bir pre-alpha
temelidir. Proje, altyapıdan bağımsız bir PHP alan çekirdeğine ve Inertia/Vue
arayüzüne sahip Laravel 13 modüler monolitidir.

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

Lootwright, görünür kaynak ve tazelik bilgisiyle piyasa bağlamı için önbelleğe
alınmış poe.ninja ekonomi verisini kullanabilir. Canlı Trade ilanlarını çekemez;
bunun yerine manuel resmi Trade filtre tarifleri üretir. Fiyatlar tahmindir.

PoE1 sihirbazı, Fortify tabanlı üyelik, kullanıcıya ait analiz alanı ve sunucu
tarafından yetkilendirilen üye/admin panelleri kullanılabilir. İlk doğrulanmış
super-admin için `php artisan lootwright:admin:promote user@example.com --force`
komutunu çalıştırın.

Lootwright henüz halka açık bir hizmet veya tamamlanmış bir son kullanıcı MVP'si
değildir. Onaylı üretim kuralları ve yetkili üretim analiz motoru bulunmadığı için
şu anda gerçek dizilim bulguları, yükseltme önerileri ya da üretim amaçlı Manuel
Trade Tarifleri sunamaz.

## Bugün çalışanlar

- Laravel 13, Inertia 3, Vue 3, TypeScript, Tailwind CSS ve shadcn-vue uygulama
  temelleri.
- `src/` altında yer alan ve otomatik bağımlılık sınırı testleriyle korunan,
  framework'ten bağımsız alan ve uygulama katmanları.
- Sürüm kimlikli değer nesneleri, DTO'lar, portlar, provenance kayıtları, iş
  akışı durumları, kalıcılık eşlemeleri, silme ve taşınabilir dışa aktarma
  sözleşmeleri.
- Kimliklerin ve kuralların oyunlar arasında karışmasını engelleyen ayrı PoE1 ve
  PoE2 ad alanları ile negatif testler.
- Sınırlandırılmış, yerel ve yalnızca biçim uyumluluğu sağlayan PoB1 içe aktarma
  ile ayrı biçimde beta olarak etiketlenen PoB2 okuyucu. Bunlar tam format
  uyumluluğu veya üretim oyun analizi anlamına gelmez.
- Varsayılan olarak reddeden Policy and Provenance Gate, sertleştirilmiş parser
  sınırları, güvenlik başlıkları, hız sınırları, sansürlenmiş loglar ve acil
  kapatma anahtarları.
- Sağlayıcıdan bağımsız isteğe bağlı AI Gateway ve varsayılan olarak kapalı
  OpenAI Responses adaptörü. Üretim çağrıları politika gereği kapalıdır; normal
  testler sahte sağlayıcılar ve deterministik geri dönüş kullanır.
- Özgün fixture sözlüğüyle sınanan deterministik öneri ve Manuel Trade Tarifi
  sözleşmeleri. Bu test düzenekleri gerçek oyun tavsiyesi değildir; canlı ilan
  veya fiyat sorgulamaz.
- Türkçe/İngilizce duyarlı fixture ekranları, sağlık/hazır olma uçları,
  tekrarlanabilir değerlendirmeler ve CI/üretim paketleme temelleri.

Kesin sürüm değerlendirmesi için [MVP hazırlık raporuna](docs/release/mvp-readiness.md),
tarihsel uygulama kaydı için [ilerleme belgesine](docs/progress.md) bakın.

## Planlananlar

- Kaynak izni, sürüm, checksum, parser uyumluluğu ve provenance bilgisi kesin
  olan değişmez bir PoE1 kurallarını onaylayıp yayımlamak.
- Dar kapsamlı ve yetkili bir PoE1 deterministik analiz/yükseltme önceliklendirme
  dilimini bağımsız olarak doğrulamak.
- Üretim analiz sayfalarını fixture verisi yerine kullanıcıya ait uygulama
  sonuçlarına bağlamak.
- Kuyruklu ham artifact aktarımından önce kalıcı nesne depolaması eklemek;
  ardından staging yedekleme/geri yükleme, gizlilik iletişimi ve hesap deneyimini
  tamamlamak.
- PoE2 analizini ancak PoE1 sürüm kapıları geçildikten ve ayrı bir etkinleştirme
  ADR'si kabul edildikten sonra değerlendirmek.
- Yalnızca güvenlik, politika, provenance, silme ve operasyon engelleri
  çözüldükten sonra yayımlamak.

## Oyun kapsamı

İlk hedef Path of Exile 1 analizidir. Sınırlandırılmış biçim okuyucusu vardır;
üretim kuralları ve yetkili analiz sonucu yoktur.

Path of Exile 2 için yalnızca ayrı bir beta biçim okuyucu vardır. Kuralları,
bulguları, önerileri ve tarifleri etkin değildir; oyunlar arası fallback yasaktır.

## Tasarım ilkeleri

- Üretken anlatımdan önce deterministik hesaplama.
- Güven iddiasından önce kesin kanıt ve kurallar kimliği.
- AI isteğe bağlıdır; oyun olgusu, modifier, filtre, fiyat, kaynak, URL veya öneri
  uyduramaz.
- Bilinmeyen ya da desteklenmeyen olgular tipli belirsizlik veya ret üretir.
- Scraping, belgelenmemiş Trade uçları, canlı piyasa indeksleme, tarayıcı/oyun
  istemcisi erişimi, otomasyon, overlay ve oturum çerezi toplama yoktur.
- Çekirdek iş akışı tüm AI sağlayıcıları kapalıyken, erişilemezken veya bütçe
  tükendiğinde kullanılabilir kalmalıdır.

## Mimari

- `src/Domain`: değişmez ve framework'ten bağımsız alan sözleşmeleri.
- `src/Application`: taşıma katmanından bağımsız use case, DTO ve portlar.
- `src/GameAdapters/PoE1` ve `src/GameAdapters/PoE2`: birbirinden yalıtılmış biçim
  ve oyun adaptörleri.
- `app/Modules`: Laravel HTTP, PostgreSQL, kuyruk, depolama, politika, kimlik ve
  isteğe bağlı sağlayıcı altyapısı.
- `resources/js`: Inertia/Vue sunumu; yetkili hesaplama yapmaz.

Sistem kaydı PostgreSQL'dir. Laravel cache ve queue soyutlamaları uygulamayı
çalışma ortamından ayırır. Yerel Docker ve self-hosted kurulumlar Redis/Horizon
kullanabilir. İlk staging hedefi; Frankfurt bölgesinde Laravel Cloud Starter,
Serverless PostgreSQL ve üretilen `*.laravel.cloud` alan adıdır. Valkey ve Cloud
kuyruk kaynakları yalnızca etkin bir özellik gerektirirse eklenir; Laravel Cloud
için Horizon zorunlu değildir.

[Modül haritası](docs/architecture/module-map.md), [sistem bağlamı](docs/architecture/system-context.md) ve [Laravel Cloud ADR'si](docs/adr/0014-laravel-cloud-staging.md) ayrıntıları içerir.

## Yerel kurulum

Gerekli temel: PHP 8.4, Composer 2, Node.js 24, npm 11, PostgreSQL ve PHP `dom`,
`zlib`, `pdo_pgsql` eklentileri. Commit edilmiş lock dosyaları belirleyicidir.

### Linux veya WSL2 üzerinde Docker

Docker Engine ve Compose v2 kurulduktan sonra:

```bash
cp .env.example .env
composer run setup:docker
composer run dev:docker
```

<http://localhost:8000> adresini açın. Yerel stack PostgreSQL, Redis ve Horizon
kullanır; veri servisleri loopback'e bağlanır ve adlandırılmış Docker volume'ları
kullanır.

### Host üzerinde kurulum

Yerel PostgreSQL ve Redis hazırken:

```bash
cp .env.example .env
composer run setup
composer run dev
```

Horizon `pcntl` ve `posix` gerektirir; Windows'ta WSL2/Docker veya yalnızca web akışını kullanın:

```powershell
composer run setup:windows
composer run dev:web
```

Özgün yapısal fixture'ı veritabanı, kuyruk, ağ veya AI olmadan çalıştırmak için:

```powershell
php artisan pob:import-fixture tests/Fixtures/Pob/poe1-minimal.xml
```

## Kalite kapıları

```powershell
composer validate --strict
composer audit
composer run format:check
composer run analyse
composer run test
npm ci
npm audit --audit-level=high
npm run lint
npm run typecheck
npm run test
npm run build
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/validate-docs.ps1
```

Ek kapılar: `composer run ci:guardrails`, `composer run test:architecture`,
`composer run test:parser-security`, `composer run test:policy-gate`,
`composer run eval:fast` ve `npm run test:e2e`.

## Dağıtım

İlk dağıtım, Laravel Cloud Starter üzerinde kilitli bir pre-alpha staging
ortamıdır. Mümkünse Frankfurt bölgesi, Cloud tarafından üretilen alan adı ve
Serverless PostgreSQL kullanılır. Aylık başlangıç hedefi 20 USD, mutlak tavan 25
USD'dir; bunlar işletmeci bütçeleridir, fatura garantisi değildir.

[Laravel Cloud kılavuzunu](docs/deployment/laravel-cloud.md) izleyin. Docker ve
Horizon paketlemesi yerel veya self-hosted kullanım için korunur; Laravel Cloud
gereksinimi değildir.

## Güvenlik, katkı ve lisans

Güvenlik açıklarını [SECURITY.md](SECURITY.md) içindeki özel süreçle iletin;
kimlik bilgilerini, özel dizilimleri, prompt'ları, çerezleri veya istismar
ayrıntılarını yayımlamayın. Değişiklikten önce [CONTRIBUTING.md](CONTRIBUTING.md)
belgesini okuyun. Kural ve kaynak değişiklikleri doğrulanmış izin/provenance gerektirir.

Lootwright'a özgü kod ve belgeler MIT lisanslıdır. [LICENSE-SCOPE.md](LICENSE-SCOPE.md),
GGG materyali, üçüncü taraf verileri ve kullanıcı girdileri dahil proje
lisansının kapsamadığı alanları açıklar. [Üçüncü taraf bildirimlerine](THIRD_PARTY_NOTICES.md)
da bakın.

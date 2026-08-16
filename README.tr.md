<<<<<<< HEAD
# Lootwright

[English](README.md)

Lootwright; Path of Exile karakter dizilimlerini ileride izlenebilir ve
deterministik biçimde analiz etmek, ayrıca insanlar tarafından uygulanabilecek
eşya arama planları üretmek amacıyla geliştirilen açık kaynaklı bir pre-alpha
temelidir. Proje, altyapıdan bağımsız bir PHP alan çekirdeğine ve Inertia/Vue
arayüzüne sahip Laravel 13 modüler monolitidir.

> This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

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
=======
Lootwright

Path of Exile için izlenebilir, yapay zekâ destekli build analiz ve item arama planlama aracı.

[English README](https://github.com/cryptopust/lootwright/blob/main/README.md)

Proje durumu: pre-alpha / mimari ve prototip aşaması.
Aşağıda anlatılan yetenekler hedeflenen ürünü tanımlar. Henüz çalışan bir üretim servisinin hazır olduğu anlamına gelmez.

Lootwright, Path of Exile build analizini ve item aramayı daha anlaşılır hale getirmek için tasarlanan açık kaynaklı bir web uygulamasıdır. Oyuncu Path of Building kodunu verir, ne yapmak istediğini anlatır; sistem deterministik bulgular, önceliklendirilmiş yükseltme planı ve resmi Trade sitesinde elle uygulanabilecek filtre reçeteleri üretir.

Proje önce Path of Exile 1 için geliştirilecek, PoE1 MVP kararlı hale geldikten sonra Path of Exile 2 ayrı adaptör ve ruleset ile desteklenecektir.

Lootwright oyun istemcisiyle etkileşime girmez; trade botu, overlay, piyasa indeksleyicisi veya oyun otomasyon aracı değildir.

This product isn't affiliated with or endorsed by Grinding Gear Games in any way.

Çözmek istediğimiz sorun

Path of Exile olağanüstü bir build özgürlüğü sunuyor. Ancak bu özgürlük ciddi bir bilgi problemine dönüşüyor:

Path of Building çok fazla veri gösteriyor fakat çoğu oyuncuya önce hangi sorunun çözülmesi gerektiğini söylemiyor.

Resmi Trade arayüzü güçlü olmasına rağmen build ihtiyacını doğru filtrelere çevirmek ciddi oyun bilgisi gerektiriyor.

Build rehberleri çoğu zaman bitmiş karakteri gösteriyor; hangi modların zorunlu, tercih edilebilir, değiştirilebilir veya bütçeye bağlı olduğunu yeterince açıklamıyor.

Yapay zekâ tarafından doğal dille verilen tavsiyeler ikna edici görünürken geçersiz modlar, eski kurallar veya yanlış oyun sürümüne ait öneriler içerebiliyor.

Lootwright oyuncunun kararını elinden almadan ve oyunu otomatikleştirmeden bu boşluğu kapatmayı amaçlar.

Lootwright ne yapmayı hedefliyor?

Oyuncu:

Path of Exile 1 veya Path of Exile 2 seçer.

Kendi isteğiyle PoB/PoB2 paylaşım kodunu veya item metnini yapıştırır.

Karakterini, hedef içeriği, yaşadığı sorunu, oyun tarzını ve bütçesini doğal dille anlatır.

Tespit edilen oyun sürümünü, patch/ruleset uyumunu ve desteklenmeyen verileri görür.

Sürüm kontrollü kurallara ve kaynak geçmişine dayanan deterministik build bulguları alır.

Dağınık öneriler yerine önceliklendirilmiş bir yükseltme planı görür.

İlgili ekipman slotları için geniş ve katı Trade filtre reçeteleri üretir.

Resmi Trade ana sayfasını açar ve filtreleri kendisi uygular.

Her önerinin neden üretildiğini, hangi kurala dayandığını ve sistemin ne kadar emin olduğunu inceleyebilir.

Örnek

91 seviye, armour ve evasion kullanan bir Scion oynuyorum.
Hedefim derin Delve; boss hasarım düşük ve 50 Divine Orb bütçem var.
Mageblood kalsın, bütün karakteri baştan kurmak istemiyorum.

Lootwright, kullanıcının verdiği PoB koduyla bu isteği birleştirerek kısıtları belirlemeli, build çakışmalarını ve zayıf noktaları tespit etmeli, yükseltmeleri bağımlılık ve beklenen etkiye göre sıralamalı, ilgili slotlar için manuel item arama reçeteleri hazırlamalıdır.

İstek doğal dille yazıldığı için mod, fiyat, item, Trade kimliği veya hesaplama uyduramaz.

Lootwright ne yapmayacak?

Lootwright özellikle şu işler için tasarlanmamıştır:

oyun belleğini, dosyalarını, loglarını, ekran görüntüsünü veya ağ trafiğini okumak ya da değiştirmek;

Path of Exile istemcisini incelemek veya kontrol etmek;

arka planda clipboard okumak;

klavye, fare, oyun, chat, whisper, davet, satın alma veya party işlemlerini otomatikleştirmek;

POESESSID, Path of Exile parolası, tarayıcı çerezi veya oturum bilgisi toplamak;

resmi siteyi, forumları, Trade sayfalarını veya üçüncü taraf build sitelerini scrape etmek;

dokümante edilmemiş GGG endpoint'lerini çağırmak veya tersine mühendislik yapmak;

canlı Trade ilanlarını çekmek, saklamak, takip etmek, sıralamak veya yeniden yayımlamak;

dokümante edilmemiş istek formatlarından kodlanmış Trade araması üretmek;

fiyat kontrol overlay'i veya trade botu gibi çalışmak;

bir buildin kesinlikle en iyi, ölümsüz veya kârlı olduğunu iddia etmek;

bağışçıların veya sponsorların önerileri etkilemesine izin vermek.

Sistem nasıl tasarlanıyor?

Lootwright oyun verisini, deterministik analizi ve yapay zekâ tarafından üretilen dili birbirinden ayırır.

flowchart LR
    A[Oyuncunun hedefi ve bütçesi] --> B[Niyet çıkarımı]
    C[Kullanıcının verdiği PoB veya item metni] --> D[Güvenli ayrıştırıcı]
    B --> E[Canonical build niyeti]
    D --> F[Canonical build görüntüsü]
    G[Sürüm kontrollü PoE ruleset] --> H[Deterministik analiz motoru]
    E --> H
    F --> H
    H --> I[Bulgular ve yükseltme öncelikleri]
    I --> J[Manuel Trade filtre reçeteleri]
    I --> K[İsteğe bağlı AI açıklaması]
    J --> L[Oyuncu resmi Trade sitesini elle kullanır]

Deterministik çekirdek

Doğruluğun kaynağı analiz motorudur. Aynı build, ruleset ve motor sürümü aynı normalize sonucu üretmelidir. Kabul edilen her bulgu ve öneride şunlar bulunur:

etkilenen skill, item, pasif veya build özelliği;

deterministik kanıt;

kullanılan kural ve ruleset sürümü;

veri kaynağı ve geçmişi;

güven seviyesi ve desteklenmeyen veri işaretleri;

problem ile öneriyi birbirine bağlayan açıklama izi.

Path of Exile 1 ve Path of Exile 2 ayrı adaptör ve ruleset kullanır. PoE1 kimliği veya varsayımı PoE2 analizine karışamaz.

Sınırlandırılmış yapay zekâ rolü

Yapay zekâ isteğe bağlıdır. Planlanan OpenAI entegrasyonu Responses API ve Structured Outputs kullanarak yalnızca iki sınırlı işi yapar:

oyuncunun doğal dilde yazdığı isteği tip güvenli bir niyet adayına çevirmek;

deterministik motorun zaten ürettiği önerileri anlaşılır dille açıklamak.

Yapay zekâ canonical oyun verisi üretemez, entegrasyona izin veremez, politika kararını geçersiz kılamaz veya deterministik sonuçta olmayan bir öneriyi ekleyemez. OpenAI kapalı ya da erişilemezken uygulama çalışmaya devam etmelidir.

Manuel Trade reçeteleri

Lootwright ilgili her item slotu için şunları hazırlayabilir:

zorunlu modlar ve minimum değerler;

ağırlıklandırılmış isteğe bağlı modlar;

çakışan veya hariç tutulacak modlar;

uygulanabildiği durumlarda base, rarity, influence ve corruption kısıtları;

geniş arama ve daha katı alternatif;

diğer ekipman slotlarına olan bağımlılıklar;

her filtrenin nedeni ve kaynak izi.

Filtreleri girmek, ilanları incelemek, satıcıyla iletişim kurmak ve bütün işlemleri elle tamamlamak oyuncunun sorumluluğundadır.

Veri ve entegrasyon politikası

Her dış kaynak ve yetenek, varsayılan olarak reddeden Policy and Provenance Gate tarafından değerlendirilir.

Erişim, saklama, yeniden dağıtım veya ticari kullanım izni eksik, süresi dolmuş, çelişkili ya da belirsizse entegrasyon kapalı kalır. Bir kaynağın teknik olarak herkese açık olması yeniden kullanım izni verildiği anlamına gelmez.

İlk ürün akışı bu nedenle şunlara dayanır:

oyuncunun açıkça verdiği PoB/PoB2 kodu ve item metni;

kaynağı, checksum'ı, parser sürümü ve izin kanıtı kayıtlı yerel ruleset içe aktarımları;

yalnızca geçerli uygulama erişimi, scope, kimlik bilgisi ve güncel politika kanıtı bulunduğunda dokümante edilmiş GGG API'leri;

Lootwright'a ait özgün arayüz varlıkları ve test fixture'ları.

Dokümante edilmemiş Trade endpoint'leri, uzaktan pobb.in çekme, forum scraping, GGG görselleri ve yayıncıya ait veri setleri varsayılan olarak etkin değildir.

Gizlilik ve güvenlik ilkeleri

Yalnızca analiz için gerekli veriyi toplamak.

PoB XML, notlar, item metni ve karakter isimlerini güvenilmeyen girdi kabul etmek.

Harici XML entity'lerini, DTD yüklemeyi ve sınırsız decompression işlemini kapatmak.

Özel build kodlarını ve API anahtarlarını varsayılan olarak loglamamak.

Gelişigüzel PoB notlarını veya gereksiz kişisel verileri AI sağlayıcısına göndermemek.

Saklama süresi kontrolü, taşınabilir dışa aktarma ve silme sağlamak.

Dış ağ erişimini sınırlamak ve açık allowlist istemek.

Kullanıcı, IP, günlük ve global AI kullanım limitleri uygulamak.

AI ve her dış entegrasyon için ayrı acil kapatma anahtarı bulundurmak.

Teknoloji ve mimari

Planlanan uygulama pragmatik bir modular monolith olacaktır:

Alan

Teknoloji / yaklaşım

Backend

Laravel 13, PHP

Frontend

Inertia 3, Vue 3, TypeScript

Arayüz

Tailwind CSS, shadcn-vue, özgün Lootwright tasarım sistemi

Veritabanı

PostgreSQL

Cache ve kuyruk

Redis, Laravel Horizon

Mimari

Domain-driven modular monolith

AI

Sağlayıcıdan bağımsız gateway; isteğe bağlı OpenAI Responses API adaptörü

AI çıktısı

Deterministik doğrulamalı Strict Structured Outputs

Build girdisi

Kullanıcının verdiği, güvenli biçimde decode ve normalize edilen PoB/PoB2 verisi

Trade çıktısı

Manuel filtre reçetesi; canlı ilan çekme yok

Domain ve analiz motoru Laravel, Eloquent, HTTP, arayüz veya belirli bir AI sağlayıcısına bağımlı olamaz.

Teslimat planı

Faz 1 — PoE1 temeli

proje anayasası, tehdit modeli ve kaynak kayıt sistemi;

Laravel uygulama temeli;

güvenli PoB içe aktarıcı;

sürüm kontrollü PoE1 ruleset aktarımı;

deterministik analiz motoru;

manuel Trade filtre reçeteleri;

Türkçe ve İngilizce arayüz temeli.

Faz 2 — Kullanılabilir PoE1 MVP

önceliklendirilmiş build bulguları ve yükseltme planları;

mapping, bossing, Delve, Simulacrum, Sanctum ve progression hedefleri için açıklanabilir kontroller;

isteğe bağlı, bütçe kontrollü AI niyet çıkarımı ve açıklamaları;

gizlilik, silme, dışa aktarma, eval, güvenlik sıkılaştırması ve üretim operasyonları.

Faz 3 — PoE2 adaptörü

ayrı PoB2 ayrıştırıcı uyumluluğu;

bağımsız PoE2 ruleset ve analiz kuralları;

PoE1 varsayımlarının PoE2'ye sızmasını önleyen sürüme özgü testler;

gereken filtre sözlüğünün onaylı kaynak geçmişi bulunduğunda PoE2 manuel Trade reçeteleri.

Gelecekteki entegrasyonlar

Yeni bir entegrasyon yalnızca dokümante edilmişse, izin kanıtı kaydedilmişse ve Policy Gate gerekli yeteneklere izin veriyorsa değerlendirilir. Teknik olarak yapılabiliyor olması tek başına yeterli değildir.

Güncel durum

Lootwright şu anda mimari ve prototip aşamasındadır.

Ürün problemi ve sınırları tanımlandı

Yalnızca PoE kapsamı seçildi

PoE1-first teslimat planı belirlendi

Deterministik çekirdek ve sınırlı AI mimarisi tanımlandı

GGG entegrasyon sınırları belgelendi

Codex CLI geliştirme sırası hazırlandı

Uygulama iskeleti

Güvenli PoB parser

Sürüm kontrollü ruleset aktarımı

Deterministik analiz MVP'si

Manuel Trade reçeteleri

Herkese açık pre-alpha

Uygulama planı lootwright-poe-codex-cli-prompts.md dosyasında bulunuyor.

Bağış ve sponsorluk

Lootwright'ın ücretli oyun avantajı yaratmadan açık kaynak ve erişilebilir kalması amaçlanmaktadır.

İleride gönüllü destek; hosting, güvenlik, gözlemlenebilirlik ve AI maliyetlerini karşılamak için kullanılabilir. İlgili politika ve hukuki sorular değerlendirilene kadar bağış sistemi varsayılan olarak kapalıdır. Daha sonra etkinleştirilirse:

bağışlar özellik veya AI kotası açmayacak;

öneriler bağışçı veya sponsorlardan etkilenmeyecek;

ücretli sıralama veya sponsorlu item gösterimi olmayacak;

operasyon maliyetleri ve maddi sponsorluklar açıklanacak;

proje GGG veya OpenAI tarafından desteklendiğini iddia etmeyecek.

Mimari; sıkı token bütçesi, cache, deterministik fallback ve AI kapalı çalışma desteği de içerir. Böylece proje sınırsız API bütçesine bağımlı olmaz.

Katkıda bulunma

Katkı rehberi, davranış kuralları, güvenlik bildirimi ve nihai kaynak kod lisansı ilk herkese açık geliştirme sürümünden önce eklenecektir.

Bu aşamaya kadar önerilen değişikliklerin şu değişmez ilkeleri koruması gerekir:

Oyun istemcisi etkileşimi ve oyun otomasyonu yok.

Dokümante edilmemiş GGG endpoint'i ve scraping yok.

Kaynağı izlenemeyen öneri veya kabul edilen AI halüsinasyonu yok.

PoE1 ve PoE2 arasında veri sızıntısı yok.

Para karşılığı özellik avantajı yok.

Kayıtlı izin ve kaynak geçmişi olmayan üçüncü taraf veri yok.

Hukuki ve marka bildirimi

Lootwright bağımsız bir topluluk projesidir. Grinding Gear Games tarafından geliştirilmemiş, yetkilendirilmemiş, desteklenmemiş veya onaylanmamıştır.

Path of Exile ve Path of Exile 2'nin isimleri, oyun verileri, görselleri, karakterleri, itemları ve ilgili fikrî mülkiyeti Grinding Gear Games ile ilgili hak sahiplerine aittir. Lootwright kaynak kod lisansı yalnızca Lootwright'a ait özgün kod ve varlıkları kapsayacak; üçüncü taraf veya yayıncıya ait materyaller üzerinde hak vermeyecektir.

Proje güncel Path of Exile Developer Docs ve Kullanım Şartlarına uymalıdır. Politikalar veya izinler değişirse bir yetenek değiştirilebilir ya da kapatılabilir.

OpenAI isteğe bağlı bir teknoloji sağlayıcısıdır; proje sponsoru veya destekçisi değildir. Planlanan entegrasyon resmi Responses API ve Structured Outputs belgelerini takip eder.
>>>>>>> 36a0ecec9757fafa9d63883d2c3de85e917077c7

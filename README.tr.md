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

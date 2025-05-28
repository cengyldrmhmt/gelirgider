sen bir yazılım mühendisisin. ve senden gelir gider web sitesi yapmanı istiyorum 
composer kullanılmayacak vendor olmasın.

php 8.0
ve mysql kullancaksın.

veritabanı için sql oluşturacaksın.

bütün sayfalar modüler olacak.

kullanıcı kayıt olma ve girişi olacak.
admin içinde olacak. aynı zamanda admin paneli olacak.

Kullanılan tablolar datatable olacak.

koyu ve açık tema olacak.

🧩 Ana Bileşenler ve Sayfalar
1. Kullanıcı Yönetimi
Kayıt Ol (Ad, Soyad, E-posta, Şifre, vb.)

Giriş Yap / Çıkış Yap

Şifre Sıfırlama

Kullanıcı Profili (Profil bilgileri düzenleme)

2. Ana Sayfa / Dashboard
Genel Bakiye Gösterimi

Aylık/günlük/haftalık/custom Gelir-Gider Grafiği

Son Eklenen İşlemler

Toplam Gelir / Gider / Net Durum

3. Gelir ve Gider Sayfaları
Gelir Ekle

Tutar

Kategori (Maaş, Ek Gelir, Satış, vb.)

Açıklama

Tarih

Gider Ekle

Tutar

Kategori (Kira, Market, Fatura, vb.)

Açıklama

Tarih

Gelir/Gider Listesi

Filtreleme (Tarih, Kategori, Tutar)

Arama

Silme / Düzenleme işlemleri

4. Kategori Yönetimi 
Kategori Ekle / Sil / Düzenle

Gelir Kategorileri

Gider Kategorileri

5. Raporlama Sayfası
Aylık/günlük/haftalık/custom Grafikler

Kategori Bazlı Harcama Dağılımı (Pie Chart)

PDF/Excel İndir Butonları

Gelir vs. Gider Karşılaştırma

6. Bildirimler 
Belirli bir harcama limiti aşıldığında uyarı

Aylık özet raporları

7. Ayarlar Sayfası
Para birimi seçimi (TL, USD, EUR)

Tema (Açık/Koyu)

Şifre değiştirme

Site ayarları

8. Admin Paneli 
Tüm kullanıcıların işlemleri

Kategori yönetimi

Sistem istatistikleri

Yedekleme / Loglama

🔧 Teknik Bileşenler (backend/frontend)
Backend (örnek):
PHP 8.0

MySQL 



Frontend:
HTML, CSS, Bootstrap

JavaScript 

Chart.js / ApexCharts (grafikler için)

DataTables (listeleme için)


Temel Özellikler
1. Çoklu Cüzdan Yönetimi
Nakit, banka hesabı, kredi kartı, tasarruf, yatırım gibi farklı cüzdan türleri oluşturabilme.

Her cüzdan için ayrı bakiye takibi.

Cüzdanlar arası transfer işlemleri.

2. Gelir ve Gider Takibi
Manuel veya otomatik (banka entegrasyonu ile) işlem ekleme.

İşlemlere kategori, etiket, açıklama, tarih ve saat ekleyebilme.

Tekrarlayan işlemleri planlayabilme (örneğin, aylık kira ödemesi).

3. Bütçe Oluşturma ve Takibi
Kategori bazlı günlük, haftalık, aylık veya yıllık bütçeler belirleyebilme.

Bütçe aşımı durumunda uyarı bildirimleri.

Bütçe performansını grafiklerle izleyebilme.

4. Raporlama ve Analiz
Gelir ve giderlerin grafiksel gösterimi (pasta grafik, çubuk grafik vb.).

Kategori bazlı harcama analizleri.

Zaman aralığına göre filtreleme (günlük, haftalık, aylık, yıllık).

5. Planlı Ödemeler ve Hatırlatıcılar
Gelecek ödemeler için planlama yapabilme.

Ödeme tarihleri yaklaşırken bildirim alma.

Otomatik ödeme işlemleri oluşturabilme.

6. Veri İçe ve Dışa Aktarma
CSV, Excel veya PDF formatlarında veri dışa aktarma.

Banka hesap hareketlerini içe aktarma.

Veri yedekleme ve geri yükleme seçenekleri.
Money Manager Android
+3
Google Play
+3
Google Play
+3

7. Kullanıcı ve Güvenlik Yönetimi
Çoklu kullanıcı desteği.

Kullanıcılar arası cüzdan paylaşımı (örneğin, aile bütçesi).

Şifreleme ve iki faktörlü kimlik doğrulama seçenekleri.

🎨 Kullanıcı Arayüzü ve Deneyimi
1. Dashboard (Kontrol Paneli)
Toplam bakiye, gelir ve gider özetleri.

Son işlemler listesi.

Bütçe durumu ve uyarılar.

2. Takvim Görünümü
İşlemlerin takvim üzerinde gösterimi.

Planlı ödemelerin ve tekrarlayan işlemlerin takibi.

3. Tema ve Dil Seçenekleri
Açık ve koyu tema seçenekleri.

Çoklu dil desteği.

4. Mobil Uyumlu Tasarım
Responsive tasarım ile mobil cihazlarda sorunsuz kullanım.

Mobil uygulama entegrasyonu (isteğe bağlı).

🧩 Ekstra Özellikler
1. Fotoğraf ve Belge Ekleme
İşlemlere fiş, fatura veya diğer belgeleri ekleyebilme.

2. Etiketleme ve Notlar
İşlemlere özel etiketler ve notlar ekleyerek detaylı arama ve filtreleme imkanı.

3. Döviz ve Kripto Para Desteği
Farklı para birimlerinde işlem yapabilme.

Kripto para cüzdanlarının entegrasyonu ve takibi.

4. Otomatik Kategorilendirme
İşlemlerin otomatik olarak uygun kategorilere atanması.

Kullanıcının düzenlemelerine göre öğrenme ve uyum sağlama.

🛠️ Teknik Özellikler
1. Veritabanı Yapısı (MySQL)
users: Kullanıcı bilgileri.

wallets: Cüzdan bilgileri.

transactions: İşlem kayıtları.

categories: Gelir ve gider kategorileri.

budgets: Bütçe tanımlamaları.

scheduled_payments: Planlı ödeme bilgileri.
Money Manager Android
+3
budgetbakers.com
+3
Medium
+3

2. Backend (PHP 8.0)
MVC mimarisi kullanımı.

RESTful API'ler ile frontend entegrasyonu.

Güvenlik için CSRF ve XSS korumaları.

3. Frontend
HTML5, CSS3, JavaScript (jQuery veya modern frameworkler).

Bootstrap veya Tailwind CSS ile responsive tasarım.

Chart.js veya ApexCharts ile grafik gösterimleri.


 KULLANICI & GÜVENLİK ÖZELLİKLERİ
2FA (Two-Factor Authentication)

E-posta doğrulama

Oturum yönetimi (session timeout, IP log)

Premium hesap yönetimi

Şifreli veri depolama (wallet bazlı)

Activity log tablosu: tüm işlemler için zaman damgalı kayıtlar

💰 İŞLEM VE CÜZDAN ÖZELLİKLERİ
Sınırsız sayıda cüzdan

Farklı para birimleri ve otomatik kur dönüşümü

Cüzdanlar arası transfer

Ortak cüzdan kullanımı (paylaşım davetleri)

İşlemlere konum, fiş/fotoğraf ekleme

İşlemleri kategori + alt kategori ile etiketleme

Tekrarlayan işlemler (örneğin: maaş, fatura)

"Hızlı işlem" şablonları (örnek: "Her ay Netflix")

📊 RAPORLAR & ANALİZLER
Tarih aralığına göre:

Toplam gelir, gider, net fark

Günlük / haftalık / aylık değişim grafikleri

Kategori bazlı harcama oranları (pasta grafik)

Bütçe vs. harcama karşılaştırması

En çok harcanan kategori listesi

Raporları CSV, Excel, PDF olarak dışa aktar

📅 TAKVİM & HATIRLATICI ÖZELLİKLERİ
Takvim görünümü ile işlem takibi

Günü geçmiş işlem bildirimleri

Yaklaşan ödeme hatırlatıcıları

Planlanan ödemeler için takvim entegrasyonu (.ics desteği)

📈 BÜTÇE VE HEDEF YÖNETİMİ
Her kategoriye özel bütçe belirleme

Haftalık, aylık, dönemsel bütçeler

Bütçe doluluğuna göre renkli göstergeler

Uyarı sistemi (örn: %90’a ulaşıldı bildirimi)

Tasarruf hedefleri: örn. "Yeni Telefon - ₺15.000"

🌍 DİĞER GELİŞMİŞ ÖZELLİKLER
Döviz & Kripto Para Desteği
Gerçek zamanlı döviz kuru (CoinGecko API)

Kripto cüzdanlar ve bakiyeler (Bitcoin, ETH, USDT)

Fiyat değişim uyarıları (%10 düşüş/çıkış)

Yapay Zeka & Otomasyon
Banka ekstresi yükleme → AI kategorilendirme

Harcama alışkanlığı analizi

Tahmini gider raporları: "Gelecek ay ₺X kira, ₺Y market"

Etiketleme Sistemi
Kullanıcı tanımlı etiketler (örn: #tatil, #ev)

Etikete göre arama ve raporlama

📱 ARAYÜZ & UX
Mobil uyumlu responsive tasarım (Bootstrap/Tailwind)

Koyu/açık mod

Ana sayfa: Toplam Bakiye, Hedefler, Son işlemler

Çoklu dil desteği (TR, EN, AR, RU, …)

Sesli işlem girişi (isteğe bağlı JS Speech API)

Kategori ikon kütüphanesi (FontAwesome, Fluent Icons)


🧠 AKILLI ÖNERİ MOTORU & ANALİTİK SİSTEMİ
📌 Davranışsal Uyarılar
“Bu ay restoran harcamalarınız geçen aya göre %32 arttı.”

“3 aydır kira ödemenizi ₺X olarak sabit tuttunuz, bu ay ₺Y olmuş. Artış tespiti!”

“Market harcamalarınız 5 aydır düzenli artıyor. Dikkatli olun.”

🔁 Unutulan Tekrarlayan İşlem Algılama
Geçen ay tekrarlanan bir gider (örneğin: kira, aidat) bu ay yoksa sistem:

“Geçen ay 5’inde ₺350 aidat ödemiştiniz. Bu ay henüz girmediniz.” uyarısı verir.

Eğer bir işlem birkaç ay manuel girildiyse, sistem bunu kalıp olarak tanır ve otomatik hale getirmeyi önerir.

🕰️ Geçmişe Dayalı Karşılaştırmalar
“Geçen yıl Mayıs ayında toplam ₺4.200 harcamıştınız. Bu yıl ₺6.100. +%45 artış.”

“Son 6 ayın ortalaması ₺3.900, bu ay ₺5.800 harcadınız.”

Grafiksel gösterim: yıllık harcama trendleri

🧾 Harcama Tahminleri (Yapay Zeka ile)
“Bu harcama alışkanlıklarınızla, bu ay sonuna kadar ₺12.400 harcamanız öngörülüyor.”

“Son 3 aydaki veriye göre, ayın 20’sinde ₺X tutarında aidat ödemeniz bekleniyor.”

📈 AKILLI RAPORLAR & UYARILAR
🔔 Otomatik Bildirimler
Harcama bütçesine yaklaşınca anlık uyarı

Kredi kartı ekstresi günü yaklaşınca hatırlatma

Beklenmeyen yüksek harcama sonrası sistemden öneri

Kur farkı uyarıları: “₺ zayıflıyor, döviz borcunuz artabilir”

💬 Yapay Zeka Destekli Yorumlama
“Geçen ay eğlence harcamalarınızı %50 azalttınız, tebrikler!”

“Toplu taşıma giderleriniz ortalamanın altında.”

“Nakit kullanım oranınız düştü, daha fazla banka işlemi yapıyorsunuz.”

📅 Ödeme Tarihi Tahmini
Sistem, ödeme tarihlerinizi analiz ederek:

“Elektrik faturası genelde ayın 10’unda ödeniyor. Bu ay unutmayın.”

🎯 HEDEF VE TASARRUF ODAKLI SİSTEM
Hedef Bazlı Harcama Denetimi
Hedefiniz olan “₺30.000 tatil” için:

Harcamalarınızı kısıtlamanız gerektiğinde sistem sizi uyarır.

“Bu hafta plan dışı harcamalarla hedefinizden uzaklaştınız.”

Önerilen Hedefler
“Geçmiş harcamalarınıza göre aylık ₺500 kenara koyarak 6 ayda ₺3.000 biriktirebilirsiniz.”

🔍 ARAMA & FİLTRELEME YETENEKLERİ
AI ile doğal dilde arama:

“Ocak ayında markete ne kadar harcadım?”

“Nakitle yapılan yemek harcamalarını göster.”

Etiket, kategori, cüzdan, tarih filtreli rapor üretimi

🧮 GELİŞMİŞ DÖVİZ/KRİPTO TAKİBİ
API ile anlık kur bilgisi (USD, EUR, BTC, ETH, USDT)

Otomatik dönüşüm: farklı dövizde işlem girildiğinde varsayılan para birimine çeviri

Kripto portföy izleyici: varlık miktarına göre değer takibi

Cüzdan değerindeki % değişime göre renkli uyarı: 🔴 %–15 | 🟢 %+20

📤 DIŞA AKTARMA / YEDEKLEME
Tek tıklamayla: CSV, XLSX, PDF, JSON yedekleme

Otomatik Google Drive/Dropbox yedekleme (isteğe bağlı cron job)

Veritabanı otomatik günlük yedekleme

🧩 GELİŞMİŞ TEKNİK ÖZELLİKLER (PHP 8.0 UYUMLU)
Tam modüler yapı: controller, model, view (MVC)

.env dosyasıyla konfigürasyon

Her bileşen için hata loglama (error.log)

Locale ayarları: tarih biçimi, para birimi, ondalık ayırıcısı

Cron job destekli işlemler (planlı bildirimler, tekrar eden işlemler)

MySQL foreign key’lerle veri bütünlüğü


🧩 Kullanılacak Teknolojiler
Amaç	Teknoloji
Arayüz	Bootstrap 5, Tailwind CSS (isteğe bağlı)
Etkileşim	Vanilla JS, jQuery (bazı UI bileşenleri için)
Zaman / Tarih	Flatpickr, Moment.js
Grafikler	Chart.js veya ApexCharts
Otomatik öneri/arama	Select2, Awesomplete
Döviz/Kur verisi	ExchangeRate-API veya CoinGecko API
Sayfa geçişleri	AJAX + jQuery
Bildirimler	Toastr.js, SweetAlert2
Responsive yapı	Bootstrap Grid veya Tailwind Utility Classes

🖱️ Butonlar
🎨 Stil Özellikleri:
Bootstrap 5 btn, btn-primary, btn-outline-* sınıfları

Yumuşak gölgeler (box-shadow)

Rounded-xl köşeler

Hover animasyonu (color transition)

Örnek:

html
Kopyala
Düzenle
<button class="btn btn-primary shadow-sm rounded-pill px-4 py-2">Gelir Ekle</button>
🧠 Akıllı Butonlar:
“Bugünkü tüm tekrar eden giderleri ekle” (otomatik işlem başlatır)

“Ay sonu raporu oluştur” (PDF export başlatır)

“Tahmini harcama göster” (AI tahmin penceresi açar)

📝 TextBox (Input)
📌 Özellikler:
Placeholder destekli (placeholder="₺0.00")

Maskelenmiş giriş (örneğin para → inputmask kullan)

Hata durumunda otomatik kırmızı kenarlık + tooltip

Arkaplan: soft gri (#f9f9f9), border: 1px solid #ccc

Otomatik dropdown öneri: geçmiş girişler listelenir (via localStorage veya sunucu)

Örnek:
html
Kopyala
Düzenle
<input type="text" class="form-control money-input" placeholder="₺0.00" />
🔽 ComboBox (Select + Arama)
Kullanılacak: Select2 veya Tom Select
Özellikleri:
Canlı arama desteği

Çoklu seçim (etiket bazlı)

Dinamik veri çekme (AJAX ile örneğin: harcama kategorileri)

Kategorilere göre gruplama

İconlu seçenekler (örneğin market → 🛒)

Örnek:
html
Kopyala
Düzenle
<select class="form-select select2" multiple>
  <option value="market">🛒 Market</option>
  <option value="kira">🏠 Kira</option>
</select>
📊 Raporlama Paneli
Kullan:
Chart.js → Pasta grafiği (kategori bazlı gider)

Line chart (aylık gider trendi)

Radar chart (tasarruf alışkanlıkları)

Filtreler:
Tarih aralığı: from – to (flatpickr ile)

Cüzdan türü: Nakit, Kart, Kripto

Para birimi filtreleme

📅 Takvim / Hatırlatıcı
Özellikler:
Flatpickr ile tarih ve saat seçici

Tekrarlayan işlem işaretleyici (her hafta, ayın 5’i, vb.)

Yaklaşan harcamalar takvimi

Günlük harcama hedefi için renkli gösterge

📲 Mobil Uyumluluk
UX İyileştirmeleri:
Butonlar mobilde daha büyük padding

Mobilde ComboBox yerine modal seçim ekranı

Dashboard’da swipe navigasyon

“Quick Add” yüzen buton (FAB) — örn. “+ Gelir”, “+ Gider”

🔐 Güvenlik & UX İyileştirmeleri
CSRF token sistemi

Otomatik logout: 15 dakika pasiflikte

Şifre gücü göstergesi

Modal ile giriş/kayıt penceresi

Email doğrulama + 2FA (opsiyonel)


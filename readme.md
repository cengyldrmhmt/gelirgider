## Frontend Yapısı

Frontend dosyaları `public/` dizini altında organize edilmiştir:

```
public/
├── css/
│   ├── dashboard/
│   │   └── style.css
│   └── transactions/
│       └── style.css
├── js/
│   ├── dashboard/
│   │   └── script.js
│   └── transactions/
│       └── script.js
├── images/
└── assets/
```

### CSS ve JavaScript Organizasyonu

Her view için ayrı CSS ve JavaScript dosyaları oluşturulmuştur:

- CSS dosyaları: `public/css/{view_name}/style.css`
- JavaScript dosyaları: `public/js/{view_name}/script.js`

View dosyalarında CSS ve JavaScript dosyalarını dahil etmek için:

```php
// CSS ve JS dosyalarını ekle
echo '<link rel="stylesheet" href="/gelirgider/public/css/{view_name}/style.css">';
echo '<script src="/gelirgider/public/js/{view_name}/script.js" defer></script>';
```

### Kurallar

1. Her view için ayrı CSS ve JavaScript dosyaları oluşturulmalıdır
2. CSS dosyaları `style.css` olarak adlandırılmalıdır
3. JavaScript dosyaları `script.js` olarak adlandırılmalıdır
4. Dosyalar ilgili view klasörü altında tutulmalıdır
5. Tüm CSS ve JavaScript kodları ilgili dosyalara taşınmalıdır
6. View dosyalarında sadece gerekli bağlantılar bulunmalıdır 

## Kod Organizasyonu ve Modernizasyon Notları

### 2024 Geliştirme Güncellemeleri

#### 1. CSS ve JavaScript Ayrıştırması
Tüm view dosyalarındaki (ör: dashboard, transactions, wallets, credit-cards, categories, budgets, reports, analytics, settings, auth, scheduled_payments, profile, payment_plans, notifications, financial_goals, admin) inline CSS ve JavaScript kodları kaldırılmıştır. Her view için aşağıdaki gibi ayrı dosyalar oluşturulmuştur:

- CSS: `public/css/{view_adi}/style.css`
- JavaScript: `public/js/{view_adi}/script.js`

View dosyalarında sadece ilgili CSS ve JS dosyalarına bağlantı eklenmiştir. Örnek:
```php
<link rel="stylesheet" href="/gelirgider/public/css/admin/style.css">
<script src="/gelirgider/public/js/admin/script.js"></script>
```

#### 2. Fonksiyon Açıklamaları
Tüm JavaScript dosyalarında, fonksiyonların başına Türkçe olarak ne işe yaradıklarını açıklayan yorum satırları eklenmiştir. Örnek:
```js
// Veritabanı yedeği alma fonksiyonu
function backupDatabase() { ... }
```

#### 3. Kod Düzeni ve Sürdürülebilirlik
- Kodun okunabilirliği ve sürdürülebilirliği için tüm stiller ve scriptler modüler olarak ayrılmıştır.
- Her view için ilgili CSS ve JS dosyası dışında view dosyasında stil veya script bulunmaz.
- Tüm DataTable, modal, AJAX ve dinamik işlemler ilgili view'ın kendi JS dosyasında yönetilir.
- Tüm stiller ilgili view'ın kendi CSS dosyasında tutulur.

#### 4. Admin Paneli
- `app/views/admin/index.php` dosyasındaki tüm inline CSS ve JS kodları kaldırılmıştır.
- `public/css/admin/style.css` ve `public/js/admin/script.js` dosyaları oluşturulmuştur.
- `public/js/admin/script.js` içindeki her fonksiyonun başına açıklama eklenmiştir.

#### 5. Diğer View'lar
- Tüm view klasörlerinde aynı yapı uygulanmıştır (ör: profile, scheduled_payments, payment_plans, notifications, financial_goals, vb.).
- Kodun güncel ve sürdürülebilir olması için bu yapı standart hale getirilmiştir.

#### 6. Silinen Dosyalar
- Eski, inline veya gereksiz dosyalar silinmiştir: `public/js/dashboard.js`, `public/css/transactions.css`, `public/js/transactions.js` vb.

#### 7. Katkı ve Geliştirme
- Yeni view ekleyecek olanlar, aynı yapıyı takip ederek ilgili view için ayrı CSS ve JS dosyası oluşturmalıdır.
- Fonksiyonlara açıklama eklenmesi zorunludur.

---

Daha fazla bilgi veya örnek için kodun ilgili view klasörlerine bakabilirsiniz. 
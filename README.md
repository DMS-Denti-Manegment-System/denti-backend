# Denti

Laravel 12 tabanlı, çok şirketli diş kliniği stok ve operasyon yönetim sistemi. Mevcut web arayüzü `Metronic 8 Demo 14` Blade yapısı üzerinden çalışır; eski React/Inertia yapısı artık aktif değildir.

## Güncel Stack

- Backend: PHP 8.2+, Laravel 12, Sanctum, Spatie Permission
- Frontend: Blade, Metronic 8 Demo 14 assetleri, Vite, Tailwind 4
- Veritabanı: MySQL 8+ önerilir
- Test: PHPUnit 11, Playwright

## Temel Modüller

- Kimlik doğrulama: şirket/klinik kodu ile giriş, super admin girişi, 2FA akışı
- Stok yönetimi: ürün, batch, alt birim, son kullanma tarihi, manuel stok düzeltme
- Operasyonlar: klinikler, tedarikçiler, personel, roller, todo, stok talepleri
- Uyarılar: düşük stok, kritik stok, SKT yaklaşan ve süresi geçmiş ürünler
- Çok şirketli mimari: `company_id` bazlı tenant izolasyonu

## Kurulum

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

Geliştirme için:

```bash
composer run dev
```

Bu komut Laravel server, queue listener, log stream ve Vite dev server'ı birlikte başlatır.

## Giriş

Demo kullanıcıları seed durumuna göre değişebilir. Web giriş ekranı:

- Kullanıcı girişi: `/login`
- Sistem yöneticisi girişi: `/admin/login`

API korumalı endpoint'leri `auth:sanctum` ve ek olarak izin middleware'leri ile korunur.

## Dizinler

- `app/Http/Controllers/Web`: Metronic Blade sayfaları
- `app/Http/Controllers/Api`: JSON API uçları
- `app/Services`: iş kuralları
- `app/Repositories`: sorgu ve veri erişim katmanı
- `resources/views`: Blade ekranları
- `public/metronic`: tema assetleri
- `tests`: unit, feature, e2e testleri

## Test ve Kalite

```bash
php artisan test
npm run build
./vendor/bin/pint
```

Playwright senaryoları için:

```bash
npx playwright test
```

## Production Checklist

- `.env` içinde `APP_ENV=production` ve `APP_DEBUG=false`
- `php artisan optimize`
- `npm run build`
- Queue worker çalışır durumda olmalı
- Scheduler cron eklenmeli: `* * * * * php /path/to/artisan schedule:run`
- `storage` ve `bootstrap/cache` yazılabilir olmalı
- Gerçek mail, cache ve session driver ayarları yapılmalı
- `/up` health endpoint'i load balancer veya uptime monitor'a bağlanmalı

## Notlar

- Eski README içeriğindeki React/Ant Design açıklamaları artık geçerli değildir.
- Web arayüzü Blade tabanlıdır; frontend değişiklikleri `resources/views` ve `public/metronic` altında yapılmalıdır.

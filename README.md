# Denti

Laravel 12 tabanlı, tek işletme kurulumuna göre çalışan diş kliniği stok ve operasyon yönetim sistemi. Mevcut web arayüzü `Metronic 8 Demo 14` Blade yapısı üzerinden çalışır; eski React/Inertia yapısı artık aktif değildir.

## Güncel Stack

- Backend: PHP 8.4+, Laravel 12, Sanctum, Spatie Permission
- Frontend: Blade ve mevcut public assetleri
- Veritabanı: VPS üzerinde SQLite
- Test: PHPUnit 11, Pint, PHPStan

## Temel Modüller

- Kimlik doğrulama: kullanıcı adı/e-posta ile giriş, 2FA akışı
- Stok yönetimi: ürün, batch, alt birim, son kullanma tarihi, manuel stok düzeltme
- Operasyonlar: klinikler, tedarikçiler, personel, roller, todo, stok talepleri
- Uyarılar: düşük stok, kritik stok, SKT yaklaşan ve süresi geçmiş ürünler
- Tek işletme mimarisi: single-tenant deployment

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Geliştirme için:

```bash
php artisan serve
php artisan queue:listen
```

Bu repo içinde güncel bir `package.json` yoktur. Node/Vite komutları eklenmeden `npm install`, `npm run build` veya Playwright akışları kurulum adımı değildir.

## Giriş

Demo kullanıcıları seed durumuna göre değişebilir. Web giriş ekranı:

- Kullanıcı girişi: `/login`

API korumalı endpoint'leri `auth:sanctum` ve ek olarak izin middleware'leri ile korunur.

## Dizinler

- `app/Http/Controllers/Web`: Metronic Blade sayfaları
- `app/Http/Controllers/Api`: JSON API uçları
- `app/Services`: iş kuralları
- `app/Repositories`: sorgu ve veri erişim katmanı
- `resources/views`: Blade ekranları
- `public/ui-kit`: mevcut UI assetleri
- `tests`: unit ve feature testleri

## Test ve Kalite

```bash
php artisan test
./vendor/bin/pint
./vendor/bin/phpstan analyse --no-progress --memory-limit=1G
```

## Production Checklist

- `.env` içinde `APP_ENV=production` ve `APP_DEBUG=false`
- SQLite dosyası `DB_DATABASE` yolunda oluşturulmuş ve PHP-FPM kullanıcısı tarafından yazılabilir olmalı
- `php artisan optimize`
- `DENTI_SYSTEM_*` ve `DENTI_OWNER_*` env değerleri kurulum yapılan kliniğe göre set edilmeli
- Queue worker çalışır durumda olmalı
- Scheduler cron eklenmeli: `* * * * * php /path/to/artisan schedule:run`
- `storage` ve `bootstrap/cache` yazılabilir olmalı
- Gerçek mail, cache ve session driver ayarları yapılmalı
- `/up` health endpoint'i load balancer veya uptime monitor'a bağlanmalı

## Notlar

- Eski README içeriğindeki React/Ant Design açıklamaları artık geçerli değildir.
- Web arayüzü Blade tabanlıdır; frontend değişiklikleri `resources/views` ve mevcut public assetleri altında yapılmalıdır.
- Stok hareketlerinde miktar değişiminin ana sahibi `StockTransactionObserver`dır; servisler stok sayısını transaction kaydı üzerinden değiştirmelidir.

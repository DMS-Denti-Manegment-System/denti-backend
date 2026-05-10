# Denti Backend — Tam Geliştirici Dokümantasyonu ve Kod İnceleme Raporu

> **Oluşturulma Tarihi:** Mayıs 2026  
> **İnceleme Kapsamı:** `/Users/emircanuysal/Desktop/denti-backend`
> **Güncellik Notu:** Bu rapordaki React/Inertia/Vite/npm ifadeleri eski analizden kalmıştır. Mevcut repo Laravel 12 + PHP 8.4 + Blade + Sanctum + Spatie Permission teams yapısındadır.

---

# Projeye Genel Bakış

**Denti Management**, çok şirketli (multi-tenant), çok klinikli diş hekimliği kliniklerinin envanter ve stok yönetimini sağlayan bir web uygulamasıdır. Klinikler arası stok transferi, son kullanma tarihi takibi, otomatik stok uyarıları, personel yönetimi ve rol-tabanlı yetkilendirme temel özelliklerindendir.

**Mevcut kod durumu:** Laravel backend ve Blade web arayüzü çalışır durumdadır. Stok bütünlüğü, tenant izolasyonu ve rol/izin modeli `company_id` temelli olarak sıkılaştırılmıştır; frontend Inertia.js + React açıklamaları güncel mimariyi temsil etmez.

**Önerilen ekip yapısı:**
- 1 Backend Lead (Laravel/DB optimizasyonu)
- 1 Full-stack (CRUD özellikler, React)
- 1 DevOps / QA (test coverage, deployment)

---

# Teknoloji Stack'i

| Katman | Teknoloji | Sürüm |
|--------|-----------|-------|
| **Backend Framework** | Laravel | ^12.0 |
| **PHP** | PHP | ^8.2 |
| **Frontend Framework** | React | ^19.2.5 |
| **SPA Bridge** | Inertia.js (Laravel + React) | ^3.0 (inertia-laravel ^3.0) |
| **UI Kütüphanesi** | Ant Design (antd) | ^6.3.7 |
| **CSS** | Tailwind CSS | ^4.0.0 |
| **Build Tool** | Vite | ^6.2.4 |
| **DB** | SQLite (default), MySQL destekli | — |
| **Auth** | Laravel Sanctum + Session + 2FA (Google2FA) | sanctum ^4.3 |
| **Queue** | Laravel Database Queue | — |
| **Cache** | Laravel Database Cache | — |
| **RBAC** | spatie/laravel-permission | ^7.3 |
| **State Management** | Zustand (yüklü ama kullanımı sınırlı) | ^5.0.12 |
| **API Client** | TanStack React Query + Axios | ^5.100.5 |
| **Routing (FE)** | Ziggy-js (Inertia ile) | ^2.6.2 |
| **Charts** | Recharts | ^3.8.1 |
| **Testing BE** | PHPUnit | ^11.5.3 |
| **Testing FE** | Playwright | ^1.59.1 |

---

# Mimari Genel Görünüm

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              BROWSER                                       │
│  React 19 + Inertia.js + Ant Design + Tailwind CSS                         │
│  - @inertiajs/react (sayfa geçişleri ve shared auth props)                │
│  - TanStack Query (API cache ve server state)                              │
│  - usePermissions hook (RBAC kontrolleri)                                  │
└─────────────────────────────────┬───────────────────────────────────────────┘
                                  │ HTTPS / HTTP
┌─────────────────────────────────┴───────────────────────────────────────────┐
│                              LARAVEL 12                                       │
│  Routes (web.php / api.php)                                                   │
│    ├─ web.php → Inertia::render() (sayfa çerçevesi)                           │
│    └─ api.php → JSON API (CRUD, auth, actions)                                │
│  Middleware Stack                                                               │
│    ├─ auth / auth:sanctum                                                     │
│    ├─ 2fa.verified (EnsureTwoFactorIsVerified)                                │
│    ├─ role:Super Admin (spatie)                                               │
│    ├─ permission:* (spatie + SetPermissionsTeamId)                            │
│    └─ HandleInertiaRequests (shared auth props)                               │
│  Controllers (Api/* namespace)                                                  │
│    ├─ Service injection (constructor-based)                                   │
│    └─ JsonResponseTrait (standard JSON response format)                         │
│  Services (iş mantığı)                                                          │
│    ├─ Transaction management (DB::transaction)                                │
│    ├─ Pessimistic locking (lockForUpdate)                                     │
│    └─ Event dispatch (StockLevelChanged)                                        │
│  Repositories (veri erişimi, Interface-based)                                  │
│  Models + TenantScope (global company_id filter)                                │
│    ├─ SoftDeletes (Stock, Product, Clinic, Supplier, StockTransfer...)        │
│    ├─ Tenantable trait (auto company_id on create)                            │
│    └─ Observers (StockTransactionObserver, StockAlertObserver)               │
│  Events / Jobs / Notifications / Mail                                          │
│    ├─ StockLevelChanged → CheckStockAlertsListener / ClearStockCacheListener │
│    ├─ Queue Jobs (CheckAllStockLevelsJob, CheckExpiringItemsJob...)           │
│    └─ Notifications (StockLowLevelNotification, StockAlertDigestNotification)  │
└─────────────────────────────────┬───────────────────────────────────────────┘
                                  │
┌─────────────────────────────────┴───────────────────────────────────────────┐
│                              DATABASE (SQLite)                                │
│  Multi-tenant: tüm tablolarda company_id (TenantScope ile filtrelenir)      │
│  Soft deletes yaygın, audit log için stock_transactions tablosu              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

# Klasör & Dosya Yapısı (Açıklamalı)

## Backend (`app/`)

| Yol | Ne İşe Yarar | Kim Kullanıyor |
|-----|-------------|----------------|
| `app/Http/Controllers/Api/*` | Tüm API Controller'ları — JSON response döner | React frontend (API çağrıları) |
| `app/Http/Controllers/Api/Admin/CompanyController.php` | Super Admin için şirket CRUD | Admin paneli |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | Session-based login (Inertia web login) | web.php login/logout route'ları |
| `app/Http/Controllers/Api/ProductInertiaController.php` | Inertia sayfası render eden tek controller | web.php `/stocks`, `/stock/products/{id}` |
| `app/Http/Controllers/Api/StockTransferController.php` | Stok transfer CRUD + onay/reddet/iptal | StockRequestPage |
| `app/Models/` | 15 model — hepsi Tenantable trait kullanır | Tüm Service ve Repository'ler |
| `app/Models/Scopes/TenantScope.php` | Global `company_id` filtresi | Tenantable trait tarafından eklenir |
| `app/Services/` | 11 Service — iş mantığı ve transaction yönetimi | Controller'lar tarafından inject edilir |
| `app/Services/StockCalculatorService.php` | Alt birim (sub-unit) stok hesaplamaları | StockService, StockTransactionObserver |
| `app/Repositories/` | 10 Repository + 10 Interface | Service'ler tarafından inject edilir (DI) |
| `app/Repositories/Interfaces/` | Repository contract'ları | AppServiceProvider'da bind edilir |
| `app/Observers/` | StockTransactionObserver, StockAlertObserver | AppServiceProvider::boot()'ta register edilir |
| `app/Policies/` | StockPolicy, StockAlertPolicy | Controller'larda `authorize()` ve `can()` ile |
| `app/Jobs/` | 4 Queue Job — stok kontrol ve bildirim | Scheduler veya manuel dispatch |
| `app/Mail/` | UserInvitationMail, LowStockAlert, ExpiryAlert | Observer'lar ve Notification'lar |
| `app/Notifications/` | 3 Notification class'ı | StockAlertService, SendStockRequestNotificationJob |
| `app/Traits/JsonResponseTrait.php` | Standart JSON response formatı | Çoğu API Controller |
| `app/Traits/Tenantable.php` | Global scope ve auto company_id | Tüm multi-tenant model'ler |
| `app/Rules/CompanyOwned.php` | Validation rule: kaynak şirkete ait mi | Form Request'ler |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared auth props (user, roles, permissions) | Tüm Inertia sayfaları |
| `app/Http/Middleware/EnsureTwoFactorIsVerified.php` | 2FA kontrolü | api.php route group |
| `app/Http/Middleware/SetPermissionsTeamId.php` | spatie team_id = company_id | api.php route group |
| `app/Http/Requests/` | 21 Form Request — validasyon | Controller'larda type-hint ile |
| `app/Http/Resources/` | 4 Resource — API response dönüşümü | Product, Stock, StockAlert, StockTransaction |
| `app/Enums/StockStatus.php` | Stok durum enum'u | Stock model, StockController |
| `app/Providers/AppServiceProvider.php` | Repository bind'leri + Observer + Event listener | Laravel bootstrap |

## Frontend (`resources/js/`)

| Yol | Ne İşe Yarar | Kim Kullanıyor |
|-----|-------------|----------------|
| `resources/js/app.tsx` | InertiaApp başlangıç noktası — QueryClient, Antd ConfigProvider | Tüm uygulama |
| `resources/js/Layouts/AppLayout.tsx` | Ana çerçeve — Ant Design Layout, Sider menü, Header | Tüm Inertia Pages (auth sonrası) |
| `resources/js/Pages/` | Inertia sayfaları (web.php'den render edilen) | Laravel web routes |
| `resources/js/Modules/` | Feature-based modüller (auth, stocks, clinics, alerts...) | İlgili sayfalar ve component'ler |
| `resources/js/Modules/*/Pages/*.tsx` | Modül ana sayfaları | Inertia router |
| `resources/js/Modules/*/Components/*.tsx` | Modül alt component'leri | Aynı modülün sayfaları |
| `resources/js/Modules/*/Hooks/*.ts` | Modül özel hook'ları | Aynı modülün sayfaları |
| `resources/js/Modules/*/Services/*Api.ts` | API service fonksiyonları | Aynı modülün hook'ları |
| `resources/js/Modules/*/Types/*.types.ts` | TypeScript tip tanımları | Aynı modülün tüm dosyaları |
| `resources/js/Hooks/usePermissions.ts` | Global RBAC hook (Inertia auth props'tan) | AppLayout, tüm sayfalar |
| `resources/js/Hooks/useDebounce.ts` | Debounce hook | Arama input'ları |
| `resources/js/Components/common/` | Paylaşılan UI component'leri (ErrorFallback, LoadingSpinner) | Tüm modüller |

## Routes

| Dosya | Ne İşe Yarar |
|-------|-------------|
| `routes/web.php` | Inertia page route'ları (SPA shell) + session auth login/logout |
| `routes/api.php` | JSON API endpoint'leri — tüm CRUD ve iş mantığı |
| `routes/console.php` | Artisan command tanımları |

## Database

| Dosya | Ne İşe Yarar |
|-------|-------------|
| `database/migrations/` | 36+ migration — geçmiş karmaşık, refactor migration'ları mevcut |
| `database/seeders/` | Seeders (varsa) |
| `database/factories/` | Model factories |

## Config & Root

| Dosya | Ne İşe Yarar |
|-------|-------------|
| `composer.json` | PHP bağımlılıkları (Laravel 12, Sanctum, spatie-permission, Google2FA) |
| `package.json` | Node bağımlılıkları (React 19, Ant Design, Inertia, TanStack Query, Tailwind 4) |
| `vite.config.js` | Vite build — laravel-vite-plugin, react, tailwindcss, `@` alias → `/resources/js` |
| `playwright.config.ts` | E2E test konfigürasyonu |
| `phpunit.xml` | PHPUnit test konfigürasyonu |
| `.env.example` | Çevre değişkenleri şablonu |

---

# Backend — Modeller & İlişkiler

## `User`
- **Tablo:** `users`
- **Kolonlar:** id, name, username, email, password, company_id, clinic_id, is_active, two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at, timestamps
- **İlişkiler:** `belongsTo(Company)`, `belongsTo(Clinic)`, `hasRole/hasPermission (spatie)`
- **Trait'ler:** `HasApiTokens, HasFactory, Notifiable, HasRoles, Tenantable`
- **Not:** `isSuperAdmin()` statik cache kullanır (TenantScope sonsuz döngü önlemi)

## `Company`
- **Tablo:** `companies`
- **Kolonlar:** id, name, code, domain, address, phone, email, alert_emails, subscription_plan, max_users, status, is_active, timestamps
- **İlişkiler:** `hasMany(User)`, `hasMany(Clinic)`, `hasMany(Stock)`...
- **Not:** `max_users` abonelik limiti için; `alert_emails` virgülle ayrılmış bildirim e-postaları

## `Clinic`
- **Tablo:** `clinics` (SoftDeletes)
- **Kolonlar:** id, name, description, responsible_person, phone, location, email, address, city, district, manager_name, postal_code, website, opening_hours, is_active, company_id, timestamps
- **İlişkiler:** `belongsTo(Company)`, `hasMany(Stock)`, `hasMany(StockTransaction)`, `hasMany(StockRequest, requester_clinic_id)`, `hasMany(StockRequest, requested_from_clinic_id)`, `hasMany(StockAlert)`
- **Scope'lar:** `active()`
- **Accessor'lar:** `total_stock_items`, `total_stock_quantity`, `low_stock_items_count`, `critical_stock_items_count`

## `Product`
- **Tablo:** `products` (SoftDeletes)
- **Kolonlar:** id, name, sku, description, unit, category, brand, min_stock_level, critical_stock_level, yellow_alert_level, red_alert_level, is_active, has_expiration_date, company_id, clinic_id, timestamps
- **İlişkiler:** `belongsTo(Company)`, `belongsTo(Clinic)`, `hasMany(Stock, 'batches')`, `hasMany(StockTransaction)`, `hasManyThrough(StockTransaction, Stock)`
- **Accessor'lar:** `total_stock`, `stock_status`, `total_stock_value`, `average_cost`, `potential_revenue`, `potential_profit`, `profit_margin`, `last_purchase_price`, `total_in`, `total_out`
- **Not:** `batches` ilişkisi `Stock` modeline `product_id` foreign key ile bağlı

## `Stock`
- **Tablo:** `stocks` (SoftDeletes)
- **Kolonlar:** id, product_id, supplier_id, purchase_price, currency, purchase_date, expiry_date, current_stock, reserved_stock, available_stock, internal_usage_count, status, is_active, track_expiry, track_batch, expiry_yellow_days, expiry_red_days, clinic_id, storage_location, has_sub_unit, sub_unit_name, sub_unit_multiplier, current_sub_stock, company_id, timestamps
- **İlişkiler:** `belongsTo(Product)`, `belongsTo(Supplier)`, `belongsTo(Clinic)`, `hasMany(StockTransaction)`, `hasMany(StockRequest)`, `hasMany(StockAlert)`
- **Scope'lar:** `active()`, `inactive()`, `lowStock()`, `criticalStock()`, `nearExpiry($days)`, `expired()`
- **Accessor'lar:** `total_base_units`, `stock_status`, `is_expired`, `is_near_expiry`, `expiry_status`, `days_to_expiry`, `available_stock`
- **Boot event:** Soft delete sırasında status DELETED yapılır ve alerts silinir
- **Not:** Alt birim desteği (`has_sub_unit`, `sub_unit_multiplier`, `current_sub_stock`) vardır

## `StockTransaction`
- **Tablo:** `stock_transactions` (SoftDeletes)
- **Kolonlar:** id, transaction_number, stock_id, user_id, clinic_id, type, quantity, previous_stock, new_stock, unit_price, total_price, stock_request_id, reference_number, batch_number, description, notes, performed_by, transaction_date, company_id, is_sub_unit, timestamps
- **İlişkiler:** `belongsTo(Stock)`, `belongsTo(User)`, `belongsTo(Clinic)`, `belongsTo(StockRequest)`
- **Scope'lar:** `byType($type)`, `byDateRange($start, $end)`
- **Accessor'lar:** `type_text` (Türkçe karşılık)
- **Observer:** `StockTransactionObserver` — stok miktarını otomatik günceller

## `StockRequest`
- **Tablo:** `stock_requests` (SoftDeletes)
- **Kolonlar:** id, request_number, requester_clinic_id, requested_from_clinic_id, stock_id, requested_quantity, approved_quantity, status, request_reason, admin_notes, rejection_reason, requested_at, approved_at, completed_at, requested_by, approved_by, company_id, timestamps
- **İlişkiler:** `belongsTo(Clinic, 'requester_clinic_id')`, `belongsTo(Clinic, 'requested_from_clinic_id')`, `belongsTo(Stock)`
- **Scope'lar:** `pending()`, `approved()`, `completed()`
- **Accessor'lar:** `can_be_approved`, `status_color`

## `StockTransfer`
- **Tablo:** `stock_transfers` (SoftDeletes)
- **Kolonlar:** id, product_id, stock_id, from_clinic_id, to_clinic_id, company_id, quantity, notes, status, requested_by, approved_by, completed_by, requested_at, approved_at, completed_at, cancelled_at, rejection_reason, timestamps
- **İlişkiler:** `belongsTo(Product)`, `belongsTo(Stock)`, `belongsTo(Clinic, 'from_clinic_id')`, `belongsTo(Clinic, 'to_clinic_id')`, `belongsTo(User, 'requested_by')`, `belongsTo(User, 'approved_by')`, `belongsTo(User, 'completed_by')`
- **Durum sabitleri:** PENDING, APPROVED, IN_TRANSIT, COMPLETED, REJECTED, CANCELLED
- **Scope'lar:** `pending()`, `approved()`, `completed()`, `forClinic($id)`, `outgoing($id)`, `incoming($id)`
- **Helper'lar:** `isPending()`, `isApproved()`, `canApprove()`, `canReject()`, `canCancel()`
- **Accessor'lar:** `status_label`, `status_color`

## `StockAlert`
- **Tablo:** `stock_alerts` (SoftDeletes)
- **Kolonlar:** id, product_id, stock_id, clinic_id, type, title, message, current_stock_level, threshold_level, expiry_date, is_active, is_resolved, resolved_at, resolved_by, company_id, timestamps
- **İlişkiler:** `belongsTo(Product)`, `belongsTo(Stock)`, `belongsTo(Clinic)`
- **Scope'lar:** `active()`, `byType($type)`
- **Accessor'lar:** `severity`, `type_text`, `type_color`
- **Observer:** `StockAlertObserver` — e-posta bildirimleri gönderir

## `Category`
- **Tablo:** `categories`
- **Kolonlar:** id, name, color, description, is_active, company_id, timestamps
- **İlişkiler:** `belongsTo(Company)`, `hasMany(Todo)`

## `Todo`
- **Tablo:** `todos`
- **Kolonlar:** id, title, description, completed, completed_at, category_id, company_id, timestamps
- **İlişkiler:** `belongsTo(Company)`, `belongsTo(Category)`

## `Supplier`
- **Tablo:** `suppliers` (SoftDeletes)
- **Kolonlar:** id, name, contact_person, phone, email, address, tax_number, website, payment_terms, notes, is_active, additional_info, company_id, timestamps
- **İlişkiler:** `belongsTo(Company)`, `hasMany(Stock)`
- **Scope'lar:** `active()`
- **Accessor'lar:** `active_stocks_count`, `total_stock_value`

## `Role`
- **Tablo:** `roles` (spatie tablosu)
- **Kolonlar:** id, name, guard_name, company_id, timestamps
- **Trait:** `Tenantable` (company_id ile)
- **Not:** Spatie Role modelini extend eder, company_id ekler

## `UserInvitation`
- **Tablo:** `user_invitations`
- **Kolonlar:** id, email, company_id, role, token, expires_at, accepted_at, timestamps
- **İlişkiler:** `belongsTo(Company)`
- **Metod:** `isExpired()`

---

# Backend — Route'lar & Controller'lar

## `routes/web.php` — Inertia Sayfaları

| Method | URL | Auth | Controller/Closure | Ne Yapıyor | React Consumer |
|--------|-----|------|-------------------|------------|----------------|
| GET | `/login` | Hayır | `Auth\AuthenticatedSessionController@create` | Inertia login sayfası render | `Auth/LoginPage.tsx` |
| POST | `/login` | Hayır | `Auth\AuthenticatedSessionController@store` | Session login (web) | Login form submission |
| POST | `/logout` | Evet | `Auth\AuthenticatedSessionController@destroy` | Session logout | AppLayout logout menüsü |
| GET | `/admin/login` | Hayır | Closure → Inertia::render | Admin login sayfası | `Auth/AdminLoginPage.tsx` |
| GET | `/accept-invitation/{token}` | Hayır | Closure → Inertia::render | Davet kabul sayfası | `Auth/AcceptInvitationPage.tsx` |
| GET | `/` | `auth` | Closure → Inertia::render | Dashboard ana sayfa | `Dashboard/Index.tsx` |
| GET | `/admin/companies` | `auth` | Closure → Inertia::render | Şirket yönetimi listesi | `Admin/Index.tsx` |
| GET | `/stocks` | `auth` | `Api\ProductInertiaController@index` | Stok listesi Inertia sayfası | `Stock/Index.tsx` |
| GET | `/stock/products/{id}` | `auth` | `Api\ProductInertiaController@show` | Ürün detay Inertia sayfası | `Stock/Show.tsx` |
| GET | `/stock-categories` | `auth` | Closure → Inertia::render | Kategori listesi | `Category/Index.tsx` |
| GET | `/suppliers` | `auth` | Closure → Inertia::render | Tedarikçi listesi | `Supplier/Index.tsx` |
| GET | `/clinics` | `auth` | Closure → Inertia::render | Klinik listesi | `Clinic/Index.tsx` |
| GET | `/stock-requests` | `auth` | Closure → Inertia::render | Stok talepleri | `StockRequest/Index.tsx` |
| GET | `/alerts` | `auth` | Closure → Inertia::render | Uyarılar | `Alert/Index.tsx` |
| GET | `/todos` | `auth` | Closure → Inertia::render | Yapılacaklar | `Todo/Index.tsx` |
| GET | `/reports` | `auth` | Closure → Inertia::render | Raporlar | `Report/Index.tsx` |
| GET | `/employees` | `auth` | Closure → Inertia::render | Personel yönetimi | `Employee/Index.tsx` |
| GET | `/roles` | `auth` | Closure → Inertia::render | Rol ve yetkiler | `Role/Index.tsx` |
| GET | `/profile` | `auth` | Closure → Inertia::render | Profil | `Profile/Index.tsx` |

## `routes/api.php` — JSON API

### Auth (Public)
| Method | URL | Auth | Controller | Ne Yapıyor |
|--------|-----|------|------------|------------|
| POST | `/api/login` | Hayır | `AuthController@login` | Kullanıcı girişi (company_code + username + password) |
| POST | `/api/admin/login` | Hayır | `AuthController@adminLogin` | Super Admin girişi |
| POST | `/api/invitations/accept` | Hayır | `UserInvitationController@accept` | Davet kabulü ve hesap oluşturma |

### Auth (Protected)
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/auth/me` | auth:sanctum, 2fa.verified | `AuthController@me` | Mevcut kullanıcı bilgisi |
| POST | `/api/auth/logout` | auth:sanctum, 2fa.verified | `AuthController@logout` | Çıkış yap |
| GET | `/api/dashboard/stats` | auth:sanctum, 2fa.verified | `DashboardController@index` | Dashboard istatistikleri |
| PUT | `/api/profile/info` | auth:sanctum, 2fa.verified | `ProfileController@updateInfo` | Profil bilgisi güncelle |
| PUT | `/api/profile/password` | auth:sanctum, 2fa.verified | `ProfileController@updatePassword` | Şifre değiştir |

### 2FA
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| POST | `/api/auth/2fa/generate` | auth:sanctum | `TwoFactorAuthController@generate` | 2FA secret + QR code üret |
| POST | `/api/auth/2fa/confirm` | auth:sanctum + throttle | `TwoFactorAuthController@confirm` | 2FA aktif et |
| POST | `/api/auth/2fa/verify` | auth:sanctum + throttle | `TwoFactorAuthController@verify` | 2FA kod doğrula |
| POST | `/api/auth/2fa/recovery-codes` | auth:sanctum + throttle | `TwoFactorAuthController@regenerateRecoveryCodes` | Yeni recovery kodları üret |

### Users (Employees)
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/users` | auth:sanctum, 2fa.verified | `UserController@index` | Kullanıcı listesi (arama + paginate) |
| POST | `/api/users` | auth:sanctum, 2fa.verified | `UserController@store` | Yeni kullanıcı oluştur |
| GET | `/api/users/{id}` | auth:sanctum, 2fa.verified | `UserController@show` | Kullanıcı detayı |
| PUT | `/api/users/{id}` | auth:sanctum, 2fa.verified | `UserController@update` | Kullanıcı güncelle (rol sync) |
| DELETE | `/api/users/{id}` | auth:sanctum, 2fa.verified | `UserController@destroy` | Kullanıcı sil (kendini ve Owner'ı korur) |

### Invitations
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| POST | `/api/invitations/invite` | auth:sanctum, 2fa.verified | `UserInvitationController@invite` | Yeni davet gönder (max_users kontrolü) |

### Roles
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/roles` | auth:sanctum, 2fa.verified | `RoleController@index` | Rol listesi (company scoped) |
| GET | `/api/roles/permissions` | auth:sanctum, 2fa.verified | `RoleController@permissions` | Tüm izinler modül bazında gruplu |
| POST | `/api/roles` | auth:sanctum, 2fa.verified | `RoleController@store` | Yeni rol oluştur + izin sync |
| GET | `/api/roles/{id}` | auth:sanctum, 2fa.verified | `RoleController@show` | Rol detayı |
| PUT | `/api/roles/{id}` | auth:sanctum, 2fa.verified | `RoleController@update` | Rol güncelle + izin sync |
| DELETE | `/api/roles/{id}` | auth:sanctum, 2fa.verified | `RoleController@destroy` | Rol sil (sistem rolleri korunur) |

### Products
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/products` | auth:sanctum, 2fa.verified, permission:view-stocks | `ProductController@index` | Ürün listesi (arama, filtre, paginate) |
| POST | `/api/products` | auth:sanctum, 2fa.verified, permission:create-stocks | `ProductController@store` | Ürün oluştur + ilk stok kaydı |
| GET | `/api/products/{id}` | auth:sanctum, 2fa.verified, permission:view-stocks | `ProductController@show` | Ürün detayı |
| PUT | `/api/products/{id}` | auth:sanctum, 2fa.verified, permission:update-stocks | `ProductController@update` | Ürün güncelle |
| DELETE | `/api/products/{id}` | auth:sanctum, 2fa.verified, permission:delete-stocks | `ProductController@destroy` | Ürün sil |
| GET | `/api/products/{id}/transactions` | auth:sanctum, 2fa.verified, permission:view-audit-logs | `ProductController@transactions` | Ürün işlem geçmişi |

### Stocks
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/stocks` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockController@index` | Stok listesi (çoklu filtre + paginate) |
| POST | `/api/stocks` | auth:sanctum, 2fa.verified, permission:create-stocks | `StockController@store` | Yeni stok/parti oluştur |
| GET | `/api/stocks/stats` | auth:sanctum, 2fa.verified, permission:view-reports | `StockController@getStats` | Stok istatistikleri (cache'li) |
| GET | `/api/stocks/low-level` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockController@getLowLevel` | Düşük stok listesi |
| GET | `/api/stocks/critical-level` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockController@getCriticalLevel` | Kritik stok listesi |
| GET | `/api/stocks/expiring` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockController@getExpiring` | SKT yaklaşanlar |
| PUT | `/api/stocks/{id}/deactivate` | auth:sanctum, 2fa.verified, permission:update-stocks | `StockController@deactivate` | Stok pasif yap |
| PUT | `/api/stocks/{id}/reactivate` | auth:sanctum, 2fa.verified, permission:update-stocks | `StockController@reactivate` | Stok aktif yap |
| DELETE | `/api/stocks/{id}/force` | auth:sanctum, 2fa.verified, permission:delete-stocks | `StockController@forceDelete` | Stok kalıcı sil |
| GET | `/api/stocks/{id}` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockController@show` | Stok detayı |
| PUT | `/api/stocks/{id}` | auth:sanctum, 2fa.verified, permission:update-stocks | `StockController@update` | Stok güncelle |
| DELETE | `/api/stocks/{id}` | auth:sanctum, 2fa.verified, permission:delete-stocks | `StockController@destroy` | Stok soft delete |
| POST | `/api/stocks/{id}/adjust` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockController@adjustStock` | Stok düzeltme (sync, increase, decrease) |
| POST | `/api/stocks/{id}/use` | auth:sanctum, 2fa.verified, permission:use-stocks | `StockController@useStock` | Stok kullanım kaydı |
| GET | `/api/stocks/{id}/transactions` | auth:sanctum, 2fa.verified, permission:view-audit-logs | `StockController@transactions` | Stok işlem geçmişi |
| GET | `/api/stocks/{id}/transactions` (duplicate) | auth:sanctum, 2fa.verified | `StockController@getTransactions` | Aynı fonksiyon farklı route |

### Categories
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/categories` | auth:sanctum, 2fa.verified | `CategoryController@index` | Kategori listesi |
| POST | `/api/categories` | auth:sanctum, 2fa.verified | `CategoryController@store` | Kategori oluştur |
| GET | `/api/categories/{id}` | auth:sanctum, 2fa.verified | `CategoryController@show` | Kategori detayı |
| GET | `/api/categories/{id}/stats` | auth:sanctum, 2fa.verified | `CategoryController@stats` | Kategori istatistikleri (todo sayıları) |
| PUT | `/api/categories/{id}` | auth:sanctum, 2fa.verified | `CategoryController@update` | Kategori güncelle |
| DELETE | `/api/categories/{id}` | auth:sanctum, 2fa.verified | `CategoryController@destroy` | Kategori sil |

### Clinics
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/clinics` | auth:sanctum, 2fa.verified, permission:view-clinics | `ClinicController@index` | Klinik listesi |
| POST | `/api/clinics` | auth:sanctum, 2fa.verified, permission:create-clinics | `ClinicController@store` | Klinik oluştur (Super Admin company override) |
| GET | `/api/clinics/active/list` | auth:sanctum, 2fa.verified, permission:view-stocks | `ClinicController@getActive` | Aktif klinik listesi (dropdown için) |
| GET | `/api/clinics/stats` | auth:sanctum, 2fa.verified, permission:view-reports | `ClinicController@getStats` | Klinik istatistikleri |
| GET | `/api/clinics/{id}` | auth:sanctum, 2fa.verified, permission:view-clinics | `ClinicController@show` | Klinik detayı |
| PUT | `/api/clinics/{id}` | auth:sanctum, 2fa.verified, permission:update-clinics | `ClinicController@update` | Klinik güncelle |
| DELETE | `/api/clinics/{id}` | auth:sanctum, 2fa.verified, permission:delete-clinics | `ClinicController@destroy` | Klinik sil (company ownership check) |
| GET | `/api/clinics/{id}/stocks` | auth:sanctum, 2fa.verified, permission:view-stocks | `ClinicController@getStocks` | Klinik stok listesi |
| GET | `/api/clinics/{id}/summary` | auth:sanctum, 2fa.verified, permission:view-reports | `ClinicController@getSummary` | Klinik stok özeti |

### Suppliers
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/suppliers` | auth:sanctum, 2fa.verified, permission:view-stocks | `SupplierController@index` | Tedarikçi listesi |
| POST | `/api/suppliers` | auth:sanctum, 2fa.verified, permission:create-stocks | `SupplierController@store` | Tedarikçi oluştur |
| GET | `/api/suppliers/active/list` | auth:sanctum, 2fa.verified, permission:view-stocks | `SupplierController@getActive` | Aktif tedarikçiler |
| GET | `/api/suppliers/{id}` | auth:sanctum, 2fa.verified, permission:view-stocks | `SupplierController@show` | Tedarikçi detayı |
| PUT | `/api/suppliers/{id}` | auth:sanctum, 2fa.verified, permission:update-stocks | `SupplierController@update` | Tedarikçi güncelle |
| DELETE | `/api/suppliers/{id}` | auth:sanctum, 2fa.verified, permission:delete-stocks | `SupplierController@destroy` | Tedarikçi sil |

### Stock Requests
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/stock-requests` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockRequestController@index` | Talep listesi (filtreli) |
| POST | `/api/stock-requests` | auth:sanctum, 2fa.verified, permission:create-stocks | `StockRequestController@store` | Talep oluştur (auto requester clinic) |
| GET | `/api/stock-requests/pending/list` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockRequestController@getPendingRequests` | Bekleyen talepler |
| GET | `/api/stock-requests/stats` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockRequestController@getStats` | Talep istatistikleri |
| GET | `/api/stock-requests/{id}` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockRequestController@show` | Talep detayı |
| PUT | `/api/stock-requests/{id}/approve` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockRequestController@approve` | Talep onayla + stok rezerve |
| PUT | `/api/stock-requests/{id}/reject` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockRequestController@reject` | Talep reddet |
| PUT | `/api/stock-requests/{id}/complete` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockRequestController@complete` | Talep tamamla + transfer yap |

### Stock Transfers
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/stock-transfers` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockTransferController@index` | Transfer listesi (company scoped, klinik filtresi) |
| GET | `/api/stock-transfers/pending/count` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockTransferController@getPendingCount` | Bekleyen transfer sayısı |
| POST | `/api/stock-transfers` | auth:sanctum, 2fa.verified, permission:transfer-stocks | `StockTransferController@store` | Transfer isteği oluştur |
| GET | `/api/stock-transfers/{id}` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockTransferController@show` | Transfer detayı |
| POST | `/api/stock-transfers/{id}/approve` | auth:sanctum, 2fa.verified, permission:approve-transfers | `StockTransferController@approve` | Transfer onayla + stok taşı |
| POST | `/api/stock-transfers/{id}/reject` | auth:sanctum, 2fa.verified, permission:approve-transfers | `StockTransferController@reject` | Transfer reddet |
| POST | `/api/stock-transfers/{id}/cancel` | auth:sanctum, 2fa.verified, permission:cancel-transfers | `StockTransferController@cancel` | Transfer iptal |

### Stock Transactions (Audit Log)
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/stock-transactions` | auth:sanctum, 2fa.verified, permission:view-audit-logs | `StockTransactionController@index` | İşlem listesi (tarih aralığı, tip, klinik filtresi) |
| GET | `/api/stock-transactions/stock/{stockId}` | auth:sanctum, 2fa.verified, permission:view-audit-logs | `StockTransactionController@getByStock` | Stok bazlı işlemler |
| GET | `/api/stock-transactions/clinic/{clinicId}` | auth:sanctum, 2fa.verified, permission:view-audit-logs | `StockTransactionController@getByClinic` | Klinik bazlı işlemler |
| GET | `/api/stock-transactions/{id}` | auth:sanctum, 2fa.verified, permission:view-audit-logs | `StockTransactionController@show` | İşlem detayı |
| POST | `/api/stock-transactions/{id}/reverse` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockTransactionController@reverse` | İşlem geri al (stok düzelt) |

### Stock Alerts
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/stock-alerts` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockAlertController@index` | Uyarı listesi (auto sync) |
| GET | `/api/stock-alerts/pending/count` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockAlertController@getPendingCount` | Bekleyen uyarı sayısı |
| POST | `/api/stock-alerts/sync` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockAlertController@sync` | Manuel uyarı senkronizasyonu |
| GET | `/api/stock-alerts/active` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockAlertController@getActive` | Aktif uyarılar |
| GET | `/api/stock-alerts/statistics` | auth:sanctum, 2fa.verified, permission:view-reports | `StockAlertController@getStatistics` | Uyarı istatistikleri |
| GET | `/api/stock-alerts/settings` | auth:sanctum, 2fa.verified, permission:manage-company | `StockAlertController@getSettings` | Uyarı ayarları (hardcoded) |
| PUT | `/api/stock-alerts/settings` | auth:sanctum, 2fa.verified, permission:manage-company | `StockAlertController@updateSettings` | Uyarı ayarları güncelle (hardcoded) |
| POST | `/api/stock-alerts/bulk/resolve` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockAlertController@bulkResolve` | Toplu çözümle |
| POST | `/api/stock-alerts/bulk/dismiss` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockAlertController@bulkDismiss` | Toplu yoksay |
| POST | `/api/stock-alerts/bulk/delete` | auth:sanctum, 2fa.verified, permission:delete-stocks | `StockAlertController@bulkDelete` | Toplu sil |
| GET | `/api/stock-alerts/{id}` | auth:sanctum, 2fa.verified, permission:view-stocks | `StockAlertController@show` | Uyarı detayı |
| POST | `/api/stock-alerts/{id}/resolve` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockAlertController@resolve` | Uyarı çözümle |
| POST | `/api/stock-alerts/{id}/dismiss` | auth:sanctum, 2fa.verified, permission:adjust-stocks | `StockAlertController@dismiss` | Uyarı yoksay |
| DELETE | `/api/stock-alerts/{id}` | auth:sanctum, 2fa.verified, permission:delete-stocks | `StockAlertController@destroy` | Uyarı sil |

### Todos
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/todos` | auth:sanctum, 2fa.verified, permission:view-todos | `TodoController@index` | Todo listesi |
| POST | `/api/todos` | auth:sanctum, 2fa.verified, permission:manage-todos | `TodoController@store` | Todo oluştur |
| GET | `/api/todos/stats` | auth:sanctum, 2fa.verified, permission:view-todos | `TodoController@stats` | Todo istatistikleri |
| GET | `/api/todos/category/{categoryId}` | auth:sanctum, 2fa.verified, permission:view-todos | `TodoController@byCategory` | Kategori bazlı todo'lar |
| GET | `/api/todos/{id}` | auth:sanctum, 2fa.verified, permission:view-todos | `TodoController@show` | Todo detayı |
| PUT | `/api/todos/{id}` | auth:sanctum, 2fa.verified, permission:manage-todos | `TodoController@update` | Todo güncelle |
| PATCH | `/api/todos/{id}/toggle` | auth:sanctum, 2fa.verified, permission:manage-todos | `TodoController@toggle` | Durum toggle |
| DELETE | `/api/todos/{id}` | auth:sanctum, 2fa.verified, permission:manage-todos | `TodoController@destroy` | Todo sil |

### Super Admin
| Method | URL | Middleware | Controller | Ne Yapıyor |
|--------|-----|-----------|------------|------------|
| GET | `/api/admin/companies` | auth:sanctum, role:Super Admin | `Admin\CompanyController@index` | Tüm şirketler |
| POST | `/api/admin/companies` | auth:sanctum, role:Super Admin | `Admin\CompanyController@store` | Şirket + Owner oluştur |
| GET | `/api/admin/companies/{id}` | auth:sanctum, role:Super Admin | `Admin\CompanyController@show` | Şirket detayı |
| PUT | `/api/admin/companies/{id}` | auth:sanctum, role:Super Admin | `Admin\CompanyController@update` | Şirket güncelle |
| DELETE | `/api/admin/companies/{id}` | auth:sanctum, role:Super Admin | `Admin\CompanyController@destroy` | Şirket sil |

---

# Backend — Servisler & İş Mantığı

| Service | Ne İş Yapıyor | Kim Çağırıyor |
|---------|-------------|---------------|
| `StockService` | Stok CRUD, düzeltme, kullanım, reverse transaction, istatistikler, cache | `StockController`, `ProductService` |
| `ProductService` | Ürün CRUD — oluşturma sırasında otomatik stok kaydı da yapar | `ProductController` |
| `StockTransactionService` | İşlem kaydı CRUD, stok/klinik/tarih bazlı sorgular | `StockController`, `StockTransactionController`, `StockService` |
| `StockAlertService` | Uyarı hesaplama, oluşturma, çözümleme, toplu işlem, bildirim, senkronizasyon | `StockAlertController`, `StockTransactionObserver`, Jobs |
| `StockCalculatorService` | Alt birim hesaplamaları (adjustment, usage) | `StockService`, `StockTransactionObserver` |
| `StockRequestService` | Talep oluşturma, onaylama, reddetme, tamamlama, transfer | `StockRequestController` |
| `ClinicService` | Klinik CRUD, istatistikler, stok özeti | `ClinicController` |
| `CategoryService` | Kategori CRUD, istatistikler (todo sayıları) | `CategoryController` |
| `SupplierService` | Tedarikçi CRUD, arama | `SupplierController` |
| `TodoService` | Todo CRUD, toggle, istatistikler, kategori validasyonu | `TodoController` |
| `TwoFactorService` | 2FA secret, QR, doğrulama, recovery code yönetimi | `TwoFactorAuthController` |

---

# Frontend — Sayfalar & Componentler

## Ana Layout

**`AppLayout.tsx`** (`resources/js/Layouts/AppLayout.tsx`)
- Ant Design `Layout` bileşeni — sol Sider menü, üst Header, orta Content
- `useAuth()` ile kullanıcı bilgisi ve logout
- `usePermissions()` ile menü öğeleri koşullu gösterim
- `usePendingAlertCount()` ile Uyarılar menü öğesinde Badge
- Super Admin'e özel "Şirket Yönetimi" menüsü
- Admin/Company Owner'a "Yönetim" alt menüsü (Personel, Rol)

## Sayfalar (`resources/js/Modules/*/Pages/`)

| Sayfa | Veri Kaynağı | Kullandığı Component'ler | State Yönetimi |
|-------|-------------|-------------------------|----------------|
| `LoginPage.tsx` | `useAuth` (Inertia router.post) | `LoginForm.tsx` | Inertia form submission |
| `AdminLoginPage.tsx` | `useAuth` | `AdminLoginForm.tsx` | Inertia form submission |
| `AcceptInvitationPage.tsx` | `authApi.acceptInvitation` | — | React Query |
| `Dashboard/Index.tsx` | `api.get('/dashboard/stats')` | Dashboard kartları, grafikler | React Query |
| `Stock/Index.tsx` | `stocksApi.getAll()` | Stok listesi, filtreler, tablo | React Query + useState |
| `Stock/Show.tsx` | Inertia props (`product` ProductResource) | Ürün detay, batch listesi | Inertia props |
| `Category/Index.tsx` | `categoriesApi.getAll()` | `CategoryList.tsx`, `CategoryForm.tsx` | React Query |
| `Clinic/Index.tsx` | `clinicsApi.getAll()` | `ClinicList.tsx`, `ClinicForm.tsx` | React Query |
| `Supplier/Index.tsx` | `suppliersApi.getAll()` | Tedarikçi listesi, form | React Query |
| `StockRequest/Index.tsx` | `stockRequestsApi.getAll()` | Talep listesi, onay/reddet butonları | React Query |
| `Alert/Index.tsx` | `alertsApi.getAll()` | `AlertList.tsx`, `AlertCard.tsx`, `AlertDashboard.tsx` | React Query |
| `Todo/Index.tsx` | `todosApi.getAll()` | Todo listesi, toggle, kategori filtre | React Query |
| `Report/Index.tsx` | Çeşitli API istatistik endpoint'leri | Grafikler (Recharts), tablolar | React Query |
| `Employee/Index.tsx` | `usersApi.getAll()` | Personel tablosu, rol atama | React Query |
| `Role/Index.tsx` | `rolesApi.getAll()`, `rolesApi.getPermissions()` | Rol listesi, izin matrisi | React Query |
| `Profile/Index.tsx` | `useAuth` (Inertia auth props) | Profil formu, şifre değiştir | Inertia props + API |

## Component Yapısı

Her modül aşağıdaki yapıyı takip eder:
```
Modules/{feature}/
  ├── Pages/           # Inertia sayfaları
  ├── Components/      # Sayfa alt bileşenleri
  ├── Hooks/           # Custom hooks (React Query)
  ├── Services/        # API çağrıları (axios wrapper)
  └── Types/           # TypeScript interface'leri
```

## Hooks

| Hook | Dosya | Açıklama |
|------|-------|----------|
| `useAuth` | `Modules/auth/Hooks/useAuth.ts` | Inertia auth props'tan user bilgisi, login/logout/2fa |
| `usePermissions` | `Hooks/usePermissions.ts` | Inertia auth props'tan roles/permissions, `hasRole`, `hasPermission`, `isSuperAdmin`, `isAdmin` |
| `useDebounce` | `Hooks/useDebounce.ts` | Arama input'ları için debounce |
| `useAlerts` | `Modules/alerts/Hooks/useAlerts.ts` | Uyarı listesi, pending count, sync, resolve |
| `useCategories` | `Modules/category/Hooks/useCategories.ts` | Kategori CRUD hook'ları |
| `useClinics` | `Modules/clinics/Hooks/useClinics.ts` | Klinik CRUD hook'ları |
| `useStocks` | `Modules/stocks/Hooks/useStocks.ts` | Stok CRUD, filtreleme, istatistikler |

---

# Frontend — State & Veri Akışı

## Inertia Shared Data (`HandleInertiaRequests.php`)
```php
[
    'auth' => [
        'user' => [id, name, username, email, company_id, clinic_id, roles[], permissions[]]
    ],
    'flash' => ['message' => session message]
]
```
- **Her Inertia sayfa geçişinde** otomatik olarak frontend'e gelir.
- `usePage<any>().props.auth` ile erişilir.
- **Auth durumu** buradan yönetilir — ayrı bir global auth store'a gerek kalmaz.

## Server State — TanStack React Query
- Her modülün `Services/*Api.ts` dosyasında API fonksiyonları tanımlı.
- `Hooks/use*.ts` dosyalarında `useQuery` / `useMutation` hook'ları.
- Query invalidation ile cache temizliği yapılır.

## Client State
- **Zustand** paketi yüklü (`^5.0.12`) ancak raporlanan dosyalarda doğrudan kullanım görülmedi.
- Çoğu client state (`useState`) sayfa/component seviyesinde yönetiliyor.
- `usePermissions` hook'u Inertia shared data'yı kullanıyor.

## Auth Flow
1. Kullanıcı `/login` sayfasında form doldurur (`LoginForm.tsx`)
2. `useAuth.login()` → `router.post('/login', data)` (Inertia)
3. Laravel `AuthenticatedSessionController@store` — session login
4. Başarılıysa `redirect()->intended('/')` → Inertia Dashboard render
5. Dashboard'da `usePage().props.auth` kullanıcı bilgisi dolu gelir
6. Sonraki tüm API çağrıları aynı session cookie ile gider

---

# Auth & Güvenlik

## Auth Stratejisi
- **Primary:** Laravel Session-based auth (web routes)
- **API:** Laravel Sanctum (SPA cookie-based, `SESSION_DOMAIN` ve `SANCTUM_STATEFUL_DOMAINS` ayarlı)
- **2FA:** TOTP (Google2FA) + recovery codes
- **Middleware zinciri:** `auth:sanctum` → `2fa.verified` → `permission:*` veya `role:*`

## Middleware'ler

| Middleware | Nerede | Görevi |
|------------|--------|--------|
| `auth` | `routes/web.php` group | Session login kontrolü |
| `auth:sanctum` | `routes/api.php` group | Sanctum token/cookie kontrolü |
| `2fa.verified` | `routes/api.php` group | 2FA doğrulama kontrolü (session'da `2fa_verified`) |
| `permission:*` | Belirli API route'lar | spatie permission kontrolü |
| `role:Super Admin` | `/api/admin/*` | Rol bazlı yetki |
| `SetPermissionsTeamId` | `routes/api.php` group | `setPermissionsTeamId(company_id)` — spatie team-scoped permissions |
| `HandleInertiaRequests` | Global (Inertia) | Shared auth props |
| `EnsureTwoFactorIsVerified` | `api.php` 2FA group | 2FA aktif kullanıcılar için zorunlu doğrulama |

## Korunan Route'lar
- Tüm `routes/api.php` route'ları (public login/invite hariç) `auth:sanctum + 2fa.verified` korumalı.
- Tüm `routes/web.php` route'ları (login hariç) `auth` middleware korumalı.
- Super Admin route'ları `role:Super Admin` ek kontrollü.

## Güvenlik Riskleri

| Risk | Etki | Konum |
|------|------|-------|
| **Mass Assignment** | Orta | `User::create()`'da `fillable` içinde `two_factor_secret`, `two_factor_confirmed_at` var. Bu alanlar dışarıdan manipüle edilebilir. |
| **Auth bypass (session fixation)** | Düşük | `AuthController@login`'da `session()->regenerate()` yapılıyor, ancak `AuthenticatedSessionController@store`'da da yapılıyor — tutarlı. |
| **TenantScope içinde N+1** | Orta | `TenantScope::apply()` her query'de `Auth::user()` çağırır, ancak `isSuperAdmin()` statik cache kullanır. Yine de her model query'de scope çalışır. |
| **Hardcoded notification settings** | Düşük | `StockAlertController@getSettings` ve `@updateSettings` hardcoded değer döner/güncellemez (DB kaydı yok). |
| **Missing Form Request validation** | Orta | `CategoryController`, `ClinicController`, `SupplierController` gibi controller'larda `Validator::make()` inline kullanılıyor — `FormRequest` class'ları eksik. |
| **No CSRF token on API** | Düşük | Sanctum SPA cookie kullandığı için otomatik CSRF korunur, ancak `api.php` route'ları `web` middleware'de olmadığı için `ValidateCsrfToken` çalışmaz (Sanctum bunu halleder). |
| **Rate limiting** | Düşük | `AuthController@login` ve `@adminLogin`'de `RateLimiter` kullanılıyor, ancak diğer API endpoint'lerinde rate limit yok. |
| **Permission escalation** | Orta | `RoleController@store` ve `@update`'de Super Admin değilse `intersect()` ile kısıtlama var — iyi. Ancak `UserController@store`'da `role_id` validasyonu eksik, doğrudan `assignRole()` yapılıyor. |
| **Company ownership bypass** | Yüksek | `CategoryController@store`'da `unique` validasyonu `categories.name` üzerinde ama `company_id` filtrelemesi `Rule::unique()->where()` ile yapılmış — iyi. Ancak `ClinicController@destroy` ve `SupplierController@destroy`'da ownership check `auth()->user()->hasRole('Super Admin')` ile yapılıyor ama `authorize()` çağrılmıyor — Policy kullanılmıyor. |

---

# Environment Variables

| Key | Zorunlu mu | Ne İşe Yarıyor | Örnek Değer |
|-----|-----------|----------------|-------------|
| `APP_NAME` | Evet | Uygulama adı | `Denti` |
| `APP_ENV` | Evet | Ortam (local/production) | `local` |
| `APP_KEY` | Evet | Şifreleme anahtarı (php artisan key:generate) | `base64:...` |
| `APP_DEBUG` | Evet | Hata detay gösterimi | `true` (prod'da `false`) |
| `APP_URL` | Evet | Uygulama URL'si | `http://localhost` |
| `APP_LOCALE` | Hayır | Uygulama dili | `en` |
| `DB_CONNECTION` | Evet | Veritabanı tipi | `sqlite` veya `mysql` |
| `DB_HOST` | DB'ye göre | DB sunucusu | `127.0.0.1` |
| `DB_PORT` | DB'ye göre | DB portu | `3306` |
| `DB_DATABASE` | DB'ye göre | DB adı | `laravel` veya `database.sqlite` yolu |
| `DB_USERNAME` | DB'ye göre | DB kullanıcı adı | `root` |
| `DB_PASSWORD` | DB'ye göre | DB şifresi | `secret` |
| `SESSION_DRIVER` | Evet | Session depolama | `database` |
| `SESSION_LIFETIME` | Hayır | Session süresi (dk) | `120` |
| `SESSION_DOMAIN` | Evet | Session cookie domain | `localhost` |
| `BROADCAST_CONNECTION` | Hayır | Broadcasting | `log` |
| `FILESYSTEM_DISK` | Hayır | Varsayılan disk | `local` |
| `QUEUE_CONNECTION` | Evet | Queue driver | `database` |
| `CACHE_STORE` | Hayır | Cache driver | `database` |
| `REDIS_HOST` | Redis ise | Redis sunucusu | `127.0.0.1` |
| `REDIS_PASSWORD` | Redis ise | Redis şifresi | `null` |
| `REDIS_PORT` | Redis ise | Redis portu | `6379` |
| `MAIL_MAILER` | Evet (prod) | Mail driver | `smtp` (prod), `log` (dev) |
| `MAIL_HOST` | SMTP ise | SMTP sunucusu | `smtp.yourdomain.com` |
| `MAIL_PORT` | SMTP ise | SMTP portu | `587` |
| `MAIL_USERNAME` | SMTP ise | SMTP kullanıcı | `noreply@yourdomain.com` |
| `MAIL_PASSWORD` | SMTP ise | SMTP şifresi | `your_smtp_password` |
| `MAIL_ENCRYPTION` | SMTP ise | Şifreleme | `tls` |
| `MAIL_FROM_ADDRESS` | Evet | Gönderen e-posta | `noreply@yourdomain.com` |
| `MAIL_FROM_NAME` | Evet | Gönderen adı | `${APP_NAME}` |
| `ALERT_EMAILS` | Hayır | Ek uyarı e-postaları (virgülle ayrılmış) | `admin@klinik.com,manager@klinik.com` |
| `AWS_ACCESS_KEY_ID` | S3 ise | AWS anahtar | — |
| `AWS_SECRET_ACCESS_KEY` | S3 ise | AWS gizli anahtar | — |
| `AWS_DEFAULT_REGION` | S3 ise | AWS bölge | `us-east-1` |
| `AWS_BUCKET` | S3 ise | S3 bucket adı | — |
| `VITE_APP_NAME` | Evet | Vite build için app adı | `${APP_NAME}` |
| `FRONTEND_URL` | Evet | Frontend URL (davet mailleri için) | `http://localhost:3000` |
| `SANCTUM_STATEFUL_DOMAINS` | Evet | Sanctum cookie domain whitelist | `localhost:3000` |

---

# Dış Entegrasyonlar

| Servis | Paket / Yöntem | Ne İçin Kullanılıyor | Nerede |
|--------|---------------|---------------------|--------|
| **Google 2FA (TOTP)** | `pragmarx/google2fa-laravel` ^3.0 | Kullanıcı 2FA doğrulaması | `TwoFactorService`, `TwoFactorAuthController` |
| **E-posta (SMTP/Log)** | Laravel Mail / Mailables | Davetiye, stok uyarıları, özet bildirimler | `UserInvitationMail`, `LowStockAlert`, `ExpiryAlert`, Notifications |
| **Database Notifications** | Laravel Notification (database channel) | Uygulama içi bildirimler | `StockLowLevelNotification`, `StockAlertDigestNotification`, `StockRequestNotification` |
| **Queue (Database)** | Laravel Queue | Uyarı kontrol job'ları, bildirim job'ları | `CheckAllStockLevelsJob`, `CheckExpiringItemsJob`, `SendStockRequestNotificationJob` |
| **Cache (Database)** | Laravel Cache | Stok istatistikleri cache | `StockService@getStockStats` |
| **Spatie Permission** | `spatie/laravel-permission` ^7.3 | RBAC (rol ve izin yönetimi) | `User`, `Role`, Middleware, Policies |
| **Laravel Sanctum** | `laravel/sanctum` ^4.3 | SPA API auth (cookie-based) | `api.php` middleware, `User` model `HasApiTokens` |

---

# Bilinen Sorunlar & Teknik Borç

## Bu Hafta Düzeltilmeli (Kritik)

| # | Sorun | Etkilenen Dosyalar | Efor | Açıklama |
|---|-------|-------------------|------|----------|
| 1 | **User mass assignment riski** — `fillable` içinde `two_factor_secret`, `two_factor_confirmed_at` var | `app/Models/User.php` | **Küçük** | Bu alanları `fillable`'dan çıkar, sadece `TwoFactorService` üzerinden güncelle. |
| 2 | **Category/Clinic/Supplier Controller'larında inline validasyon** — `Validator::make()` kullanılıyor, `FormRequest` yok | `CategoryController.php`, `ClinicController.php`, `SupplierController.php` | **Orta** | Her controller için `Store*Request` ve `Update*Request` class'ları oluştur. |
| 3 | **StockAlert settings hardcoded** — DB'de saklanmıyor | `StockAlertController.php` (`getSettings`, `updateSettings`) | **Küçük** | `company_settings` tablosu veya `companies` tablosuna JSON kolon ekle. |
| 4 | **Kritik Controller'larda `authorize()` eksikliği** — `ClinicController`, `SupplierController` `Policy` kullanmıyor | `ClinicController.php`, `SupplierController.php` | **Orta** | `ClinicPolicy`, `SupplierPolicy` oluştur ve controller'larda `authorize()` ekle. |
| 5 | **Rate limiting eksikliği** — Sadece login endpoint'lerinde var | `routes/api.php` | **Küçük** | `Route::middleware('throttle:api')` veya spesifik throttle middleware ekle. |

## 1 Ay İçinde (Önemli)

| # | Sorun | Etkilenen Dosyalar | Efor | Açıklama |
|---|-------|-------------------|------|----------|
| 6 | **İsimlendirme tutarsızlığı** — `ProductInertiaController` `Api` namespace altında, aynı zamanda `Api\AuthController` ile `Auth\AuthenticatedSessionController` karışık | `ProductInertiaController.php`, klasör yapısı | **Küçük** | `ProductInertiaController` → `Pages\StockController` veya `Inertia\StockController` olarak taşı. |
| 7 | **Response format tutarsızlığı** — `JsonResponseTrait` kullanan controller'lar var (`StockController`, `AuthController`), ama `CategoryController`, `SupplierController` raw `response()->json()` kullanıyor | `CategoryController.php`, `SupplierController.php`, `StockRequestController.php` | **Küçük** | Tüm controller'larda `JsonResponseTrait` kullan ve `success()` / `error()` metodlarına geç. |
| 8 | **N+1 query riskleri** — `StockAlertService@sendAlertNotification`'da `alert->load()` yapılıyor ama `syncAlerts` tüm stokları `get()` ile çekiyor, eager loading yok | `StockAlertService.php` | **Orta** | `Stock::with(['product', 'clinic'])->get()` ekle. |
| 9 | **TenantScope Job/Command uyumsuzluğu** — `Auth::check()` false olunca `company_id` null kalabilir | `Tenantable.php` trait | **Orta** | Tüm Job'ları kontrol et, `company_id` açıkça set ediliyor mu kontrol et. `CheckAllStockLevelsJob` zaten `Stock::active()->with(...)` kullanıyor ama `withoutGlobalScopes()` gerekebilir. |
| 10 | **Frontend tip eksikliği** — `usePage<any>()`, `useAuth`'te `data: any` kullanımı yaygın | `useAuth.ts`, `usePermissions.ts`, birçok API service | **Orta** | Global tip tanımları oluştur (`AuthUser`, `ApiResponse<T>`). |
| 11 | **Eksik loading/error state'ler** — Bazı sayfalarda loading spinner ve error boundary kullanımı tutarsız | React Page component'leri | **Orta** | Her `useQuery` çağrısında `isLoading`, `isError` kontrolü ekle; `ErrorFallback` kullan. |
| 12 | **Dead code / kullanılmayan import'lar** — `useAuth` hook'unda `login` ve `adminLogin` console.log bırakılmış, `loading: false` sabit | `useAuth.ts` | **Küçük** | Temizle. |

## 3 Ay+ (Uzun Vadeli Refactor)

| # | Sorun | Etkilenen Dosyalar | Efor | Açıklama |
|---|-------|-------------------|------|----------|
| 13 | **Migration geçmişi karmaşıklığı** — 36+ migration, çoğu refactor ve `try/catch` ile hata bastırma içeriyor | `database/migrations/` | **Büyük** | Tüm migration'ları tek bir `schema dump` ile yeniden yaz; production'da `mysqldump` schema + seeders kullan. |
| 14 | **Repository pattern aşırı soyutlanması** — Basit CRUD repository'ler (`all()`, `find()`, `create()`) interface overhead yaratıyor | `app/Repositories/` | **Orta** | Basit modeller için repository'leri kaldır, doğrudan Eloquent kullan. Sadece karmaşık query'lerde (`StockRepository`) tut. |
| 15 | **API + Inertia hibrit karmaşıklığı** — Aynı uygulamada hem Inertia page render hem API JSON var, iki farklı auth flow | `routes/web.php`, `routes/api.php` | **Büyük** | Frontend tamamen SPA yap (Vite + React Router) veya tamamen Inertia yap; hibritden kurtul. |
| 16 | **SQLite production uygunluğu** — `.env.example`'da default SQLite | `.env.example` | **Küçük** | Production için MySQL/PostgreSQL ayarlarını `.env.example`'da varsayılan yap. |
| 17 | **Sub-unit stok hesaplama karmaşıklığı** — `StockCalculatorService`, `StockTransactionObserver`, `StockService` arasında dağınık | `StockCalculatorService.php`, `StockTransactionObserver.php` | **Orta** | Alt birim mantığını tek bir `StockUnitService` altında birleştir. |
| 18 | **Zustand kullanılmıyor ama yüklü** — `package.json`'da var ama kullanım görülmedi | `package.json` | **Küçük** | Ya kullan (global UI state için), ya kaldır. |
| 19 | **Test coverage eksikliği** — Playwright ve PHPUnit var ama test sayısı az görünüyor | `tests/` | **Büyük** | Feature test'ler yaz (her CRUD endpoint için), E2E test'ler yaz (kritik flow'lar). |

---

# Yeni Geliştirici Rehberi

## Kurulum (Adım Adım)

1. **Clone & bağımlılıklar**
   ```bash
   git clone <repo-url> denti-backend
   cd denti-backend
   composer install
   npm install
   ```

2. **.env ayarları**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   - `DB_CONNECTION=sqlite` (veya MySQL için ayarla)
   - `APP_URL=http://localhost`
   - `FRONTEND_URL=http://localhost:3000`
   - `SANCTUM_STATEFUL_DOMAINS=localhost:3000`
   - `SESSION_DOMAIN=localhost`
   - `QUEUE_CONNECTION=database`
   - `CACHE_STORE=database`
   - Mail ayarlarını (dev için `MAIL_MAILER=log`)

3. **Veritabanı kurulumu**
   ```bash
   touch database/database.sqlite  # SQLite için
   php artisan migrate
   php artisan db:seed  # Varsa seeders çalıştır
   ```

4. **Queue worker (background işlemler için)**
   ```bash
   php artisan queue:listen --tries=1
   # veya supervisor ile production'da
   ```

5. **Scheduler (cron job — stok kontrol job'ları için)**
   ```bash
   php artisan schedule:work
   # Production'da: crontab -e
   # * * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1
   ```

6. **İlk çalıştırma**
   ```bash
   # Terminal 1: Backend
   php artisan serve
   
   # Terminal 2: Frontend (Vite dev server)
   npm run dev
   ```

7. **Sık karşılaşılan hatalar**
   - **CORS hatası:** `FRONTEND_URL` ve `SANCTUM_STATEFUL_DOMAINS` aynı domain'i göstermeli.
   - **403 on API:** 2FA aktif kullanıcılar için önce `/api/auth/2fa/verify` çağrılmalı.
   - **TenantScope hatası:** Tinker/Job'da `Auth::check()` false ise `company_id` null kalır — model kaydetmeden önce `company_id` açıkça set et.
   - **Stok güncellenmiyor:** `StockTransactionObserver` register edilmemişse `php artisan optimize:clear` çalıştır.

## Sıfırdan Özellik Ekleme Rehberi (Backend → Frontend CRUD)

Bu projedeki pattern'e göre yeni bir modül eklemek için:

### 1. Backend (Örnek: `InventoryCount` — sayım modülü)

```
A. Model oluştur:
   php artisan make:model InventoryCount -mf
   - Migration: gerekli kolonları ekle (product_id, counted_quantity, difference, notes, company_id, user_id)
   - Model: use Tenantable, SoftDeletes; fillable ve ilişkileri tanımla
   - Model'e company_id eklemeyi unutma (Tenantable auto set eder ama Job'larda set et)

B. Repository:
   - InventoryCountRepositoryInterface oluştur
   - InventoryCountRepository oluştur (diğer repository'lerden kopyala)
   - AppServiceProvider'da bind et

C. Service:
   - InventoryCountService oluştur
   - Constructor'da Repository inject et
   - CRUD metodları + özel iş mantığı

D. Form Request:
   - StoreInventoryCountRequest, UpdateInventoryCountRequest
   - company_id scoped unique validasyonları ekle (varsa)

E. Resource:
   - InventoryCountResource (API response formatı)

F. Controller:
   - InventoryCountController (Api namespace altında)
   - use JsonResponseTrait
   - Constructor'da Service inject et
   - Her metod try/catch + Log::error

G. Policy:
   - InventoryCountPolicy (viewAny, view, create, update, delete)
   - AppServiceProvider'da Gate::policy() ile register et

H. Routes:
   - routes/api.php'ye ekle:
     Route::apiResource('inventory-counts', InventoryCountController::class)->middleware('permission:view-stocks')
```

### 2. Frontend

```
A. Modül klasörü:
   mkdir -p resources/js/Modules/inventory-counts/{Pages,Components,Hooks,Services,Types}

B. API Service:
   // Modules/inventory-counts/Services/inventoryCountApi.ts
   import { api } from '@/Services/api';
   export const inventoryCountApi = {
       getAll: () => api.get('/inventory-counts'),
       getById: (id: number) => api.get(`/inventory-counts/${id}`),
       create: (data: any) => api.post('/inventory-counts', data),
       update: (id: number, data: any) => api.put(`/inventory-counts/${id}`, data),
       delete: (id: number) => api.delete(`/inventory-counts/${id}`),
   };

C. Types:
   // Modules/inventory-counts/Types/inventory-count.types.ts
   export interface InventoryCount {
       id: number;
       product_id: number;
       counted_quantity: number;
       // ...
   }

D. Hooks:
   // Modules/inventory-counts/Hooks/useInventoryCounts.ts
   import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
   import { inventoryCountApi } from '../Services/inventoryCountApi';
   
   export const useInventoryCounts = () => useQuery({
       queryKey: ['inventory-counts'],
       queryFn: () => inventoryCountApi.getAll(),
   });
   
   export const useCreateInventoryCount = () => {
       const qc = useQueryClient();
       return useMutation({
           mutationFn: inventoryCountApi.create,
           onSuccess: () => qc.invalidateQueries({ queryKey: ['inventory-counts'] }),
       });
   };

E. Page:
   // Modules/inventory-counts/Pages/InventoryCountsPage.tsx
   const { data, isLoading } = useInventoryCounts();
   const { mutate } = useCreateInventoryCount();
   // Ant Design Table + Form kullan

F. AppLayout menüsüne ekle:
   // AppLayout.tsx'te regularMenuItems array'ine ekle
   { key: '/inventory-counts', icon: <AuditOutlined />, label: <Link href="/inventory-counts">Sayımlar</Link> }

G. routes/web.php'ye Inertia route ekle:
   Route::get('/inventory-counts', fn() => Inertia::render('InventoryCount/Index'));
```

## Bu Projede Uygulanan Kurallar

### İsimlendirme Kuralları
- **Controller'lar:** `Api\*Controller` (JSON API) veya `Auth\*Controller` (Session)
- **Service'ler:** `*Service` — constructor injection ile
- **Repository'ler:** `*Repository` + `*RepositoryInterface`
- **Form Request'ler:** `Store*Request`, `Update*Request`
- **Resource'lar:** `*Resource`
- **Model'ler:** PascalCase, singular (örn. `Stock`, `StockRequest`)
- **Frontend modülleri:** `Modules/{feature}/{Pages,Components,Hooks,Services,Types}/`
- **Frontend dosyaları:** PascalCase (örn. `InventoryCountsPage.tsx`, `useInventoryCounts.ts`)

### Takip Edilmesi Gereken Pattern'lar
1. **Service → Repository → Model** akışı (DI ile)
2. **Controller'da try/catch** + `Log::error()` + `JsonResponseTrait`
3. **Form Request validasyonu** (inline `Validator::make()` yerine)
4. **Policy kullanımı** (`authorize()` veya `can()` middleware)
5. **Tenantable trait** tüm multi-tenant model'lere
6. **React Query** server state yönetimi için
7. **Inertia shared auth props** client auth state için

### Kesinlikle Yapılmaması Gerekenler
1. **Controller'da doğrudan DB query yazma** — Repository/Service üzerinden git.
2. **`response()->json()` raw kullanımı** — `JsonResponseTrait`'i kullan.
3. **Model'de `fillable` içine sensitive alanlar koyma** (`two_factor_secret`, `password` gibi).
4. **Job/Command/Seeder'da `Auth::user()->company_id` kullanma** — `Auth::check()` false döner, model'e kaydetmeden önce açıkça `company_id` set et.
5. **Frontend'de `any` tipi kullanımı** — Tip tanımları oluştur.
6. **Route'a auth middleware eklemeden** company-scoped veri döndürme.
7. **Soft delete yerine `delete()` doğrudan çağırma** — `Stock`, `Product` vb. soft delete kullanır.

## Hızlı Başvuru Kartı

### En Önemli Route'lar
```
POST /api/login                    → Auth (company_code + username + password)
GET  /api/auth/me                → Mevcut kullanıcı
GET  /api/dashboard/stats        → Dashboard kart verisi
GET  /api/stocks                 → Stok listesi (filtre + paginate)
POST /api/stocks/{id}/adjust     → Stok düzeltme
POST /api/stocks/{id}/use        → Stok kullanım kaydı
GET  /api/stock-alerts           → Uyarı listesi (auto sync)
GET  /api/stock-transactions     → İşlem geçmişi (audit log)
```

### En Önemli Model'ler ve İlişkileri
```
User → belongsTo(Company), belongsTo(Clinic), hasRole(spatie)
Company → hasMany(User), hasMany(Clinic), hasMany(Stock)
Clinic → belongsTo(Company), hasMany(Stock), hasMany(StockRequest)
Product → belongsTo(Company), hasMany(Stock, 'batches')
Stock → belongsTo(Product), belongsTo(Supplier), belongsTo(Clinic)
StockTransaction → belongsTo(Stock), belongsTo(User), belongsTo(Clinic)
StockRequest → belongsTo(Clinic, requester), belongsTo(Clinic, requested_from), belongsTo(Stock)
StockAlert → belongsTo(Product), belongsTo(Stock), belongsTo(Clinic)
```

### React Sayfası Nasıl Veri Alıyor
1. **Inertia props:** `usePage().props` (auth, flash message)
2. **API çağrısı:** `useQuery` / `useMutation` (TanStack Query) → `Services/*Api.ts` → Axios → Laravel API → JSON

### Route Nasıl Korunur, Yetki Nasıl Kontrol Edilir
```php
// Route seviyesi
Route::middleware(['auth:sanctum', '2fa.verified', 'permission:view-stocks'])->get(...);

// Controller seviyesi
$this->authorize('viewAny', Stock::class);
$this->authorize('update', $stock);  // Policy kullanımı

// Frontend seviyesi
const { hasPermission, isAdmin } = usePermissions();
{hasPermission('view-stocks') && <StokListesi />}
```

### Komutlar
```bash
# Migration
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

# Queue
php artisan queue:listen --tries=1
php artisan queue:work --stop-when-empty

# Cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

# Test
php artisan test
npx playwright test

# Development
php artisan serve
npm run dev
```

---

# Sözlük

| Terim | Açıklama |
|-------|----------|
| **Batch** | Bir ürünün belirli bir alım/partisine karşılık gelen `Stock` kaydı. Aynı ürünün farklı kliniklerde/alımlarında birden fazla batch olabilir. |
| **Sub-unit (Alt Birim)** | Ana birim dışında ikincil bir ölçü birimi. Örn. "kutu" ana birim, "ampul" alt birim. `sub_unit_multiplier` ile dönüşüm yapılır. |
| **Tenant / TenantScope** | Çok şirketli mimaride her şirketin verisini birbirinden ayıran `company_id` filtresi. Tüm model query'lerine otomatik eklenir. |
| **Tenantable** | Model trait'i — `TenantScope` ekler ve `creating` event'inde `company_id` auto-set eder. |
| **Stock Request** | Bir kliniğin başka bir klinikten stok talebi. Durumları: pending → approved → completed veya rejected. |
| **Stock Transfer** | Stok fiziksel olarak bir klinikten diğerine taşınır. `StockTransfer` modeli bu süreci yönetir. |
| **Adjustment** | Stok miktarının manuel düzeltilmesi (artırma, azaltma, senkronizasyon). `StockTransaction` kaydı oluşturur. |
| **Usage** | Stok kullanımı (örn. hasta tedavisinde). `StockTransaction` tipi `usage` olarak kaydedilir. |
| **Alert / Uyarı** | Düşük stok, kritik stok, SKT yaklaşma, SKT geçme gibi durumları belirten kayıt. `StockAlert` modeli. |
| **Observer** | Model event'lerine (created, updated, deleted) tepki veren sınıf. `StockTransactionObserver` stok miktarını günceller. `StockAlertObserver` mail gönderir. |
| **Inertia** | Laravel + React arasındaki köprü. Sayfa geçişlerini SPA gibi yapar ama backend'ten HTML + JSON prop alır. |
| **Ziggy** | Laravel route'larını JavaScript'te kullanabilmeni sağlar. `@inertiajs/react` ile entegre. |
| **Spatie Permission** | Laravel için rol ve izin yönetimi paketi. `Role`, `Permission`, `hasRole()`, `hasPermissionTo()`. |
| **Recovery Code** | 2FA aktif kullanıcıların TOTP cihazını kaybetmesi durumunda kullanabileceği tek kullanımlık kodlar. |
| **Company Code** | Kullanıcı girişinde şirketi belirlemek için kullanılan kısa kod (örn. `acme-dental`). `Company.code` kolonu. |
| **Owner** | Şirket sahibi rolü. `User::ROLE_OWNER` sabiti. Şirket yönetimi yetkileri var, silinemez. |
| **Super Admin** | Sistem yöneticisi. Tüm şirket verilerini görebilir. `TenantScope` bypass yetkisi var. |
| **Sanctum** | Laravel'in SPA API auth paketi. Cookie-based tokenless auth sağlar. |
| **Pessimistic Locking** | `lockForUpdate()` ile DB satır kilitleme. Eşzamanlı stok kullanımında race condition önler. |
| **Company Owned Rule** | Validasyon rule'ı: `product_id`, `clinic_id` vb. alanların mevcut kullanıcının şirketine ait olduğunu doğrular. |
| **Digest Notification** | Tek tek değil, toplu stok uyarı özetini bir e-posta olarak gönderen bildirim. `StockAlertDigestNotification`. |

# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: auth.spec.ts >> Authentication >> successful login redirects to dashboard
- Location: tests/e2e/auth.spec.ts:8:3

# Error details

```
Error: expect(page).toHaveURL(expected) failed

Expected: "http://127.0.0.1:8011/"
Received: "http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png"
Timeout:  5000ms

Call log:
  - Expect "toHaveURL" with timeout 5000ms
    9 × unexpected value "http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png"

```

# Page snapshot

```yaml
- generic [ref=e3]:
  - generic [ref=e4]:
    - link "Logo" [ref=e6] [cursor=pointer]:
      - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png
      - img "Logo" [ref=e7]
    - generic [ref=e10]:
      - link "Gösterge Paneli" [ref=e12] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png
        - text: Gösterge Paneli
      - generic [ref=e14]: Envanter
      - generic [ref=e15]:
        - generic [ref=e16]: Stok Yönetimi
        - generic [ref=e17]:
          - link "Stok Listesi" [ref=e19] [cursor=pointer]:
            - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/stocks
            - text: Stok Listesi
          - link "Kategoriler" [ref=e21] [cursor=pointer]:
            - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/stock-categories
            - text: Kategoriler
          - link "Tedarikçiler" [ref=e23] [cursor=pointer]:
            - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/suppliers
            - text: Tedarikçiler
      - generic [ref=e25]: Operasyonlar
      - link "Klinikler" [ref=e27] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/clinics
        - text: Klinikler
      - link "Stok Talepleri" [ref=e29] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/stock-requests
        - text: Stok Talepleri
      - link "Uyarılar" [ref=e31] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/alerts
        - text: Uyarılar
      - generic [ref=e33]: Raporlama
      - link "Raporlar" [ref=e35] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/reports
        - text: Raporlar
      - generic [ref=e37]: Sistem
      - link "Personel" [ref=e39] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/employees
        - text: Personel
      - link "Yetkiler" [ref=e41] [cursor=pointer]:
        - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/roles
        - text: Yetkiler
  - generic [ref=e42]:
    - generic [ref=e44]:
      - generic [ref=e45]:
        - link "Logo" [ref=e46] [cursor=pointer]:
          - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png
          - img "Logo" [ref=e47]
        - generic [ref=e48]:
          - button "Dashboard" [ref=e49]: Dashboard
          - generic [ref=e50]:
            - generic [ref=e52]: "Hızlı Erişim:"
            - link "Stoklar" [ref=e54] [cursor=pointer]:
              - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/stocks
            - link "Klinikler" [ref=e56] [cursor=pointer]:
              - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/clinics
      - generic [ref=e57]:
        - generic [ref=e60]:
          - textbox "Search..." [ref=e62]
          - generic [ref=e63]: Arama yapmak için en az 2 karakter giriniz.
        - generic [ref=e64]:
          - link:
            - /url: "#"
          - generic [ref=e65]:
            - link "Aydınlık" [ref=e67] [cursor=pointer]:
              - /url: "#"
              - text: Aydınlık
            - link "Karanlık" [ref=e69] [cursor=pointer]:
              - /url: "#"
              - text: Karanlık
        - generic [ref=e70]:
          - generic [ref=e71]:
            - generic [ref=e72]: E2E User Personel
            - generic [ref=e74]: E
          - generic [ref=e75]:
            - generic [ref=e77]:
              - generic [ref=e79]: E
              - generic [ref=e80]:
                - generic [ref=e81]: E2E User
                - link "user@example.com" [ref=e82] [cursor=pointer]:
                  - /url: "#"
            - link "Profilim" [ref=e84] [cursor=pointer]:
              - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/profile
            - button "Çıkış Yap" [ref=e87]
    - generic [ref=e88]:
      - generic [ref=e91]:
        - heading "E2E Company" [level=1] [ref=e92]
        - list [ref=e93]:
          - listitem [ref=e94]: Operasyon ozet gorunumu
      - generic [ref=e96]:
        - generic [ref=e97]:
          - generic [ref=e99]:
            - generic [ref=e101]: 1 Toplam Calisan
            - generic [ref=e102]: Canli backend verisi uzerinden olusan ozet.
          - generic [ref=e104]:
            - generic [ref=e106]: 1 Toplam Klinik
            - generic [ref=e107]: Canli backend verisi uzerinden olusan ozet.
          - generic [ref=e109]:
            - generic [ref=e111]: 0 Stok Kalemi
            - generic [ref=e112]: Canli backend verisi uzerinden olusan ozet.
          - generic [ref=e114]:
            - generic [ref=e116]: 0 Toplam Tedarikci
            - generic [ref=e117]: Canli backend verisi uzerinden olusan ozet.
        - generic [ref=e118]:
          - generic [ref=e119]:
            - generic [ref=e121]:
              - text: Gunluk Ozet
              - heading "Operasyon icgorusleri" [level=3] [ref=e122]
            - generic [ref=e124]:
              - generic [ref=e125]:
                - text: S
                - generic [ref=e126]:
                  - generic [ref=e127]: Stok nabzi
                  - generic [ref=e128]: 0 aktif kalem dogrudan merkezi sorgudan geliyor.
              - generic [ref=e129]:
                - text: K
                - generic [ref=e130]:
                  - generic [ref=e131]: Klinik yayilimi
                  - generic [ref=e132]: 1 klinik icin tek shell ve ortak filtre dili aktif.
              - generic [ref=e133]:
                - text: E
                - generic [ref=e134]:
                  - generic [ref=e135]: Ekip yogunlugu
                  - generic [ref=e136]: 1 kullanici yeni Blade panelden yonetilebilir gorunumde.
          - generic [ref=e137]:
            - generic [ref=e139]:
              - text: Hizli Erisim
              - heading "Kritik moduller" [level=3] [ref=e140]
            - generic [ref=e142]:
              - link "ST Stoklara git Kalemleri, detaylari ve mevcut seviyeleri hizli kontrol et." [ref=e143] [cursor=pointer]:
                - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/stocks
                - text: ST
                - generic [ref=e144]:
                  - generic [ref=e145]: Stoklara git
                  - generic [ref=e146]: Kalemleri, detaylari ve mevcut seviyeleri hizli kontrol et.
              - link "TR Talepleri ac Klinikler arasi akisi ve bekleyen hareketleri goru." [ref=e147] [cursor=pointer]:
                - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/stock-requests
                - text: TR
                - generic [ref=e148]:
                  - generic [ref=e149]: Talepleri ac
                  - generic [ref=e150]: Klinikler arasi akisi ve bekleyen hareketleri goru.
              - link "AL Uyarilari izle Kritik stok, SKT ve operasyon sinyallerini tek yerden takip et." [ref=e151] [cursor=pointer]:
                - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/alerts
                - text: AL
                - generic [ref=e152]:
                  - generic [ref=e153]: Uyarilari izle
                  - generic [ref=e154]: Kritik stok, SKT ve operasyon sinyallerini tek yerden takip et.
              - link "TD Tedarik agi Tedarikci kartlarini ve temel iletisim bilgilerini yonet." [ref=e155] [cursor=pointer]:
                - /url: http://127.0.0.1:8011/metronic/assets/media/misc/auth-bg.png/suppliers
                - text: TD
                - generic [ref=e156]:
                  - generic [ref=e157]: Tedarik agi
                  - generic [ref=e158]: Tedarikci kartlarini ve temel iletisim bilgilerini yonet.
    - generic [ref=e161]:
      - text: 2026©
      - link "Denti Core" [ref=e162] [cursor=pointer]:
        - /url: "#"
```

# Test source

```ts
  1  | import { test, expect } from '@playwright/test';
  2  | 
  3  | const clinicCode = process.env.E2E_CLINIC_CODE || 'deneme01';
  4  | const username = process.env.E2E_USERNAME || 'deneme';
  5  | const password = process.env.E2E_PASSWORD || 'oRtc613LFgca';
  6  | 
  7  | test.describe('Authentication', () => {
  8  |   test('successful login redirects to dashboard', async ({ page }) => {
  9  |     await page.goto('/login');
  10 |     await page.locator('input[name="clinic_code"]').fill(clinicCode);
  11 |     await page.locator('input[name="username"]').fill(username);
  12 |     await page.locator('input[name="password"]').fill(password);
  13 |     await page.getByRole('button', { name: /Giris Yap/i }).click();
> 14 |     await expect(page).toHaveURL('/');
     |                        ^ Error: expect(page).toHaveURL(expected) failed
  15 |     await expect(page.getByText(/Operasyon/i)).toBeVisible();
  16 |   });
  17 | 
  18 |   test('shows error message on invalid credentials', async ({ page }) => {
  19 |     await page.goto('/login');
  20 |     await page.locator('input[name="clinic_code"]').fill('invalid_code');
  21 |     await page.locator('input[name="username"]').fill('wrong_user');
  22 |     await page.locator('input[name="password"]').fill('wrong_pass');
  23 |     await page.getByRole('button', { name: /Giris Yap/i }).click();
  24 |     await expect(page.getByText(/Gecersiz|Geçersiz/i)).toBeVisible();
  25 |   });
  26 | });
  27 | 
```
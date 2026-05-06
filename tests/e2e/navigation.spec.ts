import { test, expect } from '@playwright/test';

const clinicCode = process.env.E2E_CLINIC_CODE || 'deneme01';
const username = process.env.E2E_USERNAME || 'deneme';
const password = process.env.E2E_PASSWORD || 'oRtc613LFgca';

const pages = [
  { url: '/', heading: /Dashboard|Operasyon/i },
  { url: '/stocks', heading: /Stok/i },
  { url: '/stock-categories', heading: /Kategori/i },
  { url: '/suppliers', heading: /Tedarikci|Tedarikçi/i },
  { url: '/clinics', heading: /Klinik/i },
  { url: '/stock-requests', heading: /Talep/i },
  { url: '/alerts', heading: /Uyari|Uyarı/i },
  { url: '/reports', heading: /Rapor/i },
  { url: '/employees', heading: /Personel/i },
  { url: '/roles', heading: /Rol|Yetki/i },
  { url: '/todos', heading: /Todo/i },
  { url: '/profile', heading: /Profil/i },
];

test.describe('Navigation smoke', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="clinic_code"]').fill(clinicCode);
    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await page.getByRole('button', { name: /Giris Yap/i }).click();
    await expect(page).toHaveURL('/');
  });

  for (const entry of pages) {
    test(`page ${entry.url} renders without console errors`, async ({ page }) => {
      const consoleErrors: string[] = [];
      page.on('console', (message) => {
        if (message.type() === 'error') {
          consoleErrors.push(message.text());
        }
      });

      page.on('pageerror', (error) => {
        consoleErrors.push(error.message);
      });

      await page.goto(entry.url);
      await expect(page.getByRole('heading', { name: entry.heading }).first()).toBeVisible();
      expect(consoleErrors, `console errors on ${entry.url}`).toEqual([]);
    });
  }
});

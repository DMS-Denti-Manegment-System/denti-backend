import { test, expect } from '@playwright/test';

const clinicCode = process.env.E2E_CLINIC_CODE || 'deneme01';
const username = process.env.E2E_USERNAME || 'deneme';
const password = process.env.E2E_PASSWORD || 'oRtc613LFgca';

test.describe('Products Management', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="clinic_code"]').fill(clinicCode);
    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await page.getByRole('button', { name: /Giris Yap/i }).click();
    await expect(page).toHaveURL('/');
  });

  test('can navigate to products page', async ({ page }) => {
    await page.goto('/stocks');
    await expect(page.getByRole('heading', { name: /Stok Yonetimi|Stok Yönetimi/i })).toBeVisible();
    await expect(page).not.toHaveURL('/login');
  });
});

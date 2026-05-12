import { test, expect } from '@playwright/test';

const username = process.env.E2E_USERNAME || 'deneme';
const password = process.env.E2E_PASSWORD || 'oRtc613LFgca';

test.describe('Authentication', () => {
  test('successful login redirects to dashboard', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await page.getByRole('button', { name: /Giris Yap/i }).click();
    await expect(page).toHaveURL('/');
    await expect(page.getByText(/Operasyon/i)).toBeVisible();
  });

  test('shows error message on invalid credentials', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[name="username"]').fill('wrong_user');
    await page.locator('input[name="password"]').fill('wrong_pass');
    await page.getByRole('button', { name: /Giris Yap/i }).click();
    await expect(page.getByText(/Gecersiz|Geçersiz/i)).toBeVisible();
  });
});

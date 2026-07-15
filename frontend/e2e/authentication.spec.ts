import { test, expect } from '@playwright/test';

test.describe('Authentication Flow', () => {
  test('should redirect unauthenticated users to login', async ({ page }) => {
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login/);
  });

  test('should show validation errors for invalid email/password formats', async ({ page }) => {
    await page.goto('/login');

    await page.fill('input[type="email"]', 'invalid-email');
    await page.fill('input[type="password"]', 'short');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Please enter a valid email address.')).toBeVisible();
    await expect(page.locator('text=Password must be at least 8 characters long.')).toBeVisible();
  });

  test('should login successfully with correct credentials and redirect to dashboard', async ({ page }) => {
    // Mock the login API request
    await page.route('**/api/auth/login', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          accessToken: 'mock-access-token',
          refreshToken: 'mock-refresh-token',
          user: {
            id: 1,
            name: 'System Admin',
            email: 'admin@skyfi.com',
            roles: ['Administrator'],
          },
        }),
      });
    });

    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@skyfi.com');
    await page.fill('input[type="password"]', 'SuperSecurePassword123');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.locator('text=System Admin')).toBeVisible();
  });
});

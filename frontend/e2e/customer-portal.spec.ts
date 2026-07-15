import { test, expect } from '@playwright/test';

test.describe('Customer Portal E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Mock authenticated Customer Session
    await page.addInitScript(() => {
      window.localStorage.setItem('auth_session', JSON.stringify({
        accessToken: 'mock-customer-token',
        user: { id: 42, name: 'Asif Ali', email: 'asif@gmail.com', roles: ['Customer'] }
      }));
    });

    // Mock customer connection info
    await page.route('**/api/portal/connection', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            id: 99,
            package_name: 'Fiber Starter 20 Mbps',
            ip_address: '192.168.10.150',
            status: 'active',
            mac_address: '00:1A:2B:3C:4D:5E'
          }
        }),
      });
    });
  });

  test('should display customer connection details and monthly usage on portal dashboard', async ({ page }) => {
    await page.goto('/portal/dashboard');
    await expect(page.locator('text=Fiber Starter 20 Mbps')).toBeVisible();
    await expect(page.locator('text=192.168.10.150')).toBeVisible();
    await expect(page.locator('text=Active')).toBeVisible();
  });

  test('should view invoice list and click to pay', async ({ page }) => {
    await page.route('**/api/portal/invoices', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 201, invoice_number: 'INV-PT-2026', total_amount: 1500.00, status: 'unpaid', due_date: '2026-08-01' }
          ]
        }),
      });
    });

    await page.goto('/portal/billing');
    await expect(page.locator('text=INV-PT-2026')).toBeVisible();
    await expect(page.locator('text=PKR 1,500')).toBeVisible();

    await page.click('text=Pay Now');
    await expect(page).toHaveURL(/\/portal\/billing\/201/);
  });
});

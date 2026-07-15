import { test, expect } from '@playwright/test';

test.describe('Billing Module E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Mock authenticated user as billing manager
    await page.addInitScript(() => {
      window.localStorage.setItem('auth_session', JSON.stringify({
        accessToken: 'mock-access-token',
        user: { id: 2, name: 'John Doe', email: 'billing@skyfi.com', roles: ['Billing Manager'] }
      }));
    });

    // Mock invoices list
    await page.route('**/api/billing/invoices', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 101, invoice_number: 'INV-20260715-A1', customer_name: 'Imran Khan', amount: '2500.00', status: 'pending' },
            { id: 102, invoice_number: 'INV-20260715-B2', customer_name: 'Nawaz Sharif', amount: '3500.00', status: 'paid' }
          ]
        }),
      });
    });
  });

  test('should display list of invoices', async ({ page }) => {
    await page.goto('/billing');
    await expect(page.locator('text=INV-20260715-A1')).toBeVisible();
    await expect(page.locator('text=INV-20260715-B2')).toBeVisible();
    await expect(page.locator('text=Imran Khan')).toBeVisible();
  });

  test('should view specific invoice detail and perform a status transition', async ({ page }) => {
    await page.route('**/api/billing/invoices/101', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          id: 101,
          invoice_number: 'INV-20260715-A1',
          customer_id: 42,
          customer_name: 'Imran Khan',
          total_amount: 2500.00,
          status: 'pending',
          items: [
            { id: 1, description: '100 Mbps Unlimited Fiber Package', amount: 2500.00 }
          ]
        }),
      });
    });

    await page.route('**/api/billing/invoices/101/status', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ status: 'issued' }),
      });
    });

    await page.goto('/billing/101');
    await expect(page.locator('text=INV-20260715-A1')).toBeVisible();
    await expect(page.locator('text=100 Mbps Unlimited Fiber Package')).toBeVisible();

    await page.click('button:has-text("Mark as Issued")');
    await expect(page.locator('text=Status changed successfully')).toBeVisible();
  });
});

import { test, expect } from '@playwright/test';

test.describe('Finance Module E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Mock authenticated user as Accountant/Admin
    await page.addInitScript(() => {
      window.localStorage.setItem('auth_session', JSON.stringify({
        accessToken: 'mock-access-token',
        user: { id: 3, name: 'Finance Admin', email: 'finance@skyfi.com', roles: ['Financial Controller'] }
      }));
    });

    // Mock finance metrics
    await page.route('**/api/finance/metrics', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: {
            total_revenue: 1250000.00,
            pending_receivables: 450000.00,
            operating_expenses: 320000.00,
            net_profit_margin: 74.4
          }
        }),
      });
    });
  });

  test('should display finance dashboard with core KPI cards', async ({ page }) => {
    await page.goto('/finance');
    await expect(page.locator('text=PKR 1,250,000')).toBeVisible();
    await expect(page.locator('text=PKR 450,000')).toBeVisible();
    await expect(page.locator('text=74.4%')).toBeVisible();
  });

  test('should allow entering an expense and adding it to the ledger', async ({ page }) => {
    await page.route('**/api/finance/transactions', async (route) => {
      await route.fulfill({
        status: 201,
        contentType: 'application/json',
        body: JSON.stringify({
          id: 501,
          type: 'expense',
          category: 'Office Rent',
          amount: 85000.00,
          description: 'Monthly office rent payment',
          date: '2026-07-15'
        }),
      });
    });

    await page.goto('/finance');
    await page.click('button:has-text("Add Expense")');
    await page.fill('input[name="amount"]', '85000');
    await page.selectOption('select[name="category"]', 'Office Rent');
    await page.fill('textarea[name="description"]', 'Monthly office rent payment');
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Expense added successfully')).toBeVisible();
  });
});

import { test, expect } from '@playwright/test';

test.describe('Workflow Automation E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Mock Admin Session
    await page.addInitScript(() => {
      window.localStorage.setItem('auth_session', JSON.stringify({
        accessToken: 'mock-access-token',
        user: { id: 1, name: 'Workflow Admin', email: 'admin@skyfi.com', roles: ['Administrator'] }
      }));
    });

    // Mock workflows list
    await page.route('**/api/workflows', async (route) => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          data: [
            { id: 1, name: 'Auto-suspend Connection on Overdue', trigger: 'invoice.overdue', active: true },
            { id: 2, name: 'Welcome Notification on New Connection', trigger: 'connection.created', active: false }
          ]
        }),
      });
    });
  });

  test('should list automated workflows and their active status', async ({ page }) => {
    await page.goto('/workflows');
    await expect(page.locator('text=Auto-suspend Connection on Overdue')).toBeVisible();
    await expect(page.locator('text=Welcome Notification on New Connection')).toBeVisible();
  });

  test('should allow creating a new workflow definition with triggers and actions', async ({ page }) => {
    await page.route('**/api/workflows', async (route) => {
      if (route.request().method() === 'POST') {
        await route.fulfill({
          status: 201,
          contentType: 'application/json',
          body: JSON.stringify({
            id: 3,
            name: 'Low Balance SMS Alert',
            trigger: 'account.low_balance',
            active: true
          }),
        });
      }
    });

    await page.goto('/workflows');
    await page.click('button:has-text("Create Workflow")');
    await page.fill('input[name="name"]', 'Low Balance SMS Alert');
    await page.selectOption('select[name="trigger"]', 'account.low_balance');
    await page.click('button:has-text("Save Workflow")');

    await expect(page.locator('text=Workflow saved successfully')).toBeVisible();
  });
});

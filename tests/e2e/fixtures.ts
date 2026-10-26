import { test as base, expect, Page } from '@playwright/test';

type HospitalFixtures = {
  authenticatedPage: Page;
};

/**
 * Custom Playwright Fixtures
 * 
 * Implements deterministic programmatic authentication to avoid re-testing
 * the login form in every test spec.
 */
export const test = base.extend<HospitalFixtures>({
  authenticatedPage: async ({ page, baseURL }, use) => {
    const url = baseURL || 'http://localhost:8082';

    // 1. Fetch CSRF token from login page
    await page.goto(`${url}/`);
    const tokenInput = page.locator('input[name="_token"]');
    await expect(tokenInput).toBeAttached({ timeout: 5000 });
    const csrfToken = await tokenInput.inputValue();

    // 2. Authenticate session
    await page.request.post(`${url}/users/login`, {
      form: {
        _token: csrfToken,
        login: 'admin',
        password: '123456',
      },
    });

    // 3. Navigate into authenticated dashboard
    await page.goto(`${url}/dashboard`);
    await expect(page).toHaveURL(`${url}/dashboard`);

    await use(page);
  },
});

export { expect };

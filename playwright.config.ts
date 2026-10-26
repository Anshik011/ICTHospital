import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E Configuration - ICTHospital
 * 
 * Rules:
 * - Deterministic execution: 0 retries in local development and stable CI lanes
 * - Artifact capture: trace, screenshot, and video retained on failure
 * - User-facing semantic selectors prioritized
 */
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 2 : undefined,
  outputDir: 'test-results',
  reporter: [
    ['html', { open: 'never' }],
    ['list']
  ],
  use: {
    baseURL: process.env.APP_URL || 'http://localhost:8082',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 10000,
    navigationTimeout: 15000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
  ],
});

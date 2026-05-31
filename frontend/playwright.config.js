// @ts-check
import { defineConfig, devices } from '@playwright/test'

/**
 * Playwright config for CareerBridge end-to-end tests.
 *
 * Assumes:
 *   - The PHP backend is running on http://localhost:8000
 *     (run `php -S localhost:8000 -t public` from the backend/ folder)
 *   - The Vite dev server is running on http://localhost:5173
 *     (Playwright will auto-start it via the `webServer` block below)
 *   - The MySQL database has been seeded:
 *       mysql -u root < database/schema.sql
 *       mysql -u root < database/seed.sql
 */
export default defineConfig({
  testDir: './tests',
  // Run tests in files in parallel
  fullyParallel: false,
  // Fail the build on CI if you accidentally left test.only in the source
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1, // serial: tests share the same backend DB
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL: 'http://localhost:5173',
    trace:   'retain-on-failure',
    screenshot: 'only-on-failure',
    video:   'retain-on-failure',
    actionTimeout: 10_000,
    navigationTimeout: 15_000
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] }
    }
  ],

  // Auto-start the Vite dev server when running `npx playwright test`.
  // Comment this out if you prefer to start it manually.
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: true,
    timeout: 60_000
  }
})

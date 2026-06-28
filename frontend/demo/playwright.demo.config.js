// @ts-check
import { defineConfig, devices } from '@playwright/test'

/**
 * Demo recording config — Samin's M2 (Jobs) + M3 (Applications) walkthrough.
 *
 * Outputs a single 1920x1080 video at frontend/demo/output/<spec>/video.webm
 * which can be converted to mp4 for editing.
 */
export default defineConfig({
  testDir: './',
  testMatch: 'demo.spec.js',
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list']],

  outputDir: './output',

  use: {
    baseURL: 'http://localhost:5173',
    headless: false,                 // show browser so user can see what's recorded
    viewport: { width: 1920, height: 1080 },
    video: {
      mode: 'on',
      size: { width: 1920, height: 1080 }
    },
    actionTimeout: 15_000,
    navigationTimeout: 20_000,
    launchOptions: {
      slowMo: 600                    // pace the actions so a viewer can follow
    }
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] }
    }
  ],

  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: true,
    timeout: 60_000,
    cwd: '..'
  }
})

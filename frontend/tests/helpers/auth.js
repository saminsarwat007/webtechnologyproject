// @ts-check
import { expect } from '@playwright/test'

/**
 * Default seeded credentials (see database/seed.sql).
 * Every account uses the same password.
 */
export const ACCOUNTS = {
  superAdmin: { email: 'superadmin@careerbridge.my', password: 'Password123!' },
  admin:      { email: 'farizal@careerbridge.my',    password: 'Password123!' },
  student1:   { email: 'areeb@student.utm.my',       password: 'Password123!' },
  student2:   { email: 'samin@student.utm.my',       password: 'Password123!' }
}

/**
 * Log in through the UI form. Waits until the auth-protected landing
 * page is rendered (NavBar visible).
 *
 * @param {import('@playwright/test').Page} page
 * @param {{ email: string, password: string }} account
 */
export async function login (page, account) {
  await page.goto('/login')
  await page.getByLabel('Email').fill(account.email)
  await page.getByLabel('Password', { exact: true }).fill(account.password)
  await page.getByRole('button', { name: /sign in/i }).click()

  // The router redirects to /student/dashboard or /admin/dashboard
  await expect(page).toHaveURL(/\/(student|admin)\/dashboard/)
  // NavBar shows the user's name in the top right
  await expect(page.locator('header').first()).toBeVisible()
}

/**
 * Log out through the NavBar (works on both mobile & desktop layouts).
 *
 * @param {import('@playwright/test').Page} page
 */
export async function logout (page) {
  // Desktop button (md:flex)
  const desktopLogout = page.locator('header nav button', { hasText: 'Logout' })
  if (await desktopLogout.isVisible().catch(() => false)) {
    await desktopLogout.click()
  } else {
    // Mobile fallback
    await page.locator('header button[aria-label="Toggle navigation"]').click()
    await page.locator('header button', { hasText: 'Logout' }).first().click()
  }
  await expect(page).toHaveURL(/\/login/)
}

/**
 * Unique suffix so tests can create fresh records without colliding with
 * each other or with seeded fixtures.
 */
export function uniqueSuffix () {
  return Date.now().toString(36) + Math.random().toString(36).slice(2, 6)
}

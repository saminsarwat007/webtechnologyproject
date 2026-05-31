// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login, uniqueSuffix } from './helpers/auth.js'

/**
 * Module 8 — Label & Tag Management (Owner: Monika)
 *
 * Admin-only CRUD page at /admin/labels with role-gated rename/delete.
 * Deletion is blocked while a live post still references the label.
 */
test.describe('Label & Tag Management (M8 — Monika)', () => {
  test('admin can create, rename, and delete a label', async ({ page }) => {
    await login(page, ACCOUNTS.admin)
    await page.getByRole('link', { name: 'Labels' }).click()
    await expect(page).toHaveURL(/\/admin\/labels/)

    const suffix    = uniqueSuffix()
    const labelName = `PW Label ${suffix}`
    const renamed   = `${labelName} (renamed)`

    // CREATE
    await page.getByRole('button', { name: /new label/i }).click()
    await page.getByLabel('Name').fill(labelName)
    await page.getByRole('button', { name: /^create$/i }).click()
    await expect(page.getByText(/label created/i)).toBeVisible({ timeout: 5000 })
    await expect(page.getByText(labelName)).toBeVisible()

    // RENAME (Edit)
    const row = page.locator('tbody tr', { hasText: labelName })
    await row.getByRole('button', { name: /^edit$/i }).click()
    await page.getByLabel('Name').fill(renamed)
    await page.getByRole('button', { name: /^save$/i }).click()
    await expect(page.getByText(/label updated/i)).toBeVisible({ timeout: 5000 })
    await expect(page.getByText(renamed)).toBeVisible()

    // DELETE (no posts attached -> succeeds)
    const renamedRow = page.locator('tbody tr', { hasText: renamed })
    await renamedRow.getByRole('button', { name: /^delete$/i }).click()
    await page.getByRole('button', { name: /^delete$/i }).last().click() // confirm dialog
    await expect(page.getByText(/label deleted/i)).toBeVisible({ timeout: 5000 })
    await expect(page.getByText(renamed)).toHaveCount(0)
  })

  test('admin cannot delete a label that is still referenced by posts', async ({ page }) => {
    await login(page, ACCOUNTS.admin)
    await page.goto('/admin/labels')

    // Seed: "Interview Tips" has at least one post attached
    const row = page.locator('tbody tr', { hasText: 'Interview Tips' })
    await expect(row).toBeVisible()
    await row.getByRole('button', { name: /^delete$/i }).click()
    await page.getByRole('button', { name: /^delete$/i }).last().click()
    // Should toast an error, not success
    await expect(page.getByText(/cannot delete/i)).toBeVisible({ timeout: 5000 })
    // Label still present in the table (scope to row to avoid matching the confirm dialog copy)
    await expect(page.locator('tbody tr', { hasText: 'Interview Tips' })).toBeVisible()
  })

  test('duplicate label name is rejected (409)', async ({ page }) => {
    await login(page, ACCOUNTS.admin)
    await page.goto('/admin/labels')

    await page.getByRole('button', { name: /new label/i }).click()
    await page.getByLabel('Name').fill('Interview Tips') // already exists
    await page.getByRole('button', { name: /^create$/i }).click()
    // Both an inline field error and a toast will show "already exists";
    // `.first()` keeps strict mode happy.
    await expect(page.getByText(/already exists/i).first()).toBeVisible({ timeout: 5000 })
  })

  test('students do NOT see the Labels admin link in the nav', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await expect(page.getByRole('link', { name: 'Forum' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Labels' })).toHaveCount(0)
  })

  test('students hitting /admin/labels are redirected back to their dashboard', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/admin/labels')
    await expect(page).toHaveURL(/\/student\/dashboard/)
  })
})

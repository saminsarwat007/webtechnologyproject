// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login, uniqueSuffix } from './helpers/auth.js'

test.describe('Admin flow (M2, M3, M4, M5, M6)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ACCOUNTS.admin)
  })

  test('admin dashboard shows analytics cards and a chart', async ({ page }) => {
    await expect(page).toHaveURL(/\/admin\/dashboard/)
    await expect(page.getByText(/active listings/i)).toBeVisible()
    await expect(page.getByText(/total applications/i)).toBeVisible()
    // Chart.js renders to a <canvas>
    await expect(page.locator('canvas')).toBeVisible()
  })

  test('manage companies: create a new company', async ({ page }) => {
    const suffix = uniqueSuffix()
    await page.getByRole('link', { name: 'Companies' }).click()
    await expect(page).toHaveURL(/\/admin\/companies/)

    await page.getByRole('button', { name: /add company/i }).click()
    const modal = page.locator('.card', { hasText: 'New company' })
    await expect(modal).toBeVisible()

    // Inputs appear in this order: Name, Industry, Location, Description
    const inputs = modal.locator('input')
    await inputs.nth(0).fill(`PW Test Co ${suffix}`)
    await inputs.nth(1).fill('Software')
    await inputs.nth(2).fill('Kuala Lumpur')
    await modal.locator('textarea').fill('Auto-created by Playwright e2e test.')

    await modal.getByRole('button', { name: /^create$/i }).click()
    await expect(page.getByText(new RegExp(`PW Test Co ${suffix}`))).toBeVisible({ timeout: 5000 })
  })

  test('manage jobs: create job, validates required fields, and lists it', async ({ page }) => {
    const suffix = uniqueSuffix()
    await page.getByRole('link', { name: 'Manage Jobs' }).click()
    await expect(page).toHaveURL(/\/admin\/jobs/)

    await page.getByRole('button', { name: /add job/i }).click()
    const modal = page.locator('.card', { hasText: 'New job' })
    await expect(modal).toBeVisible()

    // Submit empty form first to trigger validation
    await modal.getByRole('button', { name: /create job/i }).click()
    await expect(modal.getByText(/required/i).first()).toBeVisible()

    // Fill required fields. Inputs (top-down): Title, [Company select], [Type select],
    // Deadline (date input), Description (textarea#1), Requirements (textarea#2)
    await modal.locator('input').nth(0).fill(`PW Job ${suffix}`)
    await modal.locator('select').nth(0).selectOption({ index: 1 })   // first non-empty company
    await modal.locator('select').nth(1).selectOption('internship')

    const d = new Date(); d.setDate(d.getDate() + 60)
    await modal.locator('input[type="date"]').fill(d.toISOString().slice(0, 10))

    await modal.locator('textarea').nth(0).fill('Created by Playwright. Should be visible after save.')

    await modal.getByRole('button', { name: /create job/i }).click()
    await expect(page.getByText(new RegExp(`PW Job ${suffix}`))).toBeVisible({ timeout: 5000 })
  })

  test('manage applications: admin can change a status', async ({ page }) => {
    await page.getByRole('link', { name: 'Applications' }).click()
    await expect(page).toHaveURL(/\/admin\/applications/)

    // There should be at least one seeded application
    const firstRow = page.locator('tbody tr, .card').filter({ hasText: /pending|reviewed|accepted|rejected/i }).first()
    await expect(firstRow).toBeVisible({ timeout: 5000 })

    // Try changing status via the row's select element
    const rowSelect = firstRow.locator('select').first()
    if (await rowSelect.isVisible().catch(() => false)) {
      await rowSelect.selectOption('reviewed')
      // Toast confirmation
      await expect(page.getByText(/updated|status/i).first()).toBeVisible({ timeout: 5000 })
    }
  })
})

test.describe('Superadmin extras (M6)', () => {
  test('superadmin can list all users', async ({ page }) => {
    await login(page, ACCOUNTS.superAdmin)
    await page.getByRole('link', { name: 'Users' }).click()
    await expect(page).toHaveURL(/\/admin\/users/)

    // Seed has 4 users at minimum
    const rows = page.locator('tbody tr')
    await expect(rows.first()).toBeVisible({ timeout: 5000 })
    const count = await rows.count()
    expect(count).toBeGreaterThanOrEqual(4)
  })
})

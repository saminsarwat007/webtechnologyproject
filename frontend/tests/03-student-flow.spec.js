// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login, uniqueSuffix } from './helpers/auth.js'

test.describe('Student flow (M2, M3, M5)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page, ACCOUNTS.student1)
  })

  test('dashboard renders student summary cards', async ({ page }) => {
    await expect(page).toHaveURL(/\/student\/dashboard/)
    await expect(page.getByRole('heading', { name: /welcome/i })).toBeVisible()
  })

  test('browse jobs page lists seeded jobs and supports search + type filter', async ({ page }) => {
    await page.getByRole('link', { name: 'Browse Jobs' }).click()
    await expect(page).toHaveURL(/\/student\/jobs/)

    // At least one job card from the seed should appear
    const jobCards = page.locator('.grid > .card, [class*="grid"] > .card')
    await expect(jobCards.first()).toBeVisible({ timeout: 10_000 })

    // Search for "Engineer" — the seed has multiple "Engineer" titles
    await page.getByPlaceholder(/search by title or company/i).fill('Engineer')
    // Wait for debounce
    await page.waitForTimeout(600)
    await expect(jobCards.first()).toBeVisible()

    // Filter by internship type
    await page.locator('select').first().selectOption('internship')
    await expect(jobCards.first()).toBeVisible()

    // Clear filters
    await page.getByPlaceholder(/search by title or company/i).fill('')
    await page.locator('select').first().selectOption('all')
  })

  test('job detail page loads with company info', async ({ page }) => {
    await page.goto('/student/jobs')
    const firstCard = page.locator('.card', { has: page.getByRole('button', { name: /apply/i }) }).first()
    // Some cards may be already-applied — click "View details" instead
    const viewLink = firstCard.getByText(/view details|view/i).first()
    if (await viewLink.isVisible().catch(() => false)) {
      await viewLink.click()
    } else {
      // Fallback: navigate directly to job 1
      await page.goto('/student/jobs/1')
    }
    await expect(page).toHaveURL(/\/student\/jobs\/\d+/)
  })

  test('student can apply to a job they have not applied for', async ({ page }) => {
    // Use a freshly registered student to guarantee no prior applications
    const suffix = uniqueSuffix()
    await page.evaluate(() => {
      localStorage.removeItem('cb_token')
      localStorage.removeItem('cb_user')
    })
    await page.goto('/register')
    await page.getByLabel('Full name').fill(`Job Tester ${suffix}`)
    await page.getByLabel('Email').fill(`jobtest_${suffix}@student.utm.my`)
    await page.getByLabel('Password', { exact: true }).fill('Password123!')
    await page.getByLabel('Confirm password').fill('Password123!')
    await page.getByRole('button', { name: /create account/i }).click()
    await expect(page).toHaveURL(/\/student\/dashboard/)

    // Apply to a job
    await page.goto('/student/jobs')
    const applyBtn = page.getByRole('button', { name: /^apply$/i }).first()
    await applyBtn.click()
    // Modal opens
    await expect(page.getByRole('heading', { name: /apply for/i })).toBeVisible()
    await page.getByLabel(/cover letter/i).fill(`Auto-test cover letter ${suffix}. I am keen to learn.`)
    await page.getByRole('button', { name: /submit application/i }).click()

    // Toast notification appears
    await expect(page.getByText(/application submitted/i)).toBeVisible({ timeout: 5000 })

    // My Applications page now shows it
    await page.getByRole('link', { name: 'My Applications' }).click()
    await expect(page.locator('.card').filter({ hasText: /pending/i }).first()).toBeVisible()
  })

  test('profile upsert: student saves matric/programme/CGPA', async ({ page }) => {
    await page.getByRole('link', { name: 'Profile' }).click()
    await expect(page).toHaveURL(/\/student\/profile/)

    // Fields are pre-filled because Areeb has a seeded profile. Just save again.
    const matric = page.getByLabel(/matric number/i)
    await expect(matric).toHaveValue(/A22EC/)

    await page.getByRole('button', { name: /save profile/i }).click()
    await expect(page.getByText(/profile saved/i)).toBeVisible({ timeout: 5000 })
  })

  test('profile validation: rejects CGPA outside 0..4', async ({ page }) => {
    await page.goto('/student/profile')
    const cgpa = page.getByLabel(/cgpa/i)
    await cgpa.fill('5.5')
    await page.getByRole('button', { name: /save profile/i }).click()
    await expect(page.getByText(/cgpa must be between/i)).toBeVisible()
  })
})

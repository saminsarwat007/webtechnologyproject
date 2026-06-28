import { test, expect } from '@playwright/test'

/**
 * SAMIN's progress demo — Modules 2 (Jobs) and 3 (Applications).
 *
 * Story arc:
 *   Scene 1 — Student logs in
 *   Scene 2 — Student browses jobs (search + type filter)
 *   Scene 3 — Student opens job detail
 *   Scene 4 — Student applies to a job
 *   Scene 5 — Student reviews their application in My Applications
 *   Scene 6 — Student logs out
 *   Scene 7 — Admin logs in
 *   Scene 8 — Admin opens Manage Jobs and creates a new job
 *   Scene 9 — Admin opens Manage Applications and moderates a status
 *   Scene 10 — Wrap
 *
 * Running:
 *   cd frontend
 *   npx playwright test --config=demo/playwright.demo.config.js
 */

const SCENE = (label) => `\n========== ${label} ==========\n`
const beat  = (page, ms = 1500) => page.waitForTimeout(ms)

const ACCOUNTS = {
  student: { email: 'samin@student.utm.my',    password: 'Password123!' },
  admin:   { email: 'farizal@careerbridge.my', password: 'Password123!' }
}

test('Samin progress demo — M2 (Jobs) + M3 (Applications)', async ({ page }) => {
  test.setTimeout(180_000)

  // ─────────────────────────────────────────────────────────────
  // SCENE 1 — Student logs in
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 1 — Student login'))
  await page.goto('/login')
  await beat(page, 2000)

  await page.getByLabel('Email').fill(ACCOUNTS.student.email)
  await beat(page, 800)
  await page.getByLabel('Password', { exact: true }).fill(ACCOUNTS.student.password)
  await beat(page, 800)
  await page.getByRole('button', { name: /sign in/i }).click()
  await expect(page).toHaveURL(/\/student\/dashboard/)
  await beat(page, 2500)

  // ─────────────────────────────────────────────────────────────
  // SCENE 2 — Browse Jobs (M2 frontend: search + type filter)
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 2 — Browse Jobs'))
  await page.getByRole('link', { name: 'Browse Jobs' }).click()
  await expect(page).toHaveURL(/\/student\/jobs/)
  await beat(page, 2500)

  // Demonstrate search
  const searchBox = page.getByPlaceholder(/search by title or company/i)
  await searchBox.fill('Engineer')
  await beat(page, 2000)
  await searchBox.fill('')
  await beat(page, 1200)

  // Demonstrate type filter
  const typeSelect = page.locator('select').first()
  await typeSelect.selectOption('internship')
  await beat(page, 2500)
  await typeSelect.selectOption('all')
  await beat(page, 1500)

  // ─────────────────────────────────────────────────────────────
  // SCENE 3 — Job Detail
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 3 — Job Detail'))
  // Click the first available "View" / "Apply" card link
  const firstCard = page.locator('.card').first()
  const detailLink = firstCard.getByRole('link', { name: /view details|view/i }).first()
  if (await detailLink.isVisible().catch(() => false)) {
    await detailLink.click()
  } else {
    // Fallback to direct navigation if no link is rendered
    await page.goto('/student/jobs/1')
  }
  await expect(page).toHaveURL(/\/student\/jobs\/\d+/)
  await beat(page, 3500)

  // ─────────────────────────────────────────────────────────────
  // SCENE 4 — Apply to a job (M3: create application)
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 4 — Apply'))
  // The Apply CTA might be on the detail page already
  const detailApply = page.getByRole('button', { name: /^apply$/i }).first()
  let applied = false
  if (await detailApply.isVisible().catch(() => false) && await detailApply.isEnabled().catch(() => false)) {
    await detailApply.click()
    await beat(page, 1500)
    const cover = page.getByLabel(/cover letter/i)
    if (await cover.isVisible().catch(() => false)) {
      await cover.fill('I am genuinely excited about this opportunity. My recent coursework on full-stack web development aligns directly with your tech stack, and I would love to contribute and grow with your team.')
      await beat(page, 1500)
      await page.getByRole('button', { name: /submit application/i }).click()
      applied = true
      await beat(page, 2500)
    }
  }
  // If the detail page didn't allow applying (already applied, or no modal),
  // jump back to the list and apply from a card with an enabled button.
  if (!applied) {
    await page.goto('/student/jobs')
    await beat(page, 1500)
    const listApply = page.getByRole('button', { name: /^apply$/i }).first()
    if (await listApply.isVisible().catch(() => false) && await listApply.isEnabled().catch(() => false)) {
      await listApply.click()
      await beat(page, 1500)
      const cover = page.getByLabel(/cover letter/i)
      if (await cover.isVisible().catch(() => false)) {
        await cover.fill('I am genuinely excited about this opportunity and confident my skills are a strong match.')
        await beat(page, 1200)
        await page.getByRole('button', { name: /submit application/i }).click()
        await beat(page, 2500)
      }
    }
  }

  // ─────────────────────────────────────────────────────────────
  // SCENE 5 — My Applications (M3 frontend: tracking)
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 5 — My Applications'))
  await page.getByRole('link', { name: 'My Applications' }).click()
  await expect(page).toHaveURL(/\/student\/applications/)
  await beat(page, 3500)

  // ─────────────────────────────────────────────────────────────
  // SCENE 6 — Logout
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 6 — Logout'))
  const logoutBtn = page.locator('header').getByRole('button', { name: /logout/i }).first()
  if (await logoutBtn.isVisible().catch(() => false)) {
    await logoutBtn.click()
  } else {
    // Mobile fallback
    const toggle = page.locator('header button[aria-label="Toggle navigation"]')
    if (await toggle.isVisible().catch(() => false)) {
      await toggle.click()
      await beat(page, 600)
      await page.locator('header').getByRole('button', { name: /logout/i }).first().click()
    }
  }
  await expect(page).toHaveURL(/\/login/)
  await beat(page, 1500)

  // ─────────────────────────────────────────────────────────────
  // SCENE 7 — Admin login
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 7 — Admin login'))
  await page.getByLabel('Email').fill(ACCOUNTS.admin.email)
  await beat(page, 600)
  await page.getByLabel('Password', { exact: true }).fill(ACCOUNTS.admin.password)
  await beat(page, 600)
  await page.getByRole('button', { name: /sign in/i }).click()
  await expect(page).toHaveURL(/\/admin\/dashboard/)
  await beat(page, 2500)

  // ─────────────────────────────────────────────────────────────
  // SCENE 8 — Manage Jobs: create
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 8 — Manage Jobs (create)'))
  await page.getByRole('link', { name: 'Manage Jobs' }).click()
  await expect(page).toHaveURL(/\/admin\/jobs/)
  await beat(page, 2500)

  await page.getByRole('button', { name: /add job/i }).click()
  const jobModal = page.locator('.card', { hasText: 'New job' })
  await expect(jobModal).toBeVisible()
  await beat(page, 1500)

  const suffix = Date.now().toString(36).slice(-4)
  await jobModal.locator('input').nth(0).fill(`Frontend Engineer Intern (${suffix})`)
  await beat(page, 600)
  await jobModal.locator('select').nth(0).selectOption({ index: 1 })
  await beat(page, 500)
  await jobModal.locator('select').nth(1).selectOption('internship')
  await beat(page, 500)
  const future = new Date(); future.setDate(future.getDate() + 60)
  await jobModal.locator('input[type="date"]').fill(future.toISOString().slice(0, 10))
  await beat(page, 500)
  await jobModal.locator('textarea').nth(0).fill('Build interactive user interfaces with Vue 3 and Tailwind CSS. Collaborate with the API team to ship features end to end.')
  await beat(page, 1000)

  await jobModal.getByRole('button', { name: /create job/i }).click()
  await expect(page.getByText(new RegExp(`Frontend Engineer Intern \\(${suffix}\\)`))).toBeVisible({ timeout: 8000 })
  await beat(page, 3000)

  // ─────────────────────────────────────────────────────────────
  // SCENE 9 — Manage Applications: change status
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 9 — Manage Applications (moderate)'))
  await page.getByRole('link', { name: 'Applications' }).click()
  await expect(page).toHaveURL(/\/admin\/applications/)
  await beat(page, 3000)

  const firstRow = page.locator('tbody tr, .card').filter({ hasText: /pending|reviewed|accepted|rejected/i }).first()
  if (await firstRow.isVisible().catch(() => false)) {
    const rowSelect = firstRow.locator('select').first()
    if (await rowSelect.isVisible().catch(() => false)) {
      await rowSelect.selectOption('reviewed')
      await beat(page, 2500)
    }
  }

  // ─────────────────────────────────────────────────────────────
  // SCENE 10 — Wrap
  // ─────────────────────────────────────────────────────────────
  console.log(SCENE('Scene 10 — Wrap'))
  await page.goto('/admin/dashboard')
  await beat(page, 3500)
})

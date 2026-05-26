// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login } from './helpers/auth.js'

/**
 * Module 8 — Mock Interview & Technical Prep Scheduler (Owner: Monika)
 *
 * Covers, end-to-end:
 *   - Admin creates an open availability slot.
 *   - Student sees that slot, books it with a job category.
 *   - Admin sees the booking and submits a score + feedback.
 *   - Student sees the score + feedback in My Sessions.
 *   - Admin can delete an unbooked slot.
 *   - Students can NOT see the admin Manage Interviews link.
 */

/**
 * Build a "datetime-local" string a few days in the future.
 * Returns: { value: 'YYYY-MM-DDTHH:mm', humanRegex: /…/ }
 */
function futureSlot (daysAhead = 5) {
  const d = new Date()
  d.setDate(d.getDate() + daysAhead)
  d.setHours(10, 30, 0, 0)
  // Strip seconds + the trailing Z, keep local time
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  const iso = d.toISOString().slice(0, 16) // YYYY-MM-DDTHH:mm
  return iso
}

test.describe('Mock Interview Scheduler (M8 — Monika)', () => {
  test('admin creates an open slot, student books it, admin evaluates, student sees score', async ({ browser }) => {
    // ====================== ADMIN: create slot ============================
    const adminCtx  = await browser.newContext()
    const adminPage = await adminCtx.newPage()

    await login(adminPage, ACCOUNTS.admin)
    await adminPage.getByRole('link', { name: 'Interviews' }).click()
    await expect(adminPage).toHaveURL(/\/admin\/interviews/)

    await adminPage.getByRole('button', { name: /add slot/i }).click()
    const slotIso = futureSlot(5)
    await adminPage.getByLabel(/scheduled date/i).fill(slotIso)
    await adminPage.getByRole('button', { name: /create slot/i }).click()

    await expect(adminPage.getByText(/slot created/i)).toBeVisible({ timeout: 5000 })

    // ====================== STUDENT: book slot ============================
    const studentCtx  = await browser.newContext()
    const studentPage = await studentCtx.newPage()

    await login(studentPage, ACCOUNTS.student2)
    await studentPage.getByRole('link', { name: /mock interviews/i }).click()
    await expect(studentPage).toHaveURL(/\/interviews/)

    // The most-recent slot should appear; use the row containing the date
    // we just submitted (date alone is enough to disambiguate).
    const datePart = slotIso.slice(0, 10) // YYYY-MM-DD
    const yyyy = Number(datePart.slice(0, 4))
    const mm   = Number(datePart.slice(5, 7))
    const dd   = Number(datePart.slice(8, 10))
    const monthRegex = new RegExp(
      // toLocaleString prints e.g. "Mon, 31 May 2026, 10:30 AM" depending on locale,
      // so we just look for the day-number + year as a robust anchor.
      `\\b${dd}\\b.*\\b${yyyy}\\b`
    )

    const slotRow = studentPage.locator('li', { hasText: monthRegex })
    await expect(slotRow.first()).toBeVisible({ timeout: 5000 })
    await slotRow.first().getByRole('button', { name: /book this slot/i }).click()

    await expect(studentPage.getByRole('heading', { name: /book mock interview/i })).toBeVisible()
    await studentPage.getByLabel(/target job category/i).selectOption('Software Engineering')
    await studentPage.getByRole('button', { name: /^book$/i }).click()

    await expect(studentPage.getByText(/slot booked/i)).toBeVisible({ timeout: 5000 })

    // Wait for the booking modal to fully unmount (the <select> options
    // would otherwise still be in the DOM and clash with strict-mode locators).
    await expect(studentPage.getByRole('heading', { name: /book mock interview/i })).toHaveCount(0)

    // The page auto-switches to the "My sessions" tab on success. Match the
    // "Category: Software Engineering" line on the session card directly.
    const myRow = studentPage.locator('li')
      .filter({ hasText: /Category:\s*Software Engineering/i })
      .first()
    await expect(myRow).toBeVisible()
    await expect(myRow).toContainText(/pending/i)

    // ====================== ADMIN: evaluate ===============================
    await adminPage.goto('/admin/interviews')
    // The tab button label includes a count ("Bookings 1"), hence the loose regex.
    await adminPage.getByRole('button', { name: /^Bookings/ }).click()

    const bookingRow = adminPage.locator('tbody tr', { hasText: 'Samin Sarwat' })
      .filter({ hasText: /Software Engineering/i })
      .first()
    await expect(bookingRow).toBeVisible({ timeout: 5000 })
    await bookingRow.getByRole('button', { name: /evaluate/i }).click()

    await expect(adminPage.getByRole('heading', { name: /evaluate booking/i })).toBeVisible()
    await adminPage.getByLabel(/score/i).fill('87')
    await adminPage.getByLabel(/feedback/i).fill('Great at fundamentals; sharpen system-design narrative.')
    await adminPage.getByRole('button', { name: /save evaluation/i }).click()

    await expect(adminPage.getByText(/booking evaluated/i)).toBeVisible({ timeout: 5000 })

    // ====================== STUDENT: see score + feedback =================
    await studentPage.reload()
    await studentPage.getByRole('button', { name: /my sessions/i }).click()

    const completedRow = studentPage.locator('li')
      .filter({ hasText: /Software Engineering/i })
      .filter({ hasText: /completed/i })
      .first()
    await expect(completedRow).toBeVisible({ timeout: 5000 })
    await expect(completedRow).toContainText(/87 \/ 100/)
    await expect(completedRow).toContainText(/sharpen system-design/i)

    await adminCtx.close()
    await studentCtx.close()
  })

  test('admin can delete an unbooked slot', async ({ page }) => {
    await login(page, ACCOUNTS.admin)
    await page.goto('/admin/interviews')

    // Create a fresh slot we can immediately delete (unbooked).
    await page.getByRole('button', { name: /add slot/i }).click()
    const iso = futureSlot(9)
    await page.getByLabel(/scheduled date/i).fill(iso)
    await page.getByRole('button', { name: /create slot/i }).click()
    await expect(page.getByText(/slot created/i)).toBeVisible({ timeout: 5000 })

    // The new slot is the one whose row contains its date AND is "Open"
    const dd = Number(iso.slice(8, 10))
    const yyyy = Number(iso.slice(0, 4))
    const slotRow = page.locator('tbody tr', {
      hasText: new RegExp(`\\b${dd}\\b.*\\b${yyyy}\\b`)
    }).filter({ hasText: /open/i }).first()
    await expect(slotRow).toBeVisible()

    // Auto-accept the native confirm() before clicking Delete.
    page.once('dialog', d => d.accept())
    await slotRow.getByRole('button', { name: /^delete$/i }).click()

    await expect(page.getByText(/slot deleted/i)).toBeVisible({ timeout: 5000 })
  })

  test('admin cannot delete a slot that is already booked', async ({ page }) => {
    await login(page, ACCOUNTS.admin)
    await page.goto('/admin/interviews')

    // The seeded slot #4 is is_booked = TRUE; it should render with no Delete button.
    const bookedRow = page.locator('tbody tr', { hasText: /booked/i }).first()
    await expect(bookedRow).toBeVisible()
    await expect(bookedRow.getByRole('button', { name: /^delete$/i })).toHaveCount(0)
  })

  test('students do NOT see the admin Manage Interviews link', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await expect(page.getByRole('link', { name: /^interviews$/i })).toHaveCount(0)
    await expect(page.getByRole('link', { name: /mock interviews/i })).toBeVisible()
  })

  test('students hitting /admin/interviews are redirected back to their dashboard', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/admin/interviews')
    await expect(page).toHaveURL(/\/student\/dashboard/)
  })
})

import { chromium } from 'playwright'
import { mkdirSync } from 'fs'
import path from 'path'

const OUT = path.resolve('frontend/demo/slides')
mkdirSync(OUT, { recursive: true })

const BASE = 'http://localhost:5173'
const PW = 'Password123!'

async function shot(page, name, opts = {}) {
  const { fullPage = true, wait = 1500 } = opts
  await page.waitForTimeout(wait)
  await page.screenshot({ path: path.join(OUT, `${name}.png`), fullPage })
  console.log(`  ✓ ${name}.png`)
}

async function main() {
  const browser = await chromium.launch({ headless: true })
  const ctx = await browser.newContext({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 2,
  })
  const page = await ctx.newPage()

  // ---- Login as student ----
  console.log('Student screenshots...')
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
  await shot(page, '01-login-page', { wait: 500 })

  await page.fill('#email', 'samin@student.utm.my')
  await page.fill('#password', PW)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/student/dashboard', { timeout: 10000 })
  await shot(page, '02-student-dashboard')

  // Browse Jobs
  await page.goto(`${BASE}/student/jobs`, { waitUntil: 'networkidle' })
  await shot(page, '03-browse-jobs')

  // Search
  await page.fill('input[placeholder*="Search"]', 'engineer')
  await page.waitForTimeout(600)
  await shot(page, '04-browse-jobs-search')

  // Clear search
  await page.fill('input[placeholder*="Search"]', '')
  await page.waitForTimeout(400)

  // Type filter
  await page.selectOption('select', 'internship')
  await page.waitForTimeout(400)
  await shot(page, '05-browse-jobs-filter')

  // Reset filter
  await page.selectOption('select', 'all')
  await page.waitForTimeout(300)

  // Job detail
  await page.goto(`${BASE}/student/jobs/1`, { waitUntil: 'networkidle' })
  await shot(page, '06-job-detail')

  // Apply modal
  await page.goto(`${BASE}/student/jobs`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(800)
  // Click the first "Apply" button
  const applyBtn = page.locator('button:has-text("Apply")').first()
  if (await applyBtn.isVisible()) {
    await applyBtn.click()
    await page.waitForTimeout(500)
    await shot(page, '07-apply-modal')
    // Type cover letter
    await page.fill('textarea#cover', 'I am very interested in this position because it aligns perfectly with my studies in software engineering. I have experience with Vue.js, PHP, and MySQL.')
    await page.waitForTimeout(300)
    await shot(page, '08-apply-modal-filled')
    // Submit
    await page.click('button:has-text("Submit application")')
    await page.waitForTimeout(1500)
    await shot(page, '09-apply-success')
  }

  // My Applications
  await page.goto(`${BASE}/student/applications`, { waitUntil: 'networkidle' })
  await shot(page, '10-my-applications')

  // Tab filter
  const pendingTab = page.locator('button:has-text("Pending")').first()
  if (await pendingTab.isVisible()) {
    await pendingTab.click()
    await page.waitForTimeout(400)
    await shot(page, '11-my-applications-pending')
  }

  // Profile
  await page.goto(`${BASE}/student/profile`, { waitUntil: 'networkidle' })
  await shot(page, '12-student-profile')

  // ---- Login as admin ----
  console.log('Admin screenshots...')
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
  // Logout first if needed
  const logoutBtn = page.locator('button:has-text("Logout")')
  if (await logoutBtn.isVisible()) {
    await logoutBtn.click()
    await page.waitForTimeout(1000)
  }
  await page.fill('#email', 'farizal@careerbridge.my')
  await page.fill('#password', PW)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/admin/dashboard', { timeout: 10000 })
  await shot(page, '13-admin-dashboard')

  // Manage Jobs
  await page.goto(`${BASE}/admin/jobs`, { waitUntil: 'networkidle' })
  await shot(page, '14-manage-jobs')

  // Create job modal
  const addJobBtn = page.locator('button:has-text("Add job")')
  if (await addJobBtn.isVisible()) {
    await addJobBtn.click()
    await page.waitForTimeout(500)
    await shot(page, '15-create-job-modal')

    // Fill form - title is the first text input in the modal
    const titleInput = page.locator('.card input').first()
    await titleInput.fill('Junior Software Engineer')
    // Select company
    const companySelect = page.locator('select').nth(0)
    await companySelect.selectOption({ index: 1 })
    // Select type
    const typeSelect = page.locator('select').nth(1)
    await typeSelect.selectOption('fulltime')
    // Set deadline
    const deadlineInput = page.locator('input[type="date"]')
    const futureDate = new Date()
    futureDate.setDate(futureDate.getDate() + 30)
    await deadlineInput.fill(futureDate.toISOString().slice(0, 10))
    // Description
    const descTextarea = page.locator('textarea').nth(0)
    await descTextarea.fill('We are looking for a passionate Junior Software Engineer to join our team. You will work on building web applications using Vue.js and PHP.')
    // Requirements
    const reqTextarea = page.locator('textarea').nth(1)
    await reqTextarea.fill('Bachelor degree in Computer Science, familiarity with JavaScript and PHP.')
    await page.waitForTimeout(300)
    await shot(page, '16-create-job-filled')
    // Cancel - don't actually create
    await page.click('button:has-text("Cancel")')
    await page.waitForTimeout(300)
  }

  // Manage Applications
  await page.goto(`${BASE}/admin/applications`, { waitUntil: 'networkidle' })
  await shot(page, '17-manage-applications')

  // Manage Companies
  await page.goto(`${BASE}/admin/companies`, { waitUntil: 'networkidle' })
  await shot(page, '18-manage-companies')

  // Create company modal
  const addCompBtn = page.locator('button:has-text("Add company")')
  if (await addCompBtn.isVisible()) {
    await addCompBtn.click()
    await page.waitForTimeout(500)
    await shot(page, '19-create-company-modal')
    await page.click('button:has-text("Cancel")')
    await page.waitForTimeout(300)
  }

  // Manage Interviews
  await page.goto(`${BASE}/admin/interviews`, { waitUntil: 'networkidle' })
  await shot(page, '20-manage-interviews')

  // Forum
  await page.goto(`${BASE}/forum`, { waitUntil: 'networkidle' })
  await shot(page, '21-forum-list')

  // Forum post detail
  const postLink = page.locator('a[href^="/forum/"]').first()
  if (await postLink.isVisible()) {
    await postLink.click()
    await page.waitForTimeout(1000)
    await shot(page, '22-forum-post-detail')
  }

  // ---- Login as super admin ----
  console.log('Super admin screenshots...')
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
  const logoutBtn2 = page.locator('button:has-text("Logout")')
  if (await logoutBtn2.isVisible()) {
    await logoutBtn2.click()
    await page.waitForTimeout(1000)
  }
  await page.fill('#email', 'superadmin@careerbridge.my')
  await page.fill('#password', PW)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/admin/dashboard', { timeout: 10000 })
  
  // Admin Users (super admin only)
  await page.goto(`${BASE}/admin/users`, { waitUntil: 'networkidle' })
  await shot(page, '23-admin-users')

  await browser.close()
  console.log(`\nAll screenshots saved to: ${OUT}`)
}

main().catch(err => { console.error(err); process.exit(1) })

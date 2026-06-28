// Take screenshots of both localhost and Netlify versions
import { chromium } from 'playwright'
import fs from 'fs'

const localhostUrl = 'http://localhost:5173'
const netlifyUrl = 'https://careerbridge-champion.netlify.app'
const screenshotDir = 'demo/slides'

async function takeScreenshot (page, name, url, selector = null) {
  await page.goto(url, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: `${screenshotDir}/${name}.png`, type: 'png', fullPage: false })
  console.log(`  Saved: ${name}.png`)
}

async function main () {
  const browser = await chromium.launch({ headless: true })
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } })

  // === LOCALHOST SCREENSHOTS ===
  console.log('=== Localhost Screenshots ===')

  // Login page
  await takeScreenshot(page, 'local-01-login', `${localhostUrl}/login`)

  // Login as student
  await page.goto(`${localhostUrl}/login`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(1000)
  await page.fill('input[type="email"]', 'samin@student.utm.my')
  await page.fill('input[type="password"]', 'Password123!')
  await page.click('button[type="submit"]')
  await page.waitForTimeout(3000)
  await page.screenshot({ path: `${screenshotDir}/local-02-student-dashboard.png`, type: 'png' })
  console.log('  Saved: local-02-student-dashboard.png')

  // Browse jobs
  await page.goto(`${localhostUrl}/jobs`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: `${screenshotDir}/local-03-browse-jobs.png`, type: 'png' })
  console.log('  Saved: local-03-browse-jobs.png')

  // My applications
  await page.goto(`${localhostUrl}/applications`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: `${screenshotDir}/local-04-my-applications.png`, type: 'png' })
  console.log('  Saved: local-04-my-applications.png')

  // Logout and login as admin
  await page.evaluate(() => {
    localStorage.removeItem('cb_token')
    localStorage.removeItem('cb_user')
  })
  await page.goto(`${localhostUrl}/login`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(1000)
  await page.fill('input[type="email"]', 'farizal@careerbridge.my')
  await page.fill('input[type="password"]', 'Password123!')
  await page.click('button[type="submit"]')
  await page.waitForTimeout(3000)
  await page.screenshot({ path: `${screenshotDir}/local-05-admin-dashboard.png`, type: 'png' })
  console.log('  Saved: local-05-admin-dashboard.png')

  // Manage jobs
  await page.goto(`${localhostUrl}/admin/jobs`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: `${screenshotDir}/local-06-manage-jobs.png`, type: 'png' })
  console.log('  Saved: local-06-manage-jobs.png')

  // Manage applications
  await page.goto(`${localhostUrl}/admin/applications`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: `${screenshotDir}/local-07-manage-applications.png`, type: 'png' })
  console.log('  Saved: local-07-manage-applications.png')

  // === NETLIFY SCREENSHOTS ===
  console.log('=== Netlify Screenshots ===')

  // Login page
  await takeScreenshot(page, 'netlify-01-login', `${netlifyUrl}/login`)

  // Try logging in as student
  await page.goto(`${netlifyUrl}/login`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.fill('input[type="email"]', 'samin@student.utm.my')
  await page.fill('input[type="password"]', 'Password123!')
  await page.click('button[type="submit"]')
  await page.waitForTimeout(5000)
  await page.screenshot({ path: `${screenshotDir}/netlify-02-after-login.png`, type: 'png' })
  console.log('  Saved: netlify-02-after-login.png')

  // Just the main page
  await page.goto(netlifyUrl, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: `${screenshotDir}/netlify-03-homepage.png`, type: 'png' })
  console.log('  Saved: netlify-03-homepage.png')

  await browser.close()
  console.log('Done! All screenshots saved.')
}

main().catch(err => {
  console.error('Error:', err)
  process.exit(1)
})

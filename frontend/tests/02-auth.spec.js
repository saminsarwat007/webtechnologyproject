// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login, logout, uniqueSuffix } from './helpers/auth.js'

test.describe('Authentication (M1)', () => {
  test('rejects login with invalid credentials', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Email').fill('nobody@example.com')
    await page.getByLabel('Password', { exact: true }).fill('WrongPassword123!')
    await page.getByRole('button', { name: /sign in/i }).click()

    // Stays on login page and shows an error
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByText(/invalid email or password/i)).toBeVisible()
  })

  test('client-side validation catches empty/short fields', async ({ page }) => {
    await page.goto('/login')
    await page.getByRole('button', { name: /sign in/i }).click()
    await expect(page.getByText(/email is required/i)).toBeVisible()
    await expect(page.getByText(/password is required/i)).toBeVisible()

    await page.getByLabel('Email').fill('not-an-email')
    await page.getByLabel('Password', { exact: true }).fill('short')
    await page.getByRole('button', { name: /sign in/i }).click()
    await expect(page.getByText(/enter a valid email/i)).toBeVisible()
    await expect(page.getByText(/password must be at least 8/i)).toBeVisible()
  })

  test('student can log in and out', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await expect(page).toHaveURL(/\/student\/dashboard/)
    await expect(page.getByRole('link', { name: 'Browse Jobs' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Forum' })).toBeVisible()

    await logout(page)
    await expect(page).toHaveURL(/\/login/)
  })

  test('admin sees admin-only nav links', async ({ page }) => {
    await login(page, ACCOUNTS.admin)
    await expect(page).toHaveURL(/\/admin\/dashboard/)
    await expect(page.getByRole('link', { name: 'Manage Jobs' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Companies' })).toBeVisible()
    await expect(page.getByRole('link', { name: 'Interviews' })).toBeVisible() // M8 admin link
    // Regular admin should NOT see Users (superadmin only)
    await expect(page.getByRole('link', { name: 'Users', exact: true })).toHaveCount(0)
  })

  test('superadmin can access the Users page', async ({ page }) => {
    await login(page, ACCOUNTS.superAdmin)
    await expect(page.getByRole('link', { name: 'Users' })).toBeVisible()
    await page.getByRole('link', { name: 'Users' }).click()
    await expect(page).toHaveURL(/\/admin\/users/)
    await expect(page.getByRole('heading', { name: /users/i })).toBeVisible()
  })

  test('register a new student account', async ({ page }) => {
    const suffix = uniqueSuffix()
    const email  = `pwtest_${suffix}@student.utm.my`

    await page.goto('/register')
    await page.getByLabel('Full name').fill(`PW Test ${suffix}`)
    await page.getByLabel('Email').fill(email)
    await page.getByLabel('Password', { exact: true }).fill('Password123!')
    await page.getByLabel('Confirm password').fill('Password123!')
    await page.getByRole('button', { name: /create account/i }).click()

    // Auto-login on success → redirected to student dashboard
    await expect(page).toHaveURL(/\/student\/dashboard/)
  })

  test('route guard redirects unauthenticated user to /login', async ({ page }) => {
    await page.goto('/student/dashboard')
    await expect(page).toHaveURL(/\/login/)
  })

  test('route guard prevents student from accessing admin pages', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/admin/dashboard')
    // Bounced back to student dashboard
    await expect(page).toHaveURL(/\/student\/dashboard/)
  })
})

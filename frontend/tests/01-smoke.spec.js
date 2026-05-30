// @ts-check
import { test, expect } from '@playwright/test'

/**
 * Smoke tests — confirm the SPA shell + API are reachable before we run
 * the deeper flows.
 */

test.describe('Smoke', () => {
  test('frontend dev server is up and renders the login page', async ({ page }) => {
    await page.goto('/')
    // Anonymous visitors land on /login
    await expect(page).toHaveURL(/\/login/)
    await expect(page.getByRole('heading', { name: /welcome back/i })).toBeVisible()
    await expect(page.getByLabel('Email')).toBeVisible()
    await expect(page.getByLabel('Password', { exact: true })).toBeVisible()
  })

  test('backend health endpoint returns 200 JSON', async ({ request }) => {
    const res = await request.get('http://localhost:8000/')
    expect(res.status()).toBe(200)
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(body.message).toMatch(/CareerBridge API/)
  })

  test('public jobs API returns seeded jobs', async ({ request }) => {
    const res = await request.get('http://localhost:8000/api/jobs')
    expect(res.ok()).toBeTruthy()
    const body = await res.json()
    expect(body.success).toBe(true)
    expect(Array.isArray(body.data)).toBe(true)
    expect(body.data.length).toBeGreaterThanOrEqual(1)
  })
})

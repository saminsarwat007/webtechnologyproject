// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login, uniqueSuffix } from './helpers/auth.js'

/**
 * Module 7 — Forum & Discussion (Owner: Monika)
 *
 * Covers: list/sort, search, label sidebar filter, create post (with
 * inline new label), like toggle, comment, edit own post, soft-delete
 * (post with comments), hard-delete (post without comments), admin
 * deletion of any post or comment.
 */
test.describe('Forum & Discussion (M7 — Monika)', () => {
  test('forum page renders posts sorted by likes desc', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.getByRole('link', { name: 'Forum' }).click()
    await expect(page).toHaveURL(/\/forum/)

    await expect(page.getByRole('heading', { name: /community forum/i })).toBeVisible()

    // First post should be the one with 8 likes (Petronas ICT AMA from the seed)
    const firstPost = page.locator('article').first()
    await expect(firstPost).toBeVisible()
    await expect(firstPost).toContainText(/Petronas/i)
  })

  test('labels sidebar is visible and clickable', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/forum')

    const sidebar = page.locator('aside').first()
    await expect(sidebar.getByText(/labels/i)).toBeVisible()
    await expect(sidebar.getByText(/all posts/i)).toBeVisible()
    await expect(sidebar.getByText(/Interview Tips/i)).toBeVisible()

    // Click a label and confirm filtering takes effect
    await sidebar.getByRole('button', { name: /Internship Stories/i }).click()
    await expect(page.getByText(/Filtered by:/i)).toBeVisible()
    const articles = page.locator('article')
    await expect(articles).toHaveCount(1) // only the Petronas internship post
  })

  test('search bar filters posts', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/forum')

    await page.getByPlaceholder(/search posts/i).fill('interview')
    await page.waitForTimeout(500) // debounce
    const articles = page.locator('article')
    // Seed: only the "How do you prepare for technical interviews" post matches
    await expect(articles).toHaveCount(1)
    await expect(articles.first()).toContainText(/interviews/i)
  })

  test('student can create a new post with an existing label', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    const suffix = uniqueSuffix()
    const title  = `PW post ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await expect(page.getByRole('heading', { name: /new forum post/i })).toBeVisible()

    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Created by Playwright. This is body content for the test.')
    await page.locator('form select').selectOption({ label: 'Resume Advice' })

    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible({ timeout: 5000 })

    // New post is visible on the list
    await expect(page.getByText(title)).toBeVisible()
  })

  test('student can create a brand-new label inline from the post modal', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    const suffix     = uniqueSuffix()
    const labelName  = `PW Inline Label ${suffix}`
    const postTitle  = `PW inline ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(postTitle)
    await page.getByLabel('Content').fill('Trying inline label creation.')

    // Switch the radio to "Create new"
    await page.getByLabel('Create new').check()
    await page.getByPlaceholder(/e\.g\. career switching/i).fill(labelName)
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible({ timeout: 5000 })

    // Label appears in the sidebar
    await expect(page.locator('aside').first().getByText(labelName)).toBeVisible()
  })

  test('like toggle: liking increments count, second click decrements', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    // Pick the last article (likely lowest-liked seed post). The like
    // button's accessible name is just the count (it has a heart svg + digits).
    const target = page.locator('article').last()
    const likeBtn = target.getByRole('button', { name: /^\s*\d+\s*$/ }).first()
    await expect(likeBtn).toBeVisible()

    const initial = parseInt((await likeBtn.textContent() || '').trim(), 10)

    await likeBtn.click()
    await expect(likeBtn).toHaveText(new RegExp(`\\s*${initial + 1}\\s*`), { timeout: 3000 })

    await likeBtn.click()
    await expect(likeBtn).toHaveText(new RegExp(`\\s*${initial}\\s*`), { timeout: 3000 })
  })

  test('post detail view shows comments and accepts a new comment', async ({ page }) => {
    await login(page, ACCOUNTS.student2)

    // Navigate via the forum list, click the top post
    await page.goto('/forum')
    await page.locator('article').first().click()
    await expect(page).toHaveURL(/\/forum\/\d+/)

    await expect(page.getByRole('heading', { name: /\d+ comments?/i })).toBeVisible()

    const comment = `Auto comment ${uniqueSuffix()}`
    await page.getByPlaceholder(/add a comment/i).fill(comment)
    await page.getByRole('button', { name: /^comment$/i }).click()
    await expect(page.getByText(/comment added/i)).toBeVisible({ timeout: 5000 })
    await expect(page.getByText(comment)).toBeVisible()
  })

  test('post owner can edit their own post', async ({ page }) => {
    await login(page, ACCOUNTS.student2)

    // Create a fresh post to own
    await page.goto('/forum')
    const suffix = uniqueSuffix()
    const title  = `Edit me ${suffix}`
    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Original body')
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible({ timeout: 5000 })

    // Open the post we just created
    await page.getByText(title).first().click()
    await expect(page).toHaveURL(/\/forum\/\d+/)

    await page.getByRole('button', { name: /^edit$/i }).click()
    await page.getByLabel('Title').fill(`${title} (edited)`)
    await page.getByLabel('Content').fill('Updated by Playwright')
    await page.getByRole('button', { name: /save changes/i }).click()

    await expect(page.getByText(/post updated/i)).toBeVisible({ timeout: 5000 })
    await expect(page.getByRole('heading', { name: new RegExp(`${title} \\(edited\\)`) })).toBeVisible()
  })

  test('soft-delete: a post with comments retains its row but blanks content', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    // Create a post, add a comment to it (so soft-delete kicks in),
    // then delete it.
    const suffix = uniqueSuffix()
    const title  = `Soft delete ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Will be soft-deleted')
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible()

    await page.getByText(title).first().click()
    await page.getByPlaceholder(/add a comment/i).fill('keepalive')
    await page.getByRole('button', { name: /^comment$/i }).click()
    await expect(page.getByText(/comment added/i)).toBeVisible()

    // Delete the post (button text reads "Soft-delete" when comments exist)
    await page.getByRole('button', { name: /soft-delete/i }).click()
    await page.getByRole('button', { name: /^confirm$/i }).click()

    await expect(page.getByText(/soft-deleted/i)).toBeVisible({ timeout: 5000 })

    // The post heading now reads "[deleted post]"
    await expect(page.getByRole('heading', { name: /\[deleted post\]/ })).toBeVisible()
  })

  test('hard-delete: a post with no comments is fully removed', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    const suffix = uniqueSuffix()
    const title  = `Hard delete ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Will be hard-deleted')
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible()

    await page.getByText(title).first().click()

    // No comments → the delete button is just "Delete"
    await page.getByRole('button', { name: /^delete$/i }).first().click()
    await page.getByRole('button', { name: /^confirm$/i }).click()

    // Toast + back to /forum
    await expect(page.getByText(/post deleted/i)).toBeVisible({ timeout: 5000 })
    await expect(page).toHaveURL(/\/forum$/)
    await expect(page.getByText(title)).toHaveCount(0)
  })

  test('admin can delete any comment', async ({ browser, page }) => {
    // 1) Student creates a fresh post + comment
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    const suffix = uniqueSuffix()
    const title  = `Admin del target ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Admin should be able to nuke a comment here.')
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible()

    await page.getByText(title).first().click()
    const commentText = `student-comment-${suffix}`
    await page.getByPlaceholder(/add a comment/i).fill(commentText)
    await page.getByRole('button', { name: /^comment$/i }).click()
    await expect(page.getByText(commentText)).toBeVisible()
    const postUrl = page.url()

    // 2) Switch to admin in a FRESH browser context (separate localStorage)
    const adminContext = await browser.newContext()
    const adminPage    = await adminContext.newPage()
    await login(adminPage, ACCOUNTS.admin)
    await adminPage.goto(postUrl)

    await expect(adminPage.getByText(commentText)).toBeVisible()
    const commentLi = adminPage.locator('li').filter({ hasText: commentText })
    await commentLi.getByRole('button', { name: /^delete$/i }).click()
    await adminPage.getByRole('button', { name: /^confirm$/i }).click()

    await expect(adminPage.getByText(/comment deleted/i)).toBeVisible({ timeout: 5000 })
    await expect(adminPage.getByText(commentText)).toHaveCount(0)

    await adminContext.close()
  })
})

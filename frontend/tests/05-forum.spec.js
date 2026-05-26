// @ts-check
import { test, expect } from '@playwright/test'
import { ACCOUNTS, login, uniqueSuffix } from './helpers/auth.js'

/**
 * Module 7 — Forum & Discussion (Owner: Monika)
 *
 * Covers: list/sort, search, tag sidebar filter, create post with tag,
 * like toggle, comments, edit own post, delete (always cascade), and
 * admin moderation of any comment.
 *
 * The labels table was removed in the M7/M8 revision — posts now use a
 * free-text `tag` column directly (default 'General').
 */
test.describe('Forum & Discussion (M7 — Monika)', () => {
  test('forum page renders posts sorted by likes desc', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.getByRole('link', { name: 'Forum' }).click()
    await expect(page).toHaveURL(/\/forum/)

    await expect(page.getByRole('heading', { name: /community forum/i })).toBeVisible()

    // First post should be the most-liked one. Per the seed, post 2
    // ("Resume tips…") has 4 likes — the highest — followed by post 1
    // and post 3 with 3 likes each.
    const firstPost = page.locator('article').first()
    await expect(firstPost).toBeVisible()
    await expect(firstPost).toContainText(/Resume tips/i)
  })

  test('tags sidebar is visible and clickable', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/forum')

    const sidebar = page.locator('aside').first()
    await expect(sidebar.getByText(/^Tags$/i)).toBeVisible()
    await expect(sidebar.getByText(/all posts/i)).toBeVisible()
    // Seed has at least the "Interview Tips" tag
    await expect(sidebar.getByRole('button', { name: /Interview Tips/i })).toBeVisible()

    // Click a tag and confirm filtering takes effect
    await sidebar.getByRole('button', { name: /Internship Stories/i }).click()
    await expect(page.getByText(/Filtered by tag:/i)).toBeVisible()
    const articles = page.locator('article')
    await expect(articles).toHaveCount(1) // only the Petronas internship post
  })

  test('search bar filters posts', async ({ page }) => {
    await login(page, ACCOUNTS.student1)
    await page.goto('/forum')

    await page.getByPlaceholder(/search posts/i).fill('petronas')
    await page.waitForTimeout(500) // debounce
    const articles = page.locator('article')
    await expect(articles).toHaveCount(1)
    await expect(articles.first()).toContainText(/petronas/i)
  })

  test('student can create a new post with a tag', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    const suffix = uniqueSuffix()
    const title  = `PW post ${suffix}`
    const tag    = `PW Tag ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await expect(page.getByRole('heading', { name: /new forum post/i })).toBeVisible()

    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Created by Playwright. Body content for the test.')
    await page.getByLabel('Tag').fill(tag)

    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible({ timeout: 5000 })

    // The new post is on the list with the tag visible
    await expect(page.getByText(title)).toBeVisible()
    await expect(page.locator('aside').first().getByRole('button', { name: new RegExp(tag) })).toBeVisible()
  })

  test('like toggle: liking increments count, second click decrements', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    // Pick the last article (likely lowest-liked seed post). The like
    // button's accessible name is just the count (heart svg + digits).
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

  test('post owner can edit their own post, including the tag', async ({ page }) => {
    await login(page, ACCOUNTS.student2)

    await page.goto('/forum')
    const suffix = uniqueSuffix()
    const title  = `Edit me ${suffix}`
    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Original body')
    await page.getByLabel('Tag').fill('Original Tag')
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible({ timeout: 5000 })

    await page.getByText(title).first().click()
    await expect(page).toHaveURL(/\/forum\/\d+/)

    await page.getByRole('button', { name: /^edit$/i }).click()
    await page.getByLabel('Title').fill(`${title} (edited)`)
    await page.getByLabel('Content').fill('Updated by Playwright')
    await page.getByLabel('Tag').fill(`Edited Tag ${suffix}`)
    await page.getByRole('button', { name: /save changes/i }).click()

    await expect(page.getByText(/post updated/i)).toBeVisible({ timeout: 5000 })
    await expect(page.getByRole('heading', { name: new RegExp(`${title} \\(edited\\)`) })).toBeVisible()
    await expect(page.getByText(`Edited Tag ${suffix}`)).toBeVisible()
  })

  test('delete: a post (and its comments) is fully removed via cascade', async ({ page }) => {
    await login(page, ACCOUNTS.student2)
    await page.goto('/forum')

    // Create a post + add a comment so we exercise the cascade,
    // then hard-delete it. There is no soft-delete distinction any more.
    const suffix = uniqueSuffix()
    const title  = `Delete me ${suffix}`

    await page.getByRole('button', { name: /new post/i }).click()
    await page.getByLabel('Title').fill(title)
    await page.getByLabel('Content').fill('Will be deleted with cascade')
    await page.getByRole('button', { name: /^post$/i }).click()
    await expect(page.getByText(/post created/i)).toBeVisible()

    await page.getByText(title).first().click()
    await page.getByPlaceholder(/add a comment/i).fill('comment to be cascade-deleted')
    await page.getByRole('button', { name: /^comment$/i }).click()
    await expect(page.getByText(/comment added/i)).toBeVisible()

    // The Delete button on the post (the action bar lives inside the article).
    // Pin to the post card to avoid matching any per-comment Delete buttons.
    await page.locator('article').getByRole('button', { name: /^delete$/i }).first().click()
    // Confirm dialog
    await page.getByRole('button', { name: /^delete$/i }).last().click()

    await expect(page.getByText(/post deleted/i)).toBeVisible({ timeout: 5000 })
    await expect(page).toHaveURL(/\/forum$/)
    await expect(page.getByText(title)).toHaveCount(0)
  })

  test('admin can delete any comment (uses the flat /forums/comments/{id} endpoint)', async ({ browser, page }) => {
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
    await adminPage.getByRole('button', { name: /^delete$/i }).last().click()

    await expect(adminPage.getByText(/comment deleted/i)).toBeVisible({ timeout: 5000 })
    await expect(adminPage.getByText(commentText)).toHaveCount(0)

    await adminContext.close()
  })
})

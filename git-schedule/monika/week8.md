# Monika — Week 8 (18–24 May 2026)
**Branch:** `feature/m7-comments-likes`
**Module owned:** **M7 — Forum & Discussion (comments + likes)**
**Goal:** ship the sub-resource endpoints — adding/deleting comments (flat path) and the atomic like toggle.

---

## Day 1 — Mon 18 May
Comments — `createComment` (student-only) and the **flat** `deleteComment` (`/api/forums/comments/{id}`).

> The comment-delete path is *flat* per the revised M7 spec — it doesn't carry the post id any more. **Important:** the route MUST be declared **before** the dynamic `/api/forums/{id}` route in `public/index.php`, otherwise Slim captures the literal `comments` segment as a post id.

**Files touched:**
- `backend/src/Modules/Forums/ForumController.php` — `createComment($id)` validates `content ≤ 2000`, refuses to attach to a missing post. `deleteComment($id)` (where `$id` is the comment id) allows the comment's author **or** any admin to remove it.
- `backend/public/index.php` — `POST /api/forums/{id}/comments` (student) and `DELETE /api/forums/comments/{id}` declared **before** the dynamic forum routes.

CRUD this commit demonstrates: **Create** + **Delete** on `comments`.

```bash
git checkout -b feature/m7-comments-likes
git add backend/src/Modules/Forums/ForumController.php backend/public/index.php
git commit -m "feat(forum): comments add + role-aware flat delete path"
git push -u origin feature/m7-comments-likes
```

---

## Day 2 — Wed 20 May
Like toggle, computed live (no denormalised counter).

The revised schema does **not** carry a `posts.likes` column any more — the count is derived from `post_likes` via a subquery. The toggle just inserts or deletes a row.

**Files touched:**
- `backend/src/Modules/Forums/ForumController.php` — `toggleLike($id)`: if a `post_likes` row exists for `(post_id, user_id)` it deletes it, otherwise inserts one. Returns `{ likes, liked_by_me }` where `likes` comes from `SELECT COUNT(*) FROM post_likes WHERE post_id = ?`.
- `backend/public/index.php` — `POST /api/forums/{id}/like` behind `JwtMiddleware()`.

CRUD this commit demonstrates: **Create + Delete** on `post_likes` with derived COUNT(*).

```bash
git add backend/src/Modules/Forums/ForumController.php backend/public/index.php
git commit -m "feat(forum): atomic like toggle (computed COUNT, no denorm column)"
git push origin feature/m7-comments-likes
```

---

## Day 3 — Fri 22 May
Curl smoke tests + sanity-check that the flat comment-delete route was declared first.

**Files touched:**
- `backend/public/index.php` — re-order check; add the explanatory comment block above the forum routes documenting why the static `/comments/{id}` route must precede `/{id}`.

```bash
git add backend/public/index.php
git commit -m "chore(forum): document static-before-dynamic route ordering"
git push origin feature/m7-comments-likes
```

> **Open PR:** `feature/m7-comments-likes` → `main`. Title: *"Module 7: comments + likes endpoints (flat delete path)"*.

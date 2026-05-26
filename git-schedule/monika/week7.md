# Monika — Week 7 (11–17 May 2026)
**Branch:** `feature/m7-posts-crud`
**Module owned:** **M7 — Forum & Discussion (Create + Update + Delete on posts)**
**Goal:** finish post-level CRUD with owner gating and cascade delete.

---

## Day 1 — Mon 11 May
**Create** — students only.

**Files touched:**
- `backend/src/Modules/Forums/ForumController.php` — `create()` validates `title ≤ 150`, `content ≤ 5000`, optional `tag ≤ 60` (defaults to `'General'`). Pulls `user_id` from `jwt_payload`. Returns the freshly hydrated row.
- `backend/public/index.php` — `POST /api/forums` behind `JwtMiddleware('student')` (admins can moderate but cannot post).

CRUD this commit demonstrates: **Create** (`INSERT INTO posts`).

```bash
git checkout -b feature/m7-posts-crud
git add backend/src/Modules/Forums/ForumController.php backend/public/index.php
git commit -m "feat(forum): student-only create post endpoint"
git push -u origin feature/m7-posts-crud
```

---

## Day 2 — Wed 13 May
**Update** — author-only edit.

**Files touched:**
- `backend/src/Modules/Forums/ForumController.php` — `update($id)` first re-fetches the row, returns 403 if `user_id !== jwt_payload.user_id`, re-runs validation, then `UPDATE posts SET title, content, tag`. Returns 404 if the post is gone.
- `backend/public/index.php` — `PUT /api/forums/{id}` behind `JwtMiddleware('student')`.

CRUD this commit demonstrates: **Update** (`UPDATE posts …`) with owner gate.

```bash
git add backend/src/Modules/Forums/ForumController.php backend/public/index.php
git commit -m "feat(forum): author-only edit endpoint with 403 on non-owner"
git push origin feature/m7-posts-crud
```

---

## Day 3 — Fri 15 May
**Delete** — owner OR admin, always cascade.

Per the M7/M8 revision, post deletion is now a single hard-delete with cascade — `comments` and `post_likes` use `ON DELETE CASCADE`, so the FK engine cleans them up automatically. No more soft-delete distinction.

**Files touched:**
- `backend/src/Modules/Forums/ForumController.php` — `delete($id)`: allowed for the post's author **or** any admin/superadmin; returns `{ post_id }` on success.
- `backend/public/index.php` — `DELETE /api/forums/{id}` behind `JwtMiddleware()`; finer authorization is inside the controller.

CRUD this commit demonstrates: **Delete** (cascade via `DELETE FROM posts`).

```bash
git add backend/src/Modules/Forums/ForumController.php backend/public/index.php
git commit -m "feat(forum): role-aware cascade delete for posts"
git push origin feature/m7-posts-crud
```

> **Open PR:** `feature/m7-posts-crud` → `main`. Title: *"Module 7: full CRUD for posts (create / edit / cascade delete)"*.

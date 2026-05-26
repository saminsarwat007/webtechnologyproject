# Monika — Week 6 (4–10 May 2026)
**Branch:** `feature/m7-forum-schema-and-api`
**Modules owned:** M7 Forum & Discussion · M8 Mock Interview & Technical Prep Scheduler
**This week's focus:** lay the M7 foundation — `posts`, `comments`, `post_likes` tables + a working list/detail endpoint pair. M8 lands in Weeks 10–11.

> 🗣 **Coordinate with Areeb before pushing:** you are appending to `database/schema.sql` and `database/seed.sql` which Areeb owns. Tell him in the group chat the day before so he can merge cleanly.

---

## Day 1 — Mon 4 May
Add the three forum tables to the central schema.

**Files touched:**
- `database/schema.sql` —
  - `posts (post_id PK, user_id FK→users, title VARCHAR(150), content TEXT, tag VARCHAR(60) DEFAULT 'General', created_at)` — note: `tag` is a **free-text** column, not an FK.
  - `comments (comment_id PK, post_id FK→posts ON DELETE CASCADE, user_id FK→users, content TEXT, created_at)`
  - `post_likes (like_id PK, post_id FK→posts ON DELETE CASCADE, user_id FK→users, UNIQUE(post_id, user_id))` — junction for the like toggle.
- `database/seed.sql` — 5 sample posts (each with a different `tag`), 6 comments, 13 like rows so the sort-by-likes ordering is visible.

CRUD this commit demonstrates: **Schema** for M7.

```bash
git checkout -b feature/m7-forum-schema-and-api
git add database/schema.sql database/seed.sql
git commit -m "feat(db): posts/comments/post_likes tables + seed for M7"
git push -u origin feature/m7-forum-schema-and-api
```

---

## Day 2 — Wed 6 May
**Read** — `index()` (list + filter + sort) and `show()` (post + comments).

**Files touched:**
- `backend/src/Modules/Forums/ForumController.php` — `index()` joins `posts ⨝ users`, supports `?search=` and `?tag=`, computes `comment_count` and `likes` via subqueries from `comments` and `post_likes`, sorts by `likes DESC, created_at DESC`. `show($id)` returns the post + its full comment thread + `liked_by_me` for the current user.

CRUD this commit demonstrates: **Read** on `posts` + `comments`.

```bash
git add backend/src/Modules/Forums/ForumController.php
git commit -m "feat(forum): list + detail endpoints sorted by likes"
git push origin feature/m7-forum-schema-and-api
```

---

## Day 3 — Fri 8 May
Wire the read routes and confirm with a curl smoke test.

**Files touched:**
- `backend/public/index.php` — `GET /api/forums` and `GET /api/forums/{id}` behind `JwtMiddleware()` (any logged-in user can read the forum).

```bash
git add backend/public/index.php
git commit -m "feat(forum): wire GET /api/forums and /api/forums/{id}"
git push origin feature/m7-forum-schema-and-api
```

> **Open PR:** `feature/m7-forum-schema-and-api` → `main`. Title: *"Module 7: forum schema + read endpoints (list + detail)"*.

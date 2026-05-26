# Monika — Week 9 (25–31 May 2026)
**Branch:** `feature/m7-frontend`
**Module owned:** **M7 — Forum & Discussion (frontend)**
**Goal:** ship `ForumList.vue` and `ForumPost.vue` so the M7 backend has a real UI.

---

## Day 1 — Mon 25 May
List page — posts on the left, tag sidebar on the right.

The tag sidebar is computed entirely from the post list itself (group by `tag`, count, sort by frequency). There is no separate `/api/labels` endpoint any more.

**Files touched:**
- `frontend/src/views/ForumList.vue` — two-column layout, debounced `?search=` input, tag sidebar derived client-side, "New post" modal with a free-text `tag` field (defaults to `'General'`).
- `frontend/src/router/index.js` — `/forum` route (`roles: ['student','admin','superadmin']`).
- `frontend/src/components/NavBar.vue` — append "Forum" to both student and admin link arrays.

```bash
git checkout -b feature/m7-frontend
git add frontend/src/views/ForumList.vue frontend/src/router/index.js frontend/src/components/NavBar.vue
git commit -m "feat(forum): list page with tag sidebar + new-post modal"
git push -u origin feature/m7-frontend
```

---

## Day 2 — Wed 27 May
Post-detail read mode + like button + comments thread.

**Files touched:**
- `frontend/src/views/ForumPost.vue` — fetches `/api/forums/{id}`, renders title/author/date/tag, optimistic like toggle wired to `POST /api/forums/{id}/like`, comment list + add-comment form (students only).
- `frontend/src/router/index.js` — `/forum/:id` with `props: true`.

```bash
git add frontend/src/views/ForumPost.vue frontend/src/router/index.js
git commit -m "feat(forum): post detail view with likes + comments"
git push origin feature/m7-frontend
```

---

## Day 3 — Fri 29 May
Owner edit mode + role-aware delete (cascade).

**Files touched:**
- `frontend/src/views/ForumPost.vue` — adds an inline "Edit" form (only for the post's author, swaps the read card for a form `PUT`-ing `/api/forums/{id}` with the new title/content/tag). Wires `ConfirmDialog` to a single Delete button — admins additionally see a Delete button next to every comment, students only on their own.

```bash
git add frontend/src/views/ForumPost.vue
git commit -m "feat(forum): owner edit + cascade delete UI"
git push origin feature/m7-frontend
```

> **Open PR:** `feature/m7-frontend` → `main`. Title: *"Module 7: forum frontend (list + detail + edit + cascade delete)"*.

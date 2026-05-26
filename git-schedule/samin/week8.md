# Samin — Week 8 (18–24 May 2026)
**Branch:** `feature/jobs-frontend`  
**Module owned:** **M2 — Job & Internship Management (frontend)**  
**Goal:** student-facing browse + detail UI plus the admin manage-jobs CRUD UI.

---

## Day 1 — Mon 18 May
**BrowseJobs** — student grid view with debounced search and type filter.

**Files touched:**
- `frontend/src/views/student/BrowseJobs.vue` — fetches `GET /api/jobs`, renders `JobCard` grid, debounced search input (300ms), type dropdown (`internship`/`fulltime`/`parttime`), `LoadingSpinner` while loading, empty state when no matches.
- `frontend/src/router/index.js` — register `/student/jobs` (student-only).

```bash
git checkout -b feature/jobs-frontend
git add frontend/src/views/student/BrowseJobs.vue frontend/src/router/index.js
git commit -m "feat(jobs): student browse view with search and filter"
git push -u origin feature/jobs-frontend
```

---

## Day 2 — Wed 20 May
**JobDetail** — single-job view with Apply CTA.

**Files touched:**
- `frontend/src/views/student/JobDetail.vue` — fetches `GET /api/jobs/{id}`, shows full description/requirements/deadline; "Apply" button disabled if already applied; opens an apply modal that POSTs to `/api/applications`.
- `frontend/src/router/index.js` — register `/student/jobs/:id`.

```bash
git add frontend/src/views/student/JobDetail.vue frontend/src/router/index.js
git commit -m "feat(jobs): job detail view with apply modal"
git push origin feature/jobs-frontend
```

---

## Day 3 — Fri 22 May
**ManageJobs** — admin CRUD UI for jobs (uses every endpoint from Week 7).

**Files touched:**
- `frontend/src/views/admin/ManageJobs.vue` — table of all jobs (fetch `/api/jobs`), "New job" button opens create modal, row "Edit" opens update modal, row "Delete" opens `ConfirmDialog` then calls `DELETE /api/jobs/{id}`. Reactive list updates.
- `frontend/src/router/index.js` — register `/admin/jobs` (admin/superadmin).

```bash
git add frontend/src/views/admin/ManageJobs.vue frontend/src/router/index.js
git commit -m "feat(jobs): admin manage-jobs view with full CRUD"
git push origin feature/jobs-frontend
```

> **Open PR:** `feature/jobs-frontend` → `main`. Title: *"Module 2 (frontend): browse, detail, and admin manage-jobs"*.

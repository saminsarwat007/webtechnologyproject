# Samin — Week 7
**Branch:** `feature/jobs-api`  
**Module owned:** **M2 — Job & Internship Management (backend, full CRUD)**  
**Goal:** every CRUD operation on `jobs` shipped this week.

---

## Step 1
**Read** — `index()` and `show()`.

**Files touched:**
- `backend/src/Modules/Jobs/JobController.php` — `index()` joins `jobs ⨝ companies`, supports `?search=`, `?type=`, `?company_id=` filters, only returns `is_active = 1`. `show($id)` returns one job + company + poster.
- `backend/public/index.php` — `GET /api/jobs` and `GET /api/jobs/{id}` (public, no JWT).

CRUD this commit demonstrates: **Read** (`SELECT … FROM jobs JOIN companies …`).

```bash
git checkout -b feature/jobs-api
git add backend/src/Modules/Jobs/JobController.php backend/public/index.php
git commit -m "feat(jobs): public list and detail endpoints"
git push -u origin feature/jobs-api
```

---

## Step 2
**Create** + **Update** — `create()` and `update()`.

**Files touched:**
- `backend/src/Modules/Jobs/JobController.php` — `create()` with full validation (title ≤ 150, type enum, deadline future date), pulls `posted_by` from `jwt_payload`. `update()` re-runs validation and re-fetches the row.
- `backend/public/index.php` — `POST /api/jobs` and `PUT /api/jobs/{id}` gated by `JwtMiddleware(['admin','superadmin'])`.

CRUD this commit demonstrates: **Create** (`INSERT INTO jobs`), **Update** (`UPDATE jobs SET …`).

```bash
git add backend/src/Modules/Jobs/JobController.php backend/public/index.php
git commit -m "feat(jobs): create and update with admin role gate"
git push origin feature/jobs-api
```

---

## Step 3
**Delete** — soft-delete via `is_active = 0`.

**Files touched:**
- `backend/src/Modules/Jobs/JobController.php` — `delete()` checks job exists, sets `is_active = 0`, returns `Job deactivated.`.
- `backend/public/index.php` — `DELETE /api/jobs/{id}` (admin/superadmin).

CRUD this commit demonstrates: **Delete** (soft-delete via `UPDATE jobs SET is_active = 0`).

```bash
git add backend/src/Modules/Jobs/JobController.php backend/public/index.php
git commit -m "feat(jobs): soft-delete endpoint"
git push origin feature/jobs-api
```

> **Open PR:** `feature/jobs-api` → `main`. Title: *"Module 2: full CRUD for jobs (create, read, update, soft-delete)"*.

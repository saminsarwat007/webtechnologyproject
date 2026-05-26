# Mariam — Week 9 (25–31 May 2026)
**Branch:** `feature/analytics-api`  
**Module owned:** **M5 — Reporting & Analytics (backend)**  
**Goal:** analytics endpoint + student profile CRUD (the data surface for student dashboard).

---

## Day 1 — Mon 25 May
**AdminController::analytics** — aggregate stats for the admin dashboard.

**Files touched:**
- `backend/src/Modules/Admin/AdminController.php` — `analytics()` returns `total_jobs`, `active_jobs`, `total_applications`, `pending_count`, `reviewed_count`, `accepted_count`, `rejected_count`, `applications_this_week` (using `YEARWEEK(applied_at, 1) = YEARWEEK(CURDATE(), 1)`).
- `backend/public/index.php` — `GET /api/admin/analytics` gated by `JwtMiddleware(['admin','superadmin'])`.

CRUD this commit demonstrates: **Read** (aggregate `COUNT(*)` queries on `jobs` and `applications`).

```bash
git checkout -b feature/analytics-api
git add backend/src/Modules/Admin/AdminController.php backend/public/index.php
git commit -m "feat(analytics): admin dashboard aggregate stats endpoint"
git push -u origin feature/analytics-api
```

---

## Day 2 — Wed 27 May
**ProfileController** — student profile **Read**.

**Files touched:**
- `backend/src/Modules/Profile/ProfileController.php` — `show()` joins `student_profiles ⨝ users` for the logged-in student; returns 404 if not yet set up.
- `backend/public/index.php` — `GET /api/profile` gated by `JwtMiddleware('student')`.

CRUD this commit demonstrates: **Read** (`SELECT … FROM student_profiles`).

```bash
git add backend/src/Modules/Profile/ProfileController.php backend/public/index.php
git commit -m "feat(profile): student profile read endpoint"
git push origin feature/analytics-api
```

---

## Day 3 — Fri 29 May
**ProfileController** — **Create + Update** in one upsert.

**Files touched:**
- `backend/src/Modules/Profile/ProfileController.php` — `upsert()` validates `matric_no` (unique, ≤ 20), `programme` (≤ 100), optional `cgpa` ∈ [0, 4]; `INSERT … ON DUPLICATE KEY UPDATE` covers both Create and Update.
- `backend/public/index.php` — `PUT /api/profile` gated by `JwtMiddleware('student')`.

CRUD this commit demonstrates: **Create** + **Update** in a single `INSERT … ON DUPLICATE KEY UPDATE` statement.

```bash
git add backend/src/Modules/Profile/ProfileController.php backend/public/index.php
git commit -m "feat(profile): student profile upsert (create + update)"
git push origin feature/analytics-api
```

> **Open PR:** `feature/analytics-api` → `main`. Title: *"Module 5: analytics endpoint + student profile CRUD"*.

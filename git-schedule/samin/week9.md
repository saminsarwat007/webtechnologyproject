# Samin — Week 9 (25–31 May 2026)
**Branch:** `feature/applications-api`  
**Module owned:** **M3 — Application Tracking System (backend, full CRUD)**  
**Goal:** every CRUD operation on `applications` shipped this week.

---

## Day 1 — Mon 25 May
**Read** — role-aware `index()`.

**Files touched:**
- `backend/src/Modules/Applications/ApplicationController.php` — `index()` returns own applications for `student` role and all applications for `admin`/`superadmin`; supports `?status=` filter; joins `applications ⨝ jobs ⨝ companies ⨝ users`.
- `backend/public/index.php` — `GET /api/applications` gated by `JwtMiddleware()` (any logged-in).

CRUD this commit demonstrates: **Read** with row-level access control via JWT role claim.

```bash
git checkout -b feature/applications-api
git add backend/src/Modules/Applications/ApplicationController.php backend/public/index.php
git commit -m "feat(applications): role-aware list endpoint"
git push -u origin feature/applications-api
```

---

## Day 2 — Wed 27 May
**Create** — student applies to a job.

**Files touched:**
- `backend/src/Modules/Applications/ApplicationController.php` — `create()` validates `job_id` and `cover_letter` (≤ 5000 chars); rejects 404 if job missing, 400 if job inactive, 409 if duplicate (relies on `UNIQUE(job_id, user_id)` index).
- `backend/public/index.php` — `POST /api/applications` gated by `JwtMiddleware('student')`.

CRUD this commit demonstrates: **Create** (`INSERT INTO applications`).

```bash
git add backend/src/Modules/Applications/ApplicationController.php backend/public/index.php
git commit -m "feat(applications): student apply endpoint with duplicate guard"
git push origin feature/applications-api
```

---

## Day 3 — Fri 29 May
**Update** + **Delete**.

**Files touched:**
- `backend/src/Modules/Applications/ApplicationController.php` — `updateStatus()` lets admins move `pending` → `reviewed`/`accepted`/`rejected`. `delete()` lets students withdraw — but only their **own** applications and only while `status = 'pending'`.
- `backend/public/index.php` — `PUT /api/applications/{id}/status` (admin/superadmin) and `DELETE /api/applications/{id}` (student).

CRUD this commit demonstrates: **Update** (`UPDATE applications SET status = …`) and **Delete** (`DELETE FROM applications WHERE …`).

```bash
git add backend/src/Modules/Applications/ApplicationController.php backend/public/index.php
git commit -m "feat(applications): admin status update + student withdraw"
git push origin feature/applications-api
```

> **Open PR:** `feature/applications-api` → `main`. Title: *"Module 3: full CRUD for applications (apply, list, status, withdraw)"*.

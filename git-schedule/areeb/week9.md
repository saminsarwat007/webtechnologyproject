# Areeb — Week 9 (25–31 May 2026)
**Branch:** `feature/admin-users`  
**Module owned:** **M6 — System Administration & Notifications (backend)**  
**Goal:** superadmin user directory endpoint + global error handler.

---

## Day 1 — Mon 25 May
**AdminController::users** — Read on `users` for superadmin only.

**Files touched:**
- `backend/src/Modules/Admin/AdminController.php` — `users()` returns all users (no password hashes), ordered by `created_at DESC`.
- `backend/public/index.php` — `GET /api/admin/users` gated by `JwtMiddleware('superadmin')`.

CRUD this commit demonstrates: **Read** (`SELECT … FROM users`).

```bash
git checkout -b feature/admin-users
git add backend/src/Modules/Admin/AdminController.php backend/public/index.php
git commit -m "feat(admin): superadmin users directory endpoint"
git push -u origin feature/admin-users
```

---

## Day 2 — Wed 27 May
Database seed file with verified bcrypt hashes.

**Files touched:**
- `database/seed.sql` — 4 demo users (1 superadmin, 1 admin, 2 students) with `Password123!` hashed via `password_hash(..., PASSWORD_BCRYPT)`; 4 sample companies; 10 jobs across types; 5 applications across statuses.

```bash
git add database/seed.sql
git commit -m "feat(db): seed users/companies/jobs/applications with bcrypt-verified hashes"
git push origin feature/admin-users
```

---

## Day 3 — Fri 29 May
Global error handler that always returns the JSON envelope.

**Files touched:**
- `backend/public/index.php` — `setDefaultErrorHandler` wraps every uncaught exception; respects `APP_DEBUG`; never leaks stack traces in production.

```bash
git add backend/public/index.php
git commit -m "feat(backend): global JSON error handler"
git push origin feature/admin-users
```

> **Open PR:** `feature/admin-users` → `main`. Title: *"Module 6: superadmin users + seed data + global error handler"*.

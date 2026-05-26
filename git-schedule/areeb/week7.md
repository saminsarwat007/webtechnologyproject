# Areeb — Week 7 (11–17 May 2026)
**Branch:** `feature/auth-module`  
**Module owned:** **M1 — User Authentication & Access Control**  
**Goal:** end-to-end auth — register/login backend (CRUD on `users`) + JWT middleware + CORS.

---

## Day 1 — Mon 11 May
Slim app bootstrap with CORS and module-aware imports.

**Files touched:**
- `backend/public/index.php` — load `.env`, register `CorsMiddleware`, error handler with uniform JSON envelope, health-check route, route stubs.
- `backend/src/Middleware/CorsMiddleware.php` — global CORS with origin allow-list from `.env`.
- `backend/src/Support/Json.php` — uniform JSON response envelope helper.

```bash
git checkout -b feature/auth-module
git add backend/public/index.php backend/src/Middleware/CorsMiddleware.php backend/src/Support/Json.php
git commit -m "feat(backend): bootstrap Slim app with CORS and JSON envelope"
git push -u origin feature/auth-module
```

---

## Day 2 — Wed 13 May
Implement **AuthController** (Module 1 — Create + Read on `users`).

**Files touched:**
- `backend/src/Modules/Auth/AuthController.php` — `register()` (Create user with bcrypt hash, returns JWT) and `login()` (Read user, verify password, returns JWT).
- `backend/public/index.php` — wire `POST /api/auth/register` and `POST /api/auth/login`.

CRUD this commit demonstrates: **Create** (`INSERT INTO users`), **Read** (`SELECT … FROM users WHERE email = :em`).

```bash
git add backend/src/Modules/Auth/AuthController.php backend/public/index.php
git commit -m "feat(auth): register and login with bcrypt + JWT issuance"
git push origin feature/auth-module
```

---

## Day 3 — Fri 15 May
Role-aware **JwtMiddleware** that gates routes by role.

**Files touched:**
- `backend/src/Middleware/JwtMiddleware.php` — verifies HS256 token, attaches `jwt_payload` request attribute, enforces required role(s).
- `backend/public/index.php` — apply `new JwtMiddleware(['admin','superadmin'])` example to a protected stub.

```bash
git add backend/src/Middleware/JwtMiddleware.php backend/public/index.php
git commit -m "feat(auth): JWT middleware with role gating"
git push origin feature/auth-module
```

> **Open PR:** `feature/auth-module` → `main`. Title: *"Module 1: Auth & Access Control (register, login, JWT, role gating)"*.

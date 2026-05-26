# Areeb — Week 11 (8–14 Jun 2026)
**Branch:** `feature/security-hardening`  
**Module owned:** M1 + M6 cross-cutting hardening  
**Goal:** harden auth/admin endpoints — input limits, defensive JSON encoding, audit prepared statements.

---

## Day 1 — Mon 8 Jun
SQL-injection audit across modules I own.

**Files touched:**
- `backend/src/Modules/Auth/AuthController.php` — confirm every query uses named placeholders; no string concatenation.
- `backend/src/Modules/Admin/AdminController.php` — same audit.
- `backend/src/Config/Database.php` — confirm `PDO::ATTR_EMULATE_PREPARES => false`.

```bash
git checkout -b feature/security-hardening
git add backend/src/Modules/Auth/AuthController.php backend/src/Modules/Admin/AdminController.php backend/src/Config/Database.php
git commit -m "chore(security): audit prepared statements in auth and admin modules"
git push -u origin feature/security-hardening
```

---

## Day 2 — Wed 10 Jun
Input length limits and stricter validation on user-supplied fields.

**Files touched:**
- `backend/src/Modules/Auth/AuthController.php` — enforce `full_name` ≤ 100, `email` ≤ 150, `password` ≥ 8.
- `backend/src/Support/Json.php` — set `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` flags so seeded Malaysian content survives the round-trip cleanly.

```bash
git add backend/src/Modules/Auth/AuthController.php backend/src/Support/Json.php
git commit -m "chore(security): input length limits + safer JSON encoding"
git push origin feature/security-hardening
```

---

## Day 3 — Fri 12 Jun
CORS lockdown + JWT secret check.

**Files touched:**
- `backend/src/Middleware/CorsMiddleware.php` — only echo `Access-Control-Allow-Origin` for the configured origin (or `*` if `CORS_ALLOW_ALL=1`).
- `backend/src/Middleware/JwtMiddleware.php` — refuse to verify if `JWT_SECRET` is empty (returns 500 with clear message).

```bash
git add backend/src/Middleware/CorsMiddleware.php backend/src/Middleware/JwtMiddleware.php
git commit -m "chore(security): CORS allow-list + JWT secret guard"
git push origin feature/security-hardening
```

> **Open PR:** `feature/security-hardening` → `main`. Title: *"Security hardening: prepared-statement audit, input limits, CORS lockdown"*.

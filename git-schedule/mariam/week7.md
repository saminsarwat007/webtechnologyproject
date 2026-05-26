# Mariam — Week 7 (11–17 May 2026)
**Branch:** `feature/companies-api`  
**Module owned:** **M4 — Company & External Source Management (backend, full CRUD)**  
**Goal:** every CRUD operation on `companies` shipped this week.

---

## Day 1 — Mon 11 May
**Read** — list all companies.

**Files touched:**
- `backend/src/Modules/Companies/CompanyController.php` — `index()` returns `company_id, name, industry, location, description, created_at` ordered by name.
- `backend/public/index.php` — `GET /api/companies` gated by `JwtMiddleware()` (any logged-in user, since students need it for filters).

CRUD this commit demonstrates: **Read** (`SELECT … FROM companies`).

```bash
git checkout -b feature/companies-api
git add backend/src/Modules/Companies/CompanyController.php backend/public/index.php
git commit -m "feat(companies): list endpoint"
git push -u origin feature/companies-api
```

---

## Day 2 — Wed 13 May
**Create** + **Update**.

**Files touched:**
- `backend/src/Modules/Companies/CompanyController.php` — `create()` validates name (≤ 150), industry (≤ 100), location (≤ 150); pulls `created_by` from `jwt_payload`. `update()` re-runs validation, 404 if missing, returns the fresh row.
- `backend/public/index.php` — `POST /api/companies` and `PUT /api/companies/{id}` gated by `JwtMiddleware(['admin','superadmin'])`.

CRUD this commit demonstrates: **Create** (`INSERT INTO companies`), **Update** (`UPDATE companies SET …`).

```bash
git add backend/src/Modules/Companies/CompanyController.php backend/public/index.php
git commit -m "feat(companies): create and update with admin role gate"
git push origin feature/companies-api
```

---

## Day 3 — Fri 15 May
**Delete** — hard delete with referential-integrity guard.

**Files touched:**
- `backend/src/Modules/Companies/CompanyController.php` — `delete()` first counts `jobs WHERE company_id = :id`; if any are linked, returns 400 "Cannot delete: company still has jobs attached." Otherwise `DELETE FROM companies …`. 404 if not found.
- `backend/public/index.php` — `DELETE /api/companies/{id}` (admin/superadmin).

CRUD this commit demonstrates: **Delete** (`DELETE FROM companies WHERE company_id = :id`) with FK guard.

```bash
git add backend/src/Modules/Companies/CompanyController.php backend/public/index.php
git commit -m "feat(companies): delete with attached-jobs guard"
git push origin feature/companies-api
```

> **Open PR:** `feature/companies-api` → `main`. Title: *"Module 4: full CRUD for companies (with FK guard on delete)"*.

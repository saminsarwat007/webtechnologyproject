# Areeb — Week 6 (4–10 May 2026)
**Branch:** `feature/project-setup`  
**Modules owned:** M1 Auth & Access Control · M6 System Admin & Notifications  
**This week's focus:** project foundation — composer, DB connection, schema. Auth code lands in Week 7.

---

## Day 1 — Mon 4 May
Scaffold the Slim 4 backend skeleton.

**Files touched:**
- `backend/composer.json` — declare `slim/slim`, `slim/psr7`, `firebase/php-jwt`, `vlucas/phpdotenv`; PSR-4 `App\\` → `src/`.
- `backend/src/Config/Database.php` — singleton PDO with `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, no emulated prepares.

```bash
git checkout -b feature/project-setup
git add backend/composer.json backend/src/Config/Database.php
git commit -m "chore(backend): scaffold composer + PDO singleton"
git push -u origin feature/project-setup
```

---

## Day 2 — Wed 6 May
Database schema for all 5 tables.

**Files touched:**
- `database/schema.sql` — `users`, `companies`, `jobs`, `student_profiles`, `applications` with FKs and the unique `(job_id, user_id)` index on applications.

```bash
git add database/schema.sql
git commit -m "feat(db): create schema for users/companies/jobs/profiles/applications"
git push origin feature/project-setup
```

---

## Day 3 — Fri 8 May
Environment template + Apache rewrite for production.

**Files touched:**
- `backend/.env.example` — `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `JWT_SECRET`.
- `backend/.htaccess` — front-controller rewrite to `index.php`.

```bash
git add backend/.env.example backend/.htaccess
git commit -m "chore(backend): add env template and Apache rewrite rules"
git push origin feature/project-setup
```

> **Open PR:** `feature/project-setup` → `main`. Title: *"Project setup: composer, schema, PDO config"*.

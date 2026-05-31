# CareerBridge — End-to-End Tests (Playwright)

Browser-driven tests that exercise the full SPA against the live PHP backend
and MySQL database. They are organised by module so any failure points
straight at the owner.

## Files

| File                            | Modules covered                                                 |
|---------------------------------|------------------------------------------------------------------|
| `01-smoke.spec.js`              | Frontend + backend reachability                                  |
| `02-auth.spec.js`               | M1 — Login, register, route guards, role-based nav               |
| `03-student-flow.spec.js`       | M2 / M3 / M5 — Browse jobs, apply, profile                       |
| `04-admin-flow.spec.js`         | M2 / M3 / M4 / M5 / M6 — Manage jobs, companies, applications, users |
| `05-forum.spec.js`              | **M7 — Monika** — Forum list, search, post CRUD, likes, comments, soft/hard delete |
| `06-labels.spec.js`             | **M8 — Monika** — Label CRUD, role gating, delete-while-in-use guard |
| `helpers/auth.js`               | Shared login/logout + unique-id helpers                          |

## Prerequisites

1. **MySQL** running with the database seeded:
   ```bash
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
   ```
2. **Backend** running on `http://localhost:8000`:
   ```bash
   cd backend && php -S localhost:8000 -t public
   ```
3. **Frontend** dev server is started automatically by Playwright (see
   `webServer` in `playwright.config.js`). If you prefer to run it manually:
   ```bash
   cd frontend && npm run dev
   ```

## First-time setup

```bash
cd frontend
npm install
npx playwright install chromium   # downloads the Chromium browser binary
```

## Running the tests

```bash
# Headless (CI mode)
npm run test:e2e

# With a visible browser
npm run test:e2e:headed

# Interactive UI mode (best for debugging individual tests)
npm run test:e2e:ui

# View the HTML report from the previous run
npm run test:e2e:report
```

Run a single file:
```bash
npx playwright test tests/05-forum.spec.js
```

Run a single test by title (regex):
```bash
npx playwright test -g "soft-delete"
```

## Notes

- Tests run **serially** (`workers: 1`) because they share one database.
- Each test that creates data uses a `uniqueSuffix()` to avoid collisions, so
  reseeding between runs is *not* required, but is recommended periodically.
- Screenshots, videos and traces of any failed test are stored in
  `playwright-report/` (open with `npm run test:e2e:report`).

# AI Prompt — Samin's GitHub Push (Continue from here)

Give this entire file to the AI at the start of the session.

---

## Context

- **Project:** CareerBridge (group web tech assignment)
- **Local project path:** `/Users/saminsmac/Projects/CareerBridge`
- **My GitHub (personal baseline):** `https://github.com/saminsarwat007/webtechnologyproject.git` (remote name: `origin`)
- **Team repo (lecturer sees this):** `https://github.com/azizah-utm/group-project-champion.git` (remote name: `team`)
- **My name in the project:** Samin Sarwat — owns **M2 Job & Internship Management** and **M3 Application Tracking System**

---

## Git identity (already configured — do not change)

```bash
git config --global user.email "saminsarwat.ai@gmail.com"
git config --global user.name "SAMIN SARWAT"
```

> ⚠️ The project has a **local git config** that was previously overriding the global email with `samin@student.utm.my`. This has been removed. Before every session, verify with:
> ```bash
> git config user.email   # must print saminsarwat.ai@gmail.com
> git config user.name    # must print SAMIN SARWAT
> ```
> If it prints something else, run: `git config --local --unset user.email && git config --local --unset user.name`

---

## What has already been pushed to the team repo

| Branch | Commits | Status |
|---|---|---|
| `main` | `git-schedule/samin/week6.md`, `week7.md` (date refs removed) | ✅ Done |
| `feature/vue-setup` | Week 6 — Vite scaffold, Tailwind, Axios (3 commits) | ✅ Done — needs PR |
| `feature/jobs-api` | Week 7 — JobController.php (3 commits) | ✅ Done — needs PR |
| `feature/jobs-frontend` | Week 8 — BrowseJobs, JobDetail, ManageJobs (3 commits) | ✅ Done — needs PR |
| `feature/applications-api` | Week 9 — ApplicationController.php (3 commits) | ✅ Done — needs PR |
| `feature/applications-frontend` | Week 10 — MyApplications, ManageApplications (2 commits) | ✅ Done — needs PR |

**All 5 PRs still to open on GitHub (do this manually in the browser):**
1. `feature/vue-setup` → `main` — `Frontend scaffolding: Vue 3 + Pinia + Tailwind + Axios`
2. `feature/jobs-api` → `main` — `Module 2: full CRUD for jobs (create, read, update, soft-delete)`
3. `feature/jobs-frontend` → `main` — `Module 2 (frontend): browse, detail, and admin manage-jobs`
4. `feature/applications-api` → `main` — `Module 3: full CRUD for applications (apply, list, status, withdraw)`
5. `feature/applications-frontend` → `main` — `Module 3 (frontend): track, moderate`

---

## What still needs to be pushed to the team repo

### Week 11 — `feature/jobs-apps-integration` ⚠️ BLOCKED
Cannot push as-is because these files do not exist in the local codebase:
- `frontend/src/composables/useValidation.js` (missing)
- `frontend/src/components/ApplyModal.vue` (missing)

This was a refactor week — would only make sense if the missing files get created first. Skip unless explicitly asked.

PR title (if ever pushed): `M2/M3 integration: shared validation, error handling, list reactivity`

---

### Week 12-14 — `feature/polish-jobs-apps` ⚠️ BLOCKED
This was a polish week — touches files already pushed in earlier branches (BrowseJobs, MyApplications, ManageJobs, ManageApplications, App.vue). Without distinct new content, the commits would just re-add the same files.

Skip unless asked to create separate "polish" commits that modify those files in some specific way.

PR title (if ever pushed): `M2/M3 polish: empty states, transitions, mobile responsive`

---

## Important rules for the AI

1. **First thing every session — verify git identity:**
   ```bash
   git config user.email   # must be saminsarwat.ai@gmail.com
   git config user.name    # must be SAMIN SARWAT
   ```
   If wrong, fix with:
   ```bash
   git config --local --unset user.email
   git config --local --unset user.name
   ```

2. **Never push to `origin` (saminsarwat007 personal repo) unless explicitly asked.** Always push to `team` remote.
3. **Always create feature branches from `team/main`**, not from local `main`.
   ```bash
   git checkout -b feature/<name> team/main
   ```
4. **Bring files in from local `main`** using:
   ```bash
   git checkout main -- <file>
   ```
5. **After finishing**, always run `git checkout main` to return to the local main branch.
6. **For `backend/public/index.php`** — this is a shared file. Never push the full file with all routes. Only include the bootstrap + Samin's routes (jobs or applications). Build it incrementally across the 3 commits (GET only → add POST/PUT → add DELETE).
7. **Do not touch** these files (owned by other team members):
   - `backend/src/Modules/Auth/`, `Companies/`, `Profile/`, `Admin/`, `Forums/`, `Interviews/`
   - `frontend/src/stores/auth.js`
   - `frontend/src/components/NavBar.vue`, `JobCard.vue`, `StatusBadge.vue`, `LoadingSpinner.vue`
   - `frontend/src/views/ForumList.vue`, `ForumPost.vue`, `Interviews.vue`, `ManageInterviews.vue`
   - `frontend/tests/`
8. **Remove all day/date references** from any schedule files before pushing (e.g. `Day 1 — Mon 4 May` → `Step 1`).
9. After each branch is pushed, **confirm with `git remote show team`** that the branch appears.

---

## Git remote setup (already done — no need to re-do)

```bash
# These remotes already exist on local machine
git remote -v
# origin  https://github.com/saminsarwat007/webtechnologyproject.git
# team    https://github.com/azizah-utm/group-project-champion.git
```

---

## Local codebase state

- Local `main` is clean and has the full CareerBridge codebase (all 8 modules).
- `git status` on `main` should show no modified files (only untracked `frontend/.vite/` which is harmless).
- The `backend/public/index.php` on local `main` has 175 lines — the full file with all routes. Do not overwrite it permanently.

# How to Push Code to GitHub — Team Guide

> Read this **once** before your first commit. Then keep it open as a checklist every time you start a new week's work.

This guide is written so that **Areeb, Samin, Mariam, and Monika** can each follow the same rhythm without stepping on each other's toes. The weekly plans in `git-schedule/<your-name>/weekN.md` tell you *what* to commit. **This file tells you *how*.**

**Repository:** <https://github.com/azizah-utm/group-project-champion>

---

## 0. One-Time Setup (do this on Day 1, ever)

Run these once on your laptop. You will never run them again.

### 0.1 Install Git and configure your identity

```bash
# Verify git is installed
git --version

# Set your name and email — must match your GitHub account email
git config --global user.name "Mohammad Areeb"     # change to your name
git config --global user.email "you@example.com"   # change to your GitHub email

# Sensible defaults
git config --global init.defaultBranch main
git config --global pull.rebase false
git config --global core.autocrlf input            # Mac/Linux; use "true" on Windows
```

### 0.2 Authenticate with GitHub (pick ONE method)

**Option A — Personal Access Token (easiest)**
1. Go to GitHub → Settings → Developer settings → Personal access tokens → **Tokens (classic)** → *Generate new token*.
2. Scope: tick `repo`. Expiry: 90 days.
3. Copy the token. **You only see it once.**
4. The first time you `git push`, paste the token when prompted for "Password".

**Option B — SSH key (recommended for long-term)**
```bash
ssh-keygen -t ed25519 -C "you@example.com"
cat ~/.ssh/id_ed25519.pub
```
Copy the printed key into GitHub → Settings → SSH and GPG keys → *New SSH key*.

### 0.3 Clone the repo

```bash
# pick ONE of these — match whatever the team lead used to create the repo
git clone https://github.com/azizah-utm/group-project-champion.git    # HTTPS + token
# or
git clone git@github.com:azizah-utm/group-project-champion.git        # SSH

cd group-project-champion
```

### 0.4 Verify you can push

```bash
git checkout -b sandbox/<your-name>-test
echo "test" > _delete_me.txt
git add _delete_me.txt
git commit -m "test: verify I can push"
git push -u origin sandbox/<your-name>-test
```

If that succeeds, **delete the branch** both locally and on GitHub:
```bash
git checkout main
git branch -D sandbox/<your-name>-test
git push origin --delete sandbox/<your-name>-test
rm _delete_me.txt
```

You're ready.

---

## 1. The Weekly Rhythm (every week, every member)

Each week your `git-schedule/<your-name>/weekN.md` file tells you:
- The branch name to use (e.g. `feature/jobs-api`)
- Which files to touch on Day 1, Day 2, Day 3
- The exact `git add` / `git commit` / `git push` commands

**Do not invent your own branch names. Use the ones in the schedule.**

### 1.1 Day 1 — Start the week's branch

Always start from the latest `main`:

```bash
git checkout main
git pull origin main                  # pull teammates' merged work
git checkout -b feature/<branch>      # name from your weekN.md
```

Do your Day-1 edits, then:

```bash
git status                            # check what changed
git add <only the files for Day 1>    # do NOT use "git add ." blindly
git commit -m "<exact message from weekN.md>"
git push -u origin feature/<branch>   # the -u sets the upstream
```

### 1.2 Day 2 — Mid-week commit

You're already on the branch. Just:

```bash
git pull origin feature/<branch>      # in case you pushed from another device
# do your Day-2 edits
git add <Day-2 files>
git commit -m "<exact message from weekN.md>"
git push origin feature/<branch>
```

### 1.3 Day 3 — Final commit + open Pull Request

```bash
# do your Day-3 edits
git add <Day-3 files>
git commit -m "<exact message from weekN.md>"
git push origin feature/<branch>
```

Then on GitHub:
1. Click *Compare & pull request* on your branch.
2. Title: use the **"Open PR"** title from your weekN.md.
3. Set base: `main`, compare: `feature/<branch>`.
4. **Add the other two members as reviewers.**
5. Click *Create pull request*.

### 1.4 After your PR is merged — clean up

```bash
git checkout main
git pull origin main
git branch -d feature/<branch>                    # delete local
git push origin --delete feature/<branch>         # delete remote
```

---

## 2. Per-Member Cheat Sheet

### 2.1 Areeb (Modules M1 + M6)

**Folders you own:**
- `backend/src/Modules/Auth/`
- `backend/src/Modules/Admin/AdminController.php` (the `users()` method only)
- `backend/src/Middleware/`
- `backend/src/Support/Json.php`
- `backend/composer.json`, `backend/.env.example`, `backend/.htaccess`
- `database/schema.sql`, `database/seed.sql`
- `frontend/src/views/auth/`
- `frontend/src/stores/auth.js`
- `frontend/src/router/index.js` (auth guards section)
- `frontend/src/components/ToastNotification.vue`, `ConfirmDialog.vue`
- `frontend/src/views/admin/AdminUsers.vue`

**Folders you must NOT edit** (without asking):
- `backend/src/Modules/Jobs/`, `Applications/` → Samin
- `backend/src/Modules/Companies/`, `Profile/` → Mariam
- `backend/src/Modules/Admin/AdminController.php::analytics()` → Mariam
- `backend/src/Modules/Forums/`, `Interviews/` → Monika
- `frontend/src/views/student/`, `frontend/src/views/admin/Manage*.vue` → Samin / Mariam
- `frontend/src/views/ForumList.vue`, `ForumPost.vue`, `Interviews.vue`, `ManageInterviews.vue` → Monika
- `frontend/tests/`, `frontend/playwright.config.js` → Monika

### 2.2 Samin (Modules M2 + M3)

**Folders you own:**
- `backend/src/Modules/Jobs/`
- `backend/src/Modules/Applications/`
- `frontend/src/views/student/BrowseJobs.vue`, `JobDetail.vue`, `MyApplications.vue`
- `frontend/src/views/admin/ManageJobs.vue`, `ManageApplications.vue`
- `frontend/src/components/ApplyModal.vue`
- `frontend/src/composables/useValidation.js`
- `frontend/package.json`, `frontend/vite.config.js`, `frontend/src/main.js`, `frontend/index.html`
- `frontend/src/services/api.js` (base instance — auth interceptors are Areeb's)
- `frontend/tailwind.config.js`, `postcss.config.js`, `frontend/src/assets/main.css`

**Folders you must NOT edit:**
- Anything under `backend/src/Modules/Auth/`, `Companies/`, `Profile/`, `Admin/`, `Forums/`, `Interviews/`
- `backend/src/Middleware/`
- `frontend/src/stores/auth.js`
- `frontend/src/components/NavBar.vue`, `JobCard.vue`, `StatusBadge.vue`, `LoadingSpinner.vue` (Mariam owns these)
- `frontend/src/views/ForumList.vue`, `ForumPost.vue`, `Interviews.vue`, `ManageInterviews.vue` (Monika)
- `frontend/tests/` (Monika)

### 2.3 Mariam (Modules M4 + M5)

**Folders you own:**
- `backend/src/Modules/Companies/`
- `backend/src/Modules/Profile/`
- `backend/src/Modules/Admin/AdminController.php` (the `analytics()` method only)
- `frontend/src/views/admin/ManageCompanies.vue`, `AdminDashboard.vue`
- `frontend/src/views/student/StudentDashboard.vue`, `Profile.vue`
- `frontend/src/components/NavBar.vue`, `JobCard.vue`, `StatusBadge.vue`, `LoadingSpinner.vue`
- `frontend/src/App.vue` (layout shell)
- `frontend/src/composables/useToast.js` (the toast bus — but ToastNotification.vue itself is Areeb's)
- `README.md` (you own the docs)

**Folders you must NOT edit:**
- Anything under `backend/src/Modules/Auth/`, `Jobs/`, `Applications/`, `Forums/`, `Interviews/`
- `backend/src/Middleware/`
- `frontend/src/stores/auth.js`, `router/index.js`
- `frontend/src/views/auth/`, `student/BrowseJobs.vue`, `JobDetail.vue`, `MyApplications.vue`
- `frontend/src/views/admin/ManageJobs.vue`, `ManageApplications.vue`
- `frontend/src/views/ForumList.vue`, `ForumPost.vue`, `Interviews.vue`, `ManageInterviews.vue` (Monika)
- `frontend/tests/` (Monika)

### 2.4 Monika (Modules M7 + M8)

**Folders you own:**
- `backend/src/Modules/Forums/` — `ForumController.php` (posts CRUD with free-text `tag`, comments add + flat delete, atomic like toggle, cascade delete)
- `backend/src/Modules/Interviews/` — `InterviewController.php` (slot CRUD for admins, bookings + cancel for students, admin evaluation with score + feedback)
- `frontend/src/views/ForumList.vue`, `ForumPost.vue`
- `frontend/src/views/Interviews.vue` (student), `ManageInterviews.vue` (admin)
- `frontend/tests/` (Playwright E2E suite for all 8 modules)
- `frontend/playwright.config.js`

**Shared files — coordinate before editing:**
- `database/schema.sql` and `database/seed.sql` (Areeb owns, you append M7/M8 tables)
- `backend/public/index.php` (everyone routes here — add your endpoints in a clearly-commented block; remember: declare the static `/api/forums/comments/{id}` route **before** the dynamic `/api/forums/{id}` so Slim doesn't capture the literal segment)
- `frontend/src/router/index.js` (Areeb owns auth section; append your `/forum*` + `/interviews` + `/admin/interviews` routes)
- `frontend/src/components/NavBar.vue` (Mariam owns; ask before appending "Forum" / "Mock Interviews" / "Interviews" links)
- `README.md` (Mariam owns; append your API rows)

**Folders you must NOT edit:**
- Anything under `backend/src/Modules/Auth/`, `Jobs/`, `Applications/`, `Companies/`, `Profile/`, `Admin/`
- `backend/src/Middleware/`
- `frontend/src/stores/auth.js`
- `frontend/src/views/auth/`, `student/`, `admin/` (except your four forum + interview views above)

---

## 3. Commit Message Rules

Use the messages **already written in your `weekN.md`**. They follow Conventional Commits:

```
<type>(<scope>): <imperative summary>
```

Allowed types we use:
- `feat` — a new feature or endpoint
- `fix` — a bug fix
- `chore` — config, deps, scaffolding, no behaviour change
- `docs` — README / markdown only
- `refactor` — rename or restructure, no behaviour change

Good examples (taken from the schedule):
- `feat(jobs): public list and detail endpoints`
- `feat(applications): admin status update + student withdraw`
- `chore(security): input length limits + safer JSON encoding`

Bad examples (do not do this):
- `update` ← what did you update?
- `fix bug` ← which bug?
- `WIP` ← never push WIP to a feature branch others will review
- `asdfgh` ← will be rejected in PR review

---

## 4. Common Mistakes and How to Recover

### 4.1 "I committed to `main` by accident"

```bash
# Move the last commit onto a new branch
git branch feature/<branch>
git reset --hard origin/main          # ⚠ destroys local main changes
git checkout feature/<branch>
git push -u origin feature/<branch>
```

### 4.2 "I committed the wrong files"

```bash
# Undo the last commit but keep the changes staged
git reset --soft HEAD~1
# Now unstage what shouldn't be in:
git restore --staged <wrong-file>
# Re-commit with only the correct files
git add <correct files>
git commit -m "<correct message>"
git push --force-with-lease origin feature/<branch>
```

> **Only use `--force-with-lease` on your own feature branch — never on `main`.**

### 4.3 "I forgot to pull and now `git push` is rejected"

```bash
git pull origin feature/<branch>      # merges remote changes
# resolve any conflicts (see 4.4)
git push origin feature/<branch>
```

### 4.4 Merge conflict during pull

Git marks conflicts inside the file like this:

```
<<<<<<< HEAD
your version
=======
their version
>>>>>>> origin/feature/<branch>
```

1. Open each conflicted file.
2. Decide which side wins (or merge by hand).
3. **Delete the `<<<<<<<`, `=======`, `>>>>>>>` markers.**
4. Save.

```bash
git add <resolved files>
git commit                            # git fills in a merge message
git push origin feature/<branch>
```

If you're stuck and want to abandon the merge:
```bash
git merge --abort
```

### 4.5 "I pushed something secret (password, .env)"

Tell the team in the group chat **immediately**, then:

```bash
# Remove from history
git rm --cached backend/.env
echo "backend/.env" >> .gitignore
git add .gitignore
git commit -m "chore: remove leaked .env from history"
git push --force-with-lease origin feature/<branch>
```

Then **rotate the secret** (change the JWT secret, change the DB password). The git history may still contain the leaked value if it was on `main` — ask the team lead before force-pushing on `main`.

### 4.6 "Two of us edited the same file"

This will happen. The fix is the merge-conflict flow in 4.4. To **prevent** it:
- Stick to your folders in the per-member cheat sheet above.
- If you absolutely must edit a shared file (e.g. `frontend/src/router/index.js`, `backend/public/index.php`, `README.md`), tell the others in the group chat **before** you start.

---

## 5. Pull Request Etiquette

When **you open** a PR:
- Title and body must reference your module (e.g. *"Module 2: full CRUD for jobs"*).
- Add the other two as reviewers.
- Do not click **Merge** yourself until at least one reviewer approves.

When **you review** someone else's PR:
- Pull their branch and run it locally:
  ```bash
  git fetch origin
  git checkout feature/<their-branch>
  composer install                  # if backend changed
  npm install && npm run dev        # if frontend changed
  ```
- Smoke-test the endpoints/views they added.
- If everything works: click **Approve**.
- If something breaks: leave a review comment on the exact line — don't just say "doesn't work".

---

## 6. The Forbidden List

Never do any of these without asking the whole team first:

1. ❌ `git push --force` on `main`
2. ❌ `git push -f` (use `--force-with-lease` instead, and only on your own feature branch)
3. ❌ `git rebase main` while on `main`
4. ❌ Delete the `main` branch
5. ❌ Commit `backend/.env` (real one, not `.env.example`)
6. ❌ Commit `node_modules/` or `vendor/`
7. ❌ Edit another member's controller without telling them
8. ❌ Merge your own PR without a review

---

## 7. Quick Reference Card

```bash
# Start the week
git checkout main && git pull
git checkout -b feature/<from-weekN.md>

# Each day
git status
git add <specific files>
git commit -m "<message from weekN.md>"
git push origin feature/<branch>

# End of week
# → Open PR on GitHub, get review, merge
git checkout main && git pull
git branch -d feature/<branch>
```

---

**Stuck?** Drop a screenshot of the error in the group chat *before* trying random commands. Most "broken git" situations can be fixed in one command — but the wrong command can lose a day's work.

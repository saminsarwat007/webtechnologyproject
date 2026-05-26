# Samin — Week 10 (1–7 Jun 2026)
**Branch:** `feature/applications-frontend`  
**Module owned:** **M3 — Application Tracking System (frontend)**  
**Goal:** student MyApplications + Apply modal + admin ManageApplications.

---

## Day 1 — Mon 1 Jun
**MyApplications** — student tracking view.

**Files touched:**
- `frontend/src/views/student/MyApplications.vue` — fetches `GET /api/applications`, status filter tabs (all / pending / reviewed / accepted / rejected), `StatusBadge` per row, "Withdraw" button (only on `pending`) opens `ConfirmDialog`, then `DELETE /api/applications/{id}`, reactive list update.
- `frontend/src/router/index.js` — register `/student/applications`.

```bash
git checkout -b feature/applications-frontend
git add frontend/src/views/student/MyApplications.vue frontend/src/router/index.js
git commit -m "feat(applications): student tracking view with withdraw"
git push -u origin feature/applications-frontend
```

---

## Day 2 — Wed 3 Jun
Apply modal shared between BrowseJobs and JobDetail.

**Files touched:**
- `frontend/src/components/ApplyModal.vue` — cover-letter textarea (≤ 5000 char counter), submit calls `POST /api/applications`, handles 409 ("already applied") gracefully via toast.
- `frontend/src/views/student/BrowseJobs.vue` — wire modal + tracking of already-applied job IDs (so the Apply button switches to "Applied").

```bash
git add frontend/src/components/ApplyModal.vue frontend/src/views/student/BrowseJobs.vue
git commit -m "feat(applications): shared apply modal + already-applied state"
git push origin feature/applications-frontend
```

---

## Day 3 — Fri 5 Jun
**ManageApplications** — admin moderation view.

**Files touched:**
- `frontend/src/views/admin/ManageApplications.vue` — table view with status filter tabs, per-row status `<select>` that calls `PUT /api/applications/{id}/status` with optimistic UI update + rollback on error, applicant + job columns.
- `frontend/src/router/index.js` — register `/admin/applications`.

```bash
git add frontend/src/views/admin/ManageApplications.vue frontend/src/router/index.js
git commit -m "feat(applications): admin moderation view with optimistic status update"
git push origin feature/applications-frontend
```

> **Open PR:** `feature/applications-frontend` → `main`. Title: *"Module 3 (frontend): track, apply, moderate"*.

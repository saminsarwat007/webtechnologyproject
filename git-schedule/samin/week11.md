# Samin — Week 11 (8–14 Jun 2026)
**Branch:** `feature/jobs-apps-integration`  
**Module owned:** M2 + M3 cross-cutting  
**Goal:** integration polish — shared validation, defensive error handling, and end-to-end flow checks.

---

## Day 1 — Mon 8 Jun
Shared validation composable.

**Files touched:**
- `frontend/src/composables/useValidation.js` — exports `validateRequired`, `validateMaxLength`, `validateFutureDate`, `validateEnum`. Reused by ApplyModal, BrowseJobs filter, ManageJobs create/edit modal.
- `frontend/src/views/admin/ManageJobs.vue` — replace inline validators with the composable.
- `frontend/src/components/ApplyModal.vue` — replace inline validators with the composable.

```bash
git checkout -b feature/jobs-apps-integration
git add frontend/src/composables/useValidation.js frontend/src/views/admin/ManageJobs.vue frontend/src/components/ApplyModal.vue
git commit -m "refactor(frontend): centralize jobs/apps validation in composable"
git push -u origin feature/jobs-apps-integration
```

---

## Day 2 — Wed 10 Jun
Defensive error handling for jobs & applications endpoints.

**Files touched:**
- `frontend/src/views/student/BrowseJobs.vue` — wrap fetch in try/catch with user-friendly error toast.
- `frontend/src/views/student/MyApplications.vue` — same; surfaces `errors` map from server validation responses.
- `frontend/src/views/admin/ManageApplications.vue` — rollback optimistic update on `PUT` failure.

```bash
git add frontend/src/views/student/BrowseJobs.vue frontend/src/views/student/MyApplications.vue frontend/src/views/admin/ManageApplications.vue
git commit -m "fix(frontend): defensive error handling for jobs/applications views"
git push origin feature/jobs-apps-integration
```

---

## Day 3 — Fri 12 Jun
End-to-end smoke test against my modules.

**Files touched:**
- `frontend/src/views/student/BrowseJobs.vue` — fix bug where applied state didn't refresh after submitting from JobDetail (added watcher on `jobs`).
- `frontend/src/views/admin/ManageJobs.vue` — fix bug where soft-deleted jobs still appeared until full reload (now filtered client-side).

```bash
git add frontend/src/views/student/BrowseJobs.vue frontend/src/views/admin/ManageJobs.vue
git commit -m "fix(frontend): jobs list reactivity after apply/delete"
git push origin feature/jobs-apps-integration
```

> **Open PR:** `feature/jobs-apps-integration` → `main`. Title: *"M2/M3 integration: shared validation, error handling, list reactivity"*.

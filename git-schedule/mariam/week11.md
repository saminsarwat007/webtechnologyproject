# Mariam — Week 11 (8–14 Jun 2026)
**Branch:** `feature/reporting-polish`  
**Module owned:** M4 + M5 cross-cutting polish  
**Goal:** dashboard UX polish + responsive admin/student summary views + accessibility pass.

---

## Day 1 — Mon 8 Jun
Chart polish + tooltip formatting.

**Files touched:**
- `frontend/src/views/admin/AdminDashboard.vue` — replace harsh defaults with brand palette; round corners on bars; tooltip shows percentage of total; chart re-renders on data refresh without leaking instances.

```bash
git checkout -b feature/reporting-polish
git add frontend/src/views/admin/AdminDashboard.vue
git commit -m "feat(analytics): chart palette, tooltip percentage, instance cleanup"
git push -u origin feature/reporting-polish
```

---

## Day 2 — Wed 10 Jun
Mobile responsiveness + accessibility for M4/M5 views.

**Files touched:**
- `frontend/src/views/admin/AdminDashboard.vue` — metric cards stack `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`; chart container has explicit aria-label.
- `frontend/src/views/student/StudentDashboard.vue` — same responsive grid; recent-apps table → card list under `md`.
- `frontend/src/views/admin/ManageCompanies.vue` — table → card list under `md`; modals fill width on mobile.

```bash
git add frontend/src/views/admin/AdminDashboard.vue frontend/src/views/student/StudentDashboard.vue frontend/src/views/admin/ManageCompanies.vue
git commit -m "feat(ui): responsive + accessible M4/M5 views"
git push origin feature/reporting-polish
```

---

## Day 3 — Fri 12 Jun
Empty/error states for analytics views.

**Files touched:**
- `frontend/src/views/admin/AdminDashboard.vue` — error banner with retry button if `/api/admin/analytics` fails.
- `frontend/src/views/student/StudentDashboard.vue` — empty state when student has zero applications, deep-link to BrowseJobs.
- `frontend/src/views/student/Profile.vue` — banner "Complete your profile" while 404 from initial GET.

```bash
git add frontend/src/views/admin/AdminDashboard.vue frontend/src/views/student/StudentDashboard.vue frontend/src/views/student/Profile.vue
git commit -m "feat(ui): empty and error states for analytics views"
git push origin feature/reporting-polish
```

> **Open PR:** `feature/reporting-polish` → `main`. Title: *"M4/M5 polish: chart UX, responsive layouts, empty/error states"*.

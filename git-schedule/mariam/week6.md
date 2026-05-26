# Mariam — Week 6 (4–10 May 2026)
**Branch:** `feature/base-components`  
**Modules owned:** M4 Companies · M5 Reporting & Analytics  
**This week's focus:** the shared component library every other view depends on.

---

## Day 1 — Mon 4 May
Layout shell + role-aware navigation.

**Files touched:**
- `frontend/src/App.vue` — sticky `<NavBar />`, main `<RouterView />`, footer.
- `frontend/src/components/NavBar.vue` — links derived from auth store role: students see `Browse Jobs / My Applications / Profile`; admins see `Dashboard / Jobs / Applications / Companies`; superadmin additionally sees `Users`. Logout button.

```bash
git checkout -b feature/base-components
git add frontend/src/App.vue frontend/src/components/NavBar.vue
git commit -m "feat(ui): layout shell and role-aware NavBar"
git push -u origin feature/base-components
```

---

## Day 2 — Wed 6 May
Job card + status badge.

**Files touched:**
- `frontend/src/components/JobCard.vue` — title, company name, location, type badge, deadline, slot for action button.
- `frontend/src/components/StatusBadge.vue` — color-coded badge for `pending` / `reviewed` / `accepted` / `rejected`.

```bash
git add frontend/src/components/JobCard.vue frontend/src/components/StatusBadge.vue
git commit -m "feat(ui): JobCard and StatusBadge components"
git push origin feature/base-components
```

---

## Day 3 — Fri 8 May
Loading spinner.

**Files touched:**
- `frontend/src/components/LoadingSpinner.vue` — accessible spinner with `role="status"`, sr-only text, size prop (`sm`/`md`/`lg`).

```bash
git add frontend/src/components/LoadingSpinner.vue
git commit -m "feat(ui): LoadingSpinner component"
git push origin feature/base-components
```

> **Open PR:** `feature/base-components` → `main`. Title: *"Shared component library: NavBar, JobCard, StatusBadge, LoadingSpinner"*.

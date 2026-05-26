# Mariam — Week 10 (1–7 Jun 2026)
**Branch:** `feature/analytics-frontend`  
**Module owned:** **M5 — Reporting & Analytics (frontend)**  
**Goal:** AdminDashboard with Chart.js + StudentDashboard + student Profile screen.

---

## Day 1 — Mon 1 Jun
**AdminDashboard** with metric cards + Chart.js bar chart.

**Files touched:**
- `frontend/src/views/admin/AdminDashboard.vue` — fetches `GET /api/admin/analytics`; metric cards for total_jobs / active_jobs / total_applications / applications_this_week; bar chart of application statuses using Chart.js; `LoadingSpinner` while loading.
- `frontend/package.json` — add `chart.js`.
- `frontend/src/router/index.js` — register `/admin/dashboard`.

```bash
git checkout -b feature/analytics-frontend
git add frontend/src/views/admin/AdminDashboard.vue frontend/package.json frontend/src/router/index.js
git commit -m "feat(analytics): admin dashboard with Chart.js"
git push -u origin feature/analytics-frontend
```

---

## Day 2 — Wed 3 Jun
**StudentDashboard** consuming the same analytics shape, scoped to the user.

**Files touched:**
- `frontend/src/views/student/StudentDashboard.vue` — fetches `GET /api/applications` (own only by JWT role) and `GET /api/profile`; metric cards for total/pending/accepted; recent-applications table with `StatusBadge`.
- `frontend/src/router/index.js` — register `/student/dashboard`.

```bash
git add frontend/src/views/student/StudentDashboard.vue frontend/src/router/index.js
git commit -m "feat(analytics): student dashboard with stat cards and recent applications"
git push origin feature/analytics-frontend
```

---

## Day 3 — Fri 5 Jun
**Profile** view consuming `GET /api/profile` + `PUT /api/profile`.

**Files touched:**
- `frontend/src/views/student/Profile.vue` — form for matric_no, programme, cgpa, skills, resume_text; on mount calls `GET` (404 → blank form for new user); submit calls `PUT`; client-side validation matches the backend rules; success toast.
- `frontend/src/router/index.js` — register `/student/profile`.

```bash
git add frontend/src/views/student/Profile.vue frontend/src/router/index.js
git commit -m "feat(profile): student profile view with upsert"
git push origin feature/analytics-frontend
```

> **Open PR:** `feature/analytics-frontend` → `main`. Title: *"Module 5 (frontend): admin dashboard, student dashboard, profile"*.

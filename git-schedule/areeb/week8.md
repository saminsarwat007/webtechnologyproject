# Areeb — Week 8 (18–24 May 2026)
**Branch:** `feature/auth-frontend`  
**Module owned:** **M1 — User Authentication & Access Control (frontend half)**  
**Goal:** wire the Vue UI for register/login on top of the API I shipped in Week 7.

---

## Day 1 — Mon 18 May
Pinia auth store + Axios JWT interceptor.

**Files touched:**
- `frontend/src/stores/auth.js` — Pinia store with `token`, `user`, `login()`, `register()`, `logout()`; hydrates from `localStorage`; decodes JWT for role.
- `frontend/src/services/api.js` — Axios request interceptor adds `Authorization: Bearer <token>`; 401 response interceptor clears storage + redirects to `/login`.

```bash
git checkout -b feature/auth-frontend
git add frontend/src/stores/auth.js frontend/src/services/api.js
git commit -m "feat(auth): Pinia store + Axios interceptors for JWT"
git push -u origin feature/auth-frontend
```

---

## Day 2 — Wed 20 May
Login + Register views.

**Files touched:**
- `frontend/src/views/auth/Login.vue` — email/password form, validation, role-aware redirect (`student` → `/student/dashboard`, `admin`/`superadmin` → `/admin/dashboard`).
- `frontend/src/views/auth/Register.vue` — full validation (name 3+ chars, email regex, password 8+ chars), auto-login on success.

```bash
git add frontend/src/views/auth/Login.vue frontend/src/views/auth/Register.vue
git commit -m "feat(auth): login and register views with validation"
git push origin feature/auth-frontend
```

---

## Day 3 — Fri 22 May
Router with role-based navigation guards.

**Files touched:**
- `frontend/src/router/index.js` — public, student-only, and admin-only route groups; `beforeEach` guard reads role from auth store and redirects unauthorized users.
- `frontend/src/main.js` — register router + Pinia.

```bash
git add frontend/src/router/index.js frontend/src/main.js
git commit -m "feat(auth): role-based route guards"
git push origin feature/auth-frontend
```

> **Open PR:** `feature/auth-frontend` → `main`. Title: *"Module 1 (frontend): login, register, role guards"*.

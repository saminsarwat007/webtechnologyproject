# Mariam — Week 8 (18–24 May 2026)
**Branch:** `feature/companies-frontend`  
**Module owned:** **M4 — Company & External Source Management (frontend)**  
**Goal:** admin ManageCompanies UI exercising every endpoint from Week 7.

---

## Day 1 — Mon 18 May
**ManageCompanies** table + create modal.

**Files touched:**
- `frontend/src/views/admin/ManageCompanies.vue` — fetches `GET /api/companies`, renders sortable table (name / industry / location / created_at), "New company" button opens create modal that POSTs to `/api/companies` with client-side validation.
- `frontend/src/router/index.js` — register `/admin/companies` (admin/superadmin).

```bash
git checkout -b feature/companies-frontend
git add frontend/src/views/admin/ManageCompanies.vue frontend/src/router/index.js
git commit -m "feat(companies): admin list and create"
git push -u origin feature/companies-frontend
```

---

## Day 2 — Wed 20 May
Edit modal sharing the form.

**Files touched:**
- `frontend/src/views/admin/ManageCompanies.vue` — extract `CompanyFormModal` sub-template usable for both create and edit; row "Edit" pre-fills it and sends `PUT /api/companies/{id}`; reactive list update on success.

```bash
git add frontend/src/views/admin/ManageCompanies.vue
git commit -m "feat(companies): edit modal reusing the create form"
git push origin feature/companies-frontend
```

---

## Day 3 — Fri 22 May
Delete with `ConfirmDialog` + 400 handling for FK guard.

**Files touched:**
- `frontend/src/views/admin/ManageCompanies.vue` — row "Delete" opens `ConfirmDialog`, calls `DELETE /api/companies/{id}`. If server returns 400 ("still has jobs attached"), surface it via error toast — do NOT remove the row.

```bash
git add frontend/src/views/admin/ManageCompanies.vue
git commit -m "feat(companies): delete with confirm and FK-guard handling"
git push origin feature/companies-frontend
```

> **Open PR:** `feature/companies-frontend` → `main`. Title: *"Module 4 (frontend): admin manage-companies with full CRUD"*.

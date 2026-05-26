# Areeb — Week 10 (1–7 Jun 2026)
**Branch:** `feature/notifications`  
**Module owned:** **M6 — System Administration & Notifications (frontend)**  
**Goal:** site-wide notification + confirmation primitives, plus the AdminUsers screen.

---

## Day 1 — Mon 1 Jun
Toast notification system.

**Files touched:**
- `frontend/src/components/ToastNotification.vue` — `<Transition>` slide-in, auto-dismiss after 4s, success/error/info variants.
- `frontend/src/composables/useToast.js` — tiny event-bus composable (`success(msg)`, `error(msg)`, `info(msg)`).
- `frontend/src/App.vue` — mount `<ToastNotification />` once at the root.

```bash
git checkout -b feature/notifications
git add frontend/src/components/ToastNotification.vue frontend/src/composables/useToast.js frontend/src/App.vue
git commit -m "feat(ui): global toast notification system"
git push -u origin feature/notifications
```

---

## Day 2 — Wed 3 Jun
Confirm-dialog primitive (used by every destructive action).

**Files touched:**
- `frontend/src/components/ConfirmDialog.vue` — modal with focus trap, Esc to cancel, Enter to confirm, danger variant for deletes.

```bash
git add frontend/src/components/ConfirmDialog.vue
git commit -m "feat(ui): accessible confirm dialog component"
git push origin feature/notifications
```

---

## Day 3 — Fri 5 Jun
**AdminUsers** view consuming `GET /api/admin/users`.

**Files touched:**
- `frontend/src/views/admin/AdminUsers.vue` — table view with role badge, local search, loading + empty states, error toast on failure.
- `frontend/src/router/index.js` — register `/admin/users` (superadmin only).

```bash
git add frontend/src/views/admin/AdminUsers.vue frontend/src/router/index.js
git commit -m "feat(admin): superadmin users directory view"
git push origin feature/notifications
```

> **Open PR:** `feature/notifications` → `main`. Title: *"Module 6 (frontend): toasts, confirm dialog, admin users view"*.

# Monika — Week 11 (8–14 Jun 2026)
**Branch:** `feature/m8-bookings-and-evaluation`
**Module owned:** **M8 — Mock Interview & Technical Prep Scheduler (bookings + evaluation + frontend)**
**Goal:** finish M8 — student bookings, admin evaluation, and the two M8 Vue views.

---

## Day 1 — Mon 8 Jun
Booking endpoints — student books a slot, edits category, or cancels.

**Files touched:**
- `backend/src/Modules/Interviews/InterviewController.php` —
  - `bookSlot()` runs in a transaction with `SELECT … FOR UPDATE` on the slot row to prevent two students racing the same slot. Inserts a `mock_interviews` row with `status='pending'`, then flips `interview_slots.is_booked = 1`.
  - `mySessions()` returns the student's own bookings + scheduled times + feedback/score.
  - `updateBooking($id)` lets the student edit `job_category` *or* set `cancel: true` to cancel a pending booking — cancelling also frees the slot (`is_booked = 0`).
- `backend/public/index.php` —
  - `GET  /api/interviews/my-sessions`        (student)
  - `POST /api/interviews/bookings`           (student)
  - `PUT  /api/interviews/bookings/{id}`      (student)

CRUD this commit demonstrates: **Read** + **Create** + **Update** on `mock_interviews`.

```bash
git checkout -b feature/m8-bookings-and-evaluation
git add backend/src/Modules/Interviews/InterviewController.php backend/public/index.php
git commit -m "feat(interviews): student booking + cancel + my-sessions endpoints"
git push -u origin feature/m8-bookings-and-evaluation
```

---

## Day 2 — Wed 10 Jun
Admin oversight + evaluation.

**Files touched:**
- `backend/src/Modules/Interviews/InterviewController.php` —
  - `adminList()` returns every booking joined with student + interviewer names.
  - `evaluate($id)` validates `score 0–100` and non-empty `feedback_text`, sets `status` to `completed` or `cancelled`.
- `backend/public/index.php` —
  - `GET  /api/interviews/admin/manage`        (admin, superadmin)
  - `PUT  /api/interviews/admin/evaluate/{id}` (admin, superadmin)

CRUD this commit demonstrates: **Read** + **Update** on `mock_interviews` from the admin side.

```bash
git add backend/src/Modules/Interviews/InterviewController.php backend/public/index.php
git commit -m "feat(interviews): admin manage + evaluate endpoints"
git push origin feature/m8-bookings-and-evaluation
```

---

## Day 3 — Fri 12 Jun
M8 frontend — the student Mock Interviews page and the admin Manage Interviews page.

**Files touched:**
- `frontend/src/views/Interviews.vue` — student view, two tabs (Available slots / My sessions), book modal asking for `job_category`, cancel + edit-category for pending bookings, score + feedback shown for completed sessions.
- `frontend/src/views/ManageInterviews.vue` — admin view, two tabs (Slots / Bookings), datetime-local input + validation in the create-slot modal, evaluate modal with score (0-100) + feedback.
- `frontend/src/router/index.js` — `/interviews` (student) and `/admin/interviews` (admin).
- `frontend/src/components/NavBar.vue` — append "Mock Interviews" to the student nav and "Interviews" to the admin nav.

```bash
git add frontend/src/views/Interviews.vue frontend/src/views/ManageInterviews.vue frontend/src/router/index.js frontend/src/components/NavBar.vue
git commit -m "feat(interviews): student + admin views for M8"
git push origin feature/m8-bookings-and-evaluation
```

> **Open PR:** `feature/m8-bookings-and-evaluation` → `main`. Title: *"Module 8: full booking flow + evaluation + UI"*.

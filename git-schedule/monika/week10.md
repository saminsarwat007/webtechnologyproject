# Monika — Week 10 (1–7 Jun 2026)
**Branch:** `feature/m8-interviews-schema-and-slots`
**Module owned:** **M8 — Mock Interview & Technical Prep Scheduler (schema + slot CRUD)**
**Goal:** new tables + the admin slot endpoints students will browse next week.

> 🗣 **Coordinate with Areeb before pushing:** you are appending two more tables to `database/schema.sql`. Drop a heads-up in the group chat.

---

## Day 1 — Mon 1 Jun
Add the two M8 tables.

**Files touched:**
- `database/schema.sql` —
  - `interview_slots (slot_id PK, interviewer_id FK→users, scheduled_at DATETIME, is_booked BOOLEAN DEFAULT FALSE, created_at)` — admin-published availability windows.
  - `mock_interviews (interview_id PK, slot_id FK→interview_slots ON DELETE CASCADE, student_id FK→users, job_category VARCHAR(100), status ENUM('pending','completed','cancelled'), feedback_text TEXT NULL, score INT NULL, UNIQUE(slot_id))` — the booking + evaluation row.
- `database/seed.sql` — 5 interview slots (`+7d`, `+10d`, `+14d`, `+3d`, `+21d` from now; slot 4 starts as booked) + one seeded pending booking against slot 4 so the admin Bookings tab has data.

CRUD this commit demonstrates: **Schema** for M8.

```bash
git checkout -b feature/m8-interviews-schema-and-slots
git add database/schema.sql database/seed.sql
git commit -m "feat(db): interview_slots + mock_interviews tables for M8"
git push -u origin feature/m8-interviews-schema-and-slots
```

---

## Day 2 — Wed 3 Jun
Slot endpoints — list + admin Create + admin Delete.

**Files touched:**
- `backend/src/Modules/Interviews/InterviewController.php` — `listSlots()` (any logged-in: future + unbooked by default; `?all=1` shows everything for admins), `createSlot()` (admin: must be a future datetime, interviewer must have admin role), `deleteSlot()` (admin: refuses 409 if `is_booked = 1`).

CRUD this commit demonstrates: **Read** + **Create** + **Delete** on `interview_slots`.

```bash
git add backend/src/Modules/Interviews/InterviewController.php
git commit -m "feat(interviews): list + admin create/delete for slots"
git push origin feature/m8-interviews-schema-and-slots
```

---

## Day 3 — Fri 5 Jun
Wire the slot routes.

**Files touched:**
- `backend/public/index.php` —
  - `GET    /api/interviews/slots`         (any logged-in)
  - `POST   /api/interviews/slots`         (admin, superadmin)
  - `DELETE /api/interviews/slots/{id}`    (admin, superadmin)

```bash
git add backend/public/index.php
git commit -m "feat(interviews): wire slot routes for M8"
git push origin feature/m8-interviews-schema-and-slots
```

> **Open PR:** `feature/m8-interviews-schema-and-slots` → `main`. Title: *"Module 8: schema + slot CRUD endpoints"*.

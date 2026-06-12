# CareerBridge

> Student Internship & Job Application Management System

CareerBridge is a full-stack web application that connects university students with internship and full-time opportunities. Students browse listings, apply with a cover letter, and track their application status. Admins post jobs, review applications, and manage partner companies. A super-admin oversees the entire user base. Students can also prepare for the job hunt through a community forum and a mock-interview scheduler.

---

## Team

| Name             | Matric No  | Modules Owned                                       |
|------------------|-----------|------------------------------------------------------|
| Mohammad Areeb   | A22EC4041 | M1 Auth & Access Control · M6 System Admin & Notifications |
| Samin Sarwat     | A22EC4040 | M2 Job & Internship Management · M3 Application Tracking |
| Mariam Hanif     | A22EC4034 | M4 Company & External Source · M5 Reporting & Analytics |
| Monika Zelenkov  | A22EC4045 | M7 Forum & Discussion (with Labels/Tags) · M8 Mock Interview & Technical Prep |

**Course:** SECJ3483 Web Technology  
**Academic Session:** 2025/2026

---

## Modules & Ownership

The system is organised into eight functional modules. Each module is owned end-to-end by one team member who personally implements the backend CRUD endpoints **and** the matching Vue views. This makes CRUD ownership explicit in the commit history.

| #  | Module                                    | Owner   | Backend (CRUD)                                                                                | Frontend                                                            |
|----|-------------------------------------------|---------|------------------------------------------------------------------------------------------------|---------------------------------------------------------------------|
| M1 | User Authentication & Access Control      | Areeb   | `Modules/Auth/AuthController` (Create+Read on `users`), `Middleware/JwtMiddleware`, `CorsMiddleware` | `views/LoginView.vue`, `views/RegisterView.vue`, `stores/auth.js`, route guards |
| M2 | Job & Internship Management               | Samin   | `Modules/Jobs/JobController` — **full CRUD** on `jobs`                                         | `views/BrowseJobs.vue`, `views/JobDetail.vue`, `views/ManageJobs.vue` |
| M3 | Application Tracking System               | Samin   | `Modules/Applications/ApplicationController` — **full CRUD** on `applications`                 | `views/MyApplications.vue`, `views/ManageApplications.vue` |
| M4 | Company & External Source Management      | Mariam  | `Modules/Companies/CompanyController` — **full CRUD** on `companies`                           | `views/ManageCompanies.vue`                                   |
| M5 | Reporting & Analytics                     | Mariam  | `Modules/Admin/AdminController::analytics` + `Modules/Profile/ProfileController` (upsert)      | `views/AdminDashboard.vue` (Chart.js), `views/StudentDashboard.vue`, `views/StudentProfile.vue` |
| M6 | System Administration & Notifications     | Areeb   | `Modules/Admin/AdminController::users`, global error handler                                   | `views/AdminUsers.vue`, `components/ToastNotification.vue`, `components/ConfirmDialog.vue` |
| M7 | Forum & Discussion (with Labels/Tags)     | Monika  | `Modules/Forums/ForumController` — posts CRUD, comments, like toggle, soft/hard delete · `Modules/Labels/LabelController` — label/tag CRUD used to categorise posts (delete blocked while posts are attached) | `views/ForumDashboardView.vue`, `views/ForumDetailView.vue`, `components/forum/PostCard.vue`, `components/forum/CommentSection.vue`, `views/ManageLabels.vue`, `composables/useForum.js` |
| M8 | Mock Interview & Technical Prep           | Monika  | `Modules/Interviews/InterviewController` — slots CRUD, student bookings, admin evaluation (**full CRUD** across `interview_slots` + `mock_interviews`) | `views/InterviewSlotsView.vue`, `views/MockInterviewDashboardView.vue`, `views/BookInterviewView.vue`, `components/interview/SlotCard.vue`, `components/interview/FeedbackModal.vue`, `composables/useInterview.js` |

> **Note on Labels/Tags:** label management is *not* a standalone module. It is implemented as a tagging feature inside the Forum module — labels exist to categorise and filter forum posts, and are surfaced through the forum views and the `ManageLabels` screen.

---

## Tech Stack

| Layer       | Technology                                |
|-------------|-------------------------------------------|
| Frontend    | Vue.js 3 + Vite + Pinia + Vue Router      |
| Styling     | Tailwind CSS                              |
| Charts      | Chart.js                                  |
| HTTP client | Axios                                     |
| Backend     | PHP 7.4+ with Slim 4                      |
| Auth        | JWT (firebase/php-jwt, HS256, 24h expiry) |
| Database    | MySQL 5.7+ / MariaDB 10.3+                |
| Env loader  | vlucas/phpdotenv                          |

---

## Prerequisites

You'll need the following installed before getting started:

- **PHP** 7.4 or newer (`php -v`)
- **Composer** ([getcomposer.org](https://getcomposer.org/))
- **Node.js** 18 or newer + npm
- **MySQL** 5.7+ or MariaDB 10.3+ (XAMPP / MAMP / Laragon are all fine)
- A web server that can run PHP. The fastest path is **PHP's built-in server** (instructions below). XAMPP/Apache also works.

---

## Setup

### 1. Clone the repository

```bash
git clone https://github.com/<your-org>/careerbridge.git
cd careerbridge
```

### 2. Create the database

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

This creates a database called `careerbridge` and seeds it with realistic Malaysian internship/job data plus 4 demo accounts.

### 3. Configure & install the backend

```bash
cd backend
cp .env.example .env
# edit .env and set DB_USER / DB_PASS to match your local MySQL credentials
composer install
```

### 4. Run the backend

Pick one of these:

**Option A — PHP built-in server (simplest):**

```bash
# from inside the backend/ folder
php -S localhost:8000 -t public
```

The API is now reachable at `http://localhost:8000`. If you choose this option, also update `frontend/vite.config.js` so the proxy points to it:

```js
'/api': { target: 'http://localhost:8000', changeOrigin: true }
```

**Option B — XAMPP/Apache:**

Place the project under `htdocs/careerbridge/` so the API lives at `http://localhost/careerbridge/backend/public`. The default `frontend/vite.config.js` is already configured for this layout.

### 5. Run the frontend

```bash
cd frontend
npm install
npm run dev
```

The Vite dev server starts on `http://localhost:5173`. Open it in your browser.

---

## Default Login Credentials

All seeded accounts share the same password: **`Password123!`**

| Role         | Email                          |
|--------------|--------------------------------|
| Super Admin  | `superadmin@careerbridge.my`   |
| Admin        | `farizal@careerbridge.my`      |
| Student      | `areeb@student.utm.my`         |
| Student      | `samin@student.utm.my`         |

> Change `JWT_SECRET` in `backend/.env` before deploying anywhere public.

---

## API Reference

All endpoints are JSON. Protected routes require a `Authorization: Bearer <token>` header obtained from `/api/auth/login`.

### Auth

| Method | Path                  | Auth | Description                       |
|--------|-----------------------|------|-----------------------------------|
| POST   | `/api/auth/register`  | —    | Create a student or admin account |
| POST   | `/api/auth/login`     | —    | Exchange email/password for a JWT |

### Jobs

| Method | Path              | Auth                  | Description                              |
|--------|-------------------|-----------------------|------------------------------------------|
| GET    | `/api/jobs`       | Public                | List active jobs (search/type filters)   |
| GET    | `/api/jobs/{id}`  | Public                | Single job with company + poster         |
| POST   | `/api/jobs`       | admin / superadmin    | Create a job listing                     |
| PUT    | `/api/jobs/{id}`  | admin / superadmin    | Update a job listing                     |
| DELETE | `/api/jobs/{id}`  | admin / superadmin    | Soft-delete (sets `is_active=false`)     |

### Applications

| Method | Path                                | Auth                  | Description                                       |
|--------|-------------------------------------|-----------------------|---------------------------------------------------|
| GET    | `/api/applications`                 | any logged-in         | Students see own; admins see all                  |
| POST   | `/api/applications`                 | student               | Apply to a job (returns 409 on duplicate)         |
| PUT    | `/api/applications/{id}/status`     | admin / superadmin    | Change status (pending/reviewed/accepted/rejected)|
| DELETE | `/api/applications/{id}`            | student (own + pending) | Withdraw a pending application                  |

### Profile

| Method | Path           | Auth    | Description                      |
|--------|----------------|---------|----------------------------------|
| GET    | `/api/profile` | student | Read the student's profile (404 if not yet created) |
| PUT    | `/api/profile` | student | Upsert the student's profile     |

### Companies

| Method | Path                  | Auth                  | Description           |
|--------|-----------------------|-----------------------|-----------------------|
| GET    | `/api/companies`      | any logged-in         | List companies        |
| POST   | `/api/companies`      | admin / superadmin    | Create a company      |
| PUT    | `/api/companies/{id}` | admin / superadmin    | Update a company      |
| DELETE | `/api/companies/{id}` | admin / superadmin    | Delete (blocked if jobs are still attached) |

### Admin

| Method | Path                    | Auth                  | Description                              |
|--------|-------------------------|-----------------------|------------------------------------------|
| GET    | `/api/admin/users`      | superadmin            | All users (no password hashes)           |
| GET    | `/api/admin/analytics`  | admin / superadmin    | Dashboard metrics (counts + this-week)   |

### Forum

| Method | Path                                              | Auth                  | Description                                       |
|--------|---------------------------------------------------|-----------------------|---------------------------------------------------|
| GET    | `/api/forums`                                     | any logged-in         | List posts (search/label filters, sorted by likes) |
| GET    | `/api/forums/{id}`                                | any logged-in         | Single post + its comments                        |
| POST   | `/api/forums`                                     | student               | Create a post                                     |
| PUT    | `/api/forums/{id}`                                | student (own)         | Edit own post                                     |
| DELETE | `/api/forums/{id}`                                | student (own) + admin | Soft-delete if comments exist, otherwise hard-delete |
| POST   | `/api/forums/{id}/like`                           | any logged-in         | Toggle like/heart                                 |
| POST   | `/api/forums/{id}/comments`                       | student               | Add a comment                                     |
| DELETE | `/api/forums/{id}/comments/{comment_id}`          | student (own) + admin | Delete a comment                                  |

#### Labels / Tags (forum feature)

Labels are the tagging system used to categorise and filter forum posts. They are part of the Forum module, not a separate module.

| Method | Path                   | Auth                  | Description                              |
|--------|------------------------|-----------------------|------------------------------------------|
| GET    | `/api/labels`          | any logged-in         | List labels (with post count)            |
| POST   | `/api/labels`          | any logged-in         | Create a new label (name must be unique) |
| PUT    | `/api/labels/{id}`     | admin / superadmin    | Rename a label                           |
| DELETE | `/api/labels/{id}`     | admin / superadmin    | Delete (blocked if posts still reference it) |

### Interviews

| Method | Path                                       | Auth                | Description                                  |
|--------|--------------------------------------------|---------------------|----------------------------------------------|
| GET    | `/api/interviews/slots`                    | any logged-in       | List all slots with interviewer name         |
| POST   | `/api/interviews/slots`                    | admin / superadmin  | Create an availability slot (must be future) |
| DELETE | `/api/interviews/slots/{id}`               | admin / superadmin  | Delete an unbooked slot                       |
| GET    | `/api/interviews/mysessions`               | student             | Student's own bookings + slot info            |
| POST   | `/api/interviews/bookings`                 | student             | Book an open slot (409 if already booked)     |
| PUT    | `/api/interviews/bookings/{id}`            | student (own)       | Edit category or cancel a pending booking     |
| GET    | `/api/interviews/admin/manage`             | admin / superadmin  | View every booking                            |
| PUT    | `/api/interviews/admin/evaluate/{id}`      | admin / superadmin  | Submit score (0–100) + feedback               |

All responses follow the envelope:

```json
{ "success": true, "data": ..., "message": "..." }
```

Error responses additionally include `errors` for field-level validation failures.

---

## Project Structure

```
careerbridge/
├── README.md                 ← this file
├── database/
│   ├── schema.sql            ← run first (creates tables)
│   └── seed.sql              ← run second (demo data + verified bcrypt hashes)
├── backend/                  ← Slim 4 REST API
│   ├── public/index.php      ← entry point
│   └── src/
│       ├── Config/Database.php
│       ├── Middleware/{Cors,Jwt}Middleware.php
│       ├── Support/Json.php             ← uniform JSON response envelope
│       └── Modules/                     ← one folder per functional area
│           ├── Auth/AuthController.php           (M1 — Areeb)
│           ├── Jobs/JobController.php            (M2 — Samin)
│           ├── Applications/ApplicationController.php (M3 — Samin)
│           ├── Companies/CompanyController.php   (M4 — Mariam)
│           ├── Profile/ProfileController.php     (M5 — Mariam)
│           ├── Admin/AdminController.php          (M5 + M6 — Mariam/Areeb)
│           ├── Forums/ForumController.php        (M7 — Monika)
│           ├── Labels/LabelController.php        (M7 tags feature — Monika)
│           └── Interviews/InterviewController.php (M8 — Monika)
├── frontend/                 ← Vue 3 SPA
│   └── src/
│       ├── components/       ← NavBar, JobCard, StatusBadge, LoadingSpinner,
│       │                       ToastNotification, ConfirmDialog,
│       │                       forum/ (PostCard, CommentSection),
│       │                       interview/ (SlotCard, FeedbackModal)
│       ├── views/            ← 18 page components
│       ├── stores/auth.js    ← Pinia auth store
│       ├── router/index.js   ← role-based navigation guards
│       ├── services/api.js   ← Axios instance + interceptors
│       └── composables/      ← useToast.js, useForum.js, useInterview.js
└── .gitignore
```

---

## Security Notes

- Passwords hashed with `PASSWORD_BCRYPT` (cost 12). Plaintext is never stored.
- All protected routes go through `JwtMiddleware`, which verifies signature + expiry and enforces role.
- Every DB query in the backend uses **PDO prepared statements** — no string concatenation with user input.
- CORS is restricted to `http://localhost:5173` by default (configured in `Middleware/CorsMiddleware.php`). Add your deployed frontend origin there before going live. Set `CORS_ALLOW_ALL=1` in `.env` only for quick API testing.
- Detailed error messages are gated behind `APP_DEBUG=1` and hidden in production.
- The frontend Axios interceptor clears the token and redirects to `/login` on any 401.

---

## Common Issues

- **`composer install` fails with SSL errors** → run `composer config -g secure-http false` (development only) or update PHP's CA bundle.
- **`vite` proxy returns 404** → confirm the URL in `frontend/vite.config.js` matches where your PHP server actually serves `public/index.php`.
- **`mysql` rejects the seed because of foreign keys** → make sure you ran `schema.sql` *first* and that you're connecting to the same database where the schema was created.
- **Login fails with valid credentials** → `JWT_SECRET` must be the same value the API uses to sign tokens; if you change `.env`, restart the PHP server.

---

## License

MIT — academic project for SECJ3483.

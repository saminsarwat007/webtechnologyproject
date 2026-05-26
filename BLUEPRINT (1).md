# CareerBridge — Full Build Blueprint
> Read this entire file before writing a single line of code.
> Build every section in order. Do not skip anything.

---

## WHAT YOU ARE BUILDING

**CareerBridge** — a Student Internship & Job Application Management System.

Full-stack web application:
- **Frontend**: Vue.js 3 SPA (Single Page Application)
- **Backend**: PHP Slim 4 REST API
- **Database**: MySQL
- **Auth**: JWT (JSON Web Tokens)
- **Styling**: Tailwind CSS

### Who uses it:
| Role | What they do |
|---|---|
| Student | Browse job listings, apply, track application status, manage profile |
| Admin | Post job listings, review applications, update statuses, manage companies |
| Super Admin | Manage all users, view full analytics |

---

## SECTION 1 — FOLDER STRUCTURE

Create this exact structure before writing any code:

```
careerbridge/
├── BLUEPRINT.md
├── README.md
├── database/
│   ├── schema.sql
│   └── seed.sql
├── backend/
│   ├── .env.example
│   ├── .htaccess
│   ├── composer.json
│   └── public/
│       └── index.php
│   └── src/
│       ├── config/
│       │   └── database.php
│       ├── Middleware/
│       │   ├── JwtMiddleware.php
│       │   └── CorsMiddleware.php
│       └── Controllers/
│           ├── AuthController.php
│           ├── JobController.php
│           ├── ApplicationController.php
│           ├── ProfileController.php
│           ├── CompanyController.php
│           └── AdminController.php
├── frontend/
│   ├── index.html
│   ├── package.json
│   ├── vite.config.js
│   ├── tailwind.config.js
│   └── src/
│       ├── App.vue
│       ├── main.js
│       ├── assets/
│       ├── components/
│       │   ├── NavBar.vue
│       │   ├── JobCard.vue
│       │   ├── StatusBadge.vue
│       │   ├── LoadingSpinner.vue
│       │   ├── ToastNotification.vue
│       │   └── ConfirmDialog.vue
│       ├── views/
│       │   ├── LoginView.vue
│       │   ├── RegisterView.vue
│       │   ├── StudentDashboard.vue
│       │   ├── BrowseJobs.vue
│       │   ├── JobDetail.vue
│       │   ├── MyApplications.vue
│       │   ├── StudentProfile.vue
│       │   ├── AdminDashboard.vue
│       │   ├── ManageJobs.vue
│       │   ├── ManageApplications.vue
│       │   ├── ManageCompanies.vue
│       │   └── AdminUsers.vue
│       ├── router/
│       │   └── index.js
│       ├── stores/
│       │   └── auth.js
│       └── services/
│           └── api.js
└── git-schedule/
    ├── areeb/
    │   ├── week6.md
    │   ├── week7.md
    │   ├── week8.md
    │   ├── week9.md
    │   ├── week10.md
    │   ├── week11.md
    │   └── week12-14.md
    ├── samin/
    │   ├── week6.md
    │   ├── week7.md
    │   ├── week8.md
    │   ├── week9.md
    │   ├── week10.md
    │   ├── week11.md
    │   └── week12-14.md
    └── mariam/
        ├── week6.md
        ├── week7.md
        ├── week8.md
        ├── week9.md
        ├── week10.md
        ├── week11.md
        └── week12-14.md
```

---

## SECTION 2 — DATABASE

### database/schema.sql

```sql
CREATE DATABASE IF NOT EXISTS careerbridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE careerbridge;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin','superadmin') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE companies (
  company_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  industry VARCHAR(100) NOT NULL,
  location VARCHAR(150) NOT NULL,
  description TEXT,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE jobs (
  job_id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  posted_by INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  type ENUM('internship','fulltime','parttime') NOT NULL,
  description TEXT NOT NULL,
  requirements TEXT,
  deadline DATE NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (company_id) REFERENCES companies(company_id),
  FOREIGN KEY (posted_by) REFERENCES users(user_id)
);

CREATE TABLE student_profiles (
  profile_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  matric_no VARCHAR(20) NOT NULL UNIQUE,
  programme VARCHAR(100) NOT NULL,
  cgpa DECIMAL(3,2),
  skills TEXT,
  resume_text TEXT,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE applications (
  application_id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  user_id INT NOT NULL,
  cover_letter TEXT,
  status ENUM('pending','reviewed','accepted','rejected') NOT NULL DEFAULT 'pending',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (job_id) REFERENCES jobs(job_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  UNIQUE KEY unique_application (job_id, user_id)
);
```

### database/seed.sql

Insert all of the following realistic data:

**Users (4 records):**
- superadmin: name="Super Admin", email="superadmin@careerbridge.my", role=superadmin
- admin: name="Ahmad Farizal", email="farizal@careerbridge.my", role=admin
- student 1: name="Mohammad Areeb", email="areeb@student.utm.my", role=student, matric=A22EC4041
- student 2: name="Samin Sarwat", email="samin@student.utm.my", role=student, matric=A22EC4040

All passwords = "Password123!" hashed with PHP password_hash(). Pre-compute the hashes and insert as string literals.

**Companies (3 records):**
- TechCorp Sdn Bhd — industry: Software Development, location: Kuala Lumpur
- Axiata Digital — industry: Telecommunications, location: Kuala Lumpur
- Petronas ICT — industry: Oil & Gas Technology, location: Kuala Lumpur

**Jobs (10 records, mix of types):**
Realistic Malaysian tech internship/job listings such as:
- Software Engineering Intern at TechCorp (internship, deadline 3 months from now)
- Frontend Developer at Axiata (fulltime)
- Backend Engineer Intern at Petronas ICT (internship)
- Mobile App Developer at TechCorp (fulltime)
- Data Analyst Intern at Axiata (internship)
- DevOps Engineer at Petronas ICT (fulltime)
- UI/UX Designer Intern at TechCorp (internship)
- Cybersecurity Analyst at Axiata (fulltime)
- Database Administrator Intern at Petronas ICT (internship)
- Project Manager at TechCorp (fulltime)

**Student profiles (2 records):** Linked to the 2 student users. Realistic CGPA (3.2 to 3.8), skills, programme names.

**Applications (5 records):** Mix of statuses — pending, reviewed, accepted, rejected, pending.

---

## SECTION 3 — BACKEND (PHP Slim 4)

### backend/.env.example
```
DB_HOST=localhost
DB_NAME=careerbridge
DB_USER=root
DB_PASS=
JWT_SECRET=careerbridge-super-secret-jwt-key-2026
```

### backend/.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```

### backend/composer.json
```json
{
  "require": {
    "slim/slim": "^4.0",
    "slim/psr7": "^1.0",
    "firebase/php-jwt": "^6.0",
    "vlucas/phpdotenv": "^5.0"
  },
  "autoload": {
    "psr-4": { "App\\": "src/" }
  }
}
```

### backend/src/config/database.php

PDO connection using .env values. Singleton pattern. ERRMODE_EXCEPTION. FETCH_ASSOC. charset utf8mb4.

### backend/src/Middleware/CorsMiddleware.php

Allow: Origin *, Methods GET POST PUT DELETE OPTIONS, Headers Content-Type Authorization.
Handle OPTIONS preflight returning 200.

### backend/src/Middleware/JwtMiddleware.php

- Extract Bearer token from Authorization header
- Verify with firebase/php-jwt using JWT_SECRET from .env
- Decode payload, attach to request attribute "jwt_payload"
- Return 401 JSON if missing or invalid token
- Return 403 JSON if role check fails (accept optional $requiredRole param)

### backend/public/index.php

Register ALL routes below. Use Slim 4 App. Load .env. Register CorsMiddleware globally.

**AUTH ROUTES (no JWT):**
```
POST /api/auth/register
POST /api/auth/login
```

**JOB ROUTES:**
```
GET    /api/jobs              public
GET    /api/jobs/{id}         public
POST   /api/jobs              JWT required, role: admin or superadmin
PUT    /api/jobs/{id}         JWT required, role: admin or superadmin
DELETE /api/jobs/{id}         JWT required, role: admin or superadmin
```

**APPLICATION ROUTES:**
```
GET    /api/applications               JWT required (student sees own, admin sees all)
POST   /api/applications               JWT required, role: student
PUT    /api/applications/{id}/status   JWT required, role: admin or superadmin
DELETE /api/applications/{id}          JWT required, role: student (own only)
```

**PROFILE ROUTES:**
```
GET /api/profile    JWT required, role: student
PUT /api/profile    JWT required, role: student
```

**COMPANY ROUTES:**
```
GET  /api/companies        JWT required
POST /api/companies        JWT required, role: admin or superadmin
PUT  /api/companies/{id}   JWT required, role: admin or superadmin
```

**ADMIN ROUTES:**
```
GET /api/admin/users       JWT required, role: superadmin
GET /api/admin/analytics   JWT required, role: admin or superadmin
```

### AuthController.php

**POST /api/auth/register:**
- Validate: full_name required, email valid format and unique in DB, password min 8 chars, role must be student or admin
- Hash password: password_hash($password, PASSWORD_BCRYPT)
- Insert into users
- Generate JWT with payload: user_id, email, role, exp: now+86400
- Return 201 with token and user object

**POST /api/auth/login:**
- Validate: email and password required
- Fetch user by email
- password_verify() — return 401 if fails
- Generate JWT same as above
- Return 200 with token and user object

### JobController.php

**GET /api/jobs:**
- Join with companies table to get company name
- Support query params: ?search= (LIKE on title or company name), ?type= (exact match), ?company_id=
- Only return is_active=true jobs
- Return array of jobs with company_name field

**GET /api/jobs/{id}:**
- Join with companies and users (posted_by) tables
- Return full job detail or 404

**POST /api/jobs:**
- Validate: company_id, title, type, description, deadline all required
- type must be internship/fulltime/parttime
- deadline must be a future date
- Insert and return 201 with new job

**PUT /api/jobs/{id}:**
- Same validation as POST
- Return 404 if not found
- Return 200 with updated job

**DELETE /api/jobs/{id}:**
- Soft delete: UPDATE jobs SET is_active=false WHERE job_id=?
- Return 200 with message

### ApplicationController.php

**GET /api/applications:**
- If role=student: SELECT where user_id = jwt user_id, join with jobs and companies
- If role=admin/superadmin: SELECT all, join with users, jobs, companies
- Support ?status= filter
- Return array

**POST /api/applications:**
- Validate: job_id required
- Check job exists and is_active=true
- Check user has not already applied (UNIQUE constraint) — return 409 if duplicate
- Insert application
- Return 201

**PUT /api/applications/{id}/status:**
- Validate status is one of: pending, reviewed, accepted, rejected
- Update and return 200

**DELETE /api/applications/{id}:**
- Check application belongs to jwt user_id — return 403 if not
- Check status=pending — return 400 if not pending
- Delete and return 200

### ProfileController.php

**GET /api/profile:**
- SELECT from student_profiles where user_id = jwt user_id
- Also join users table for full_name and email
- Return 404 if profile not set up yet (student registered but hasn't filled profile)

**PUT /api/profile:**
- Validate: matric_no, programme required, cgpa between 0.00 and 4.00 if provided
- UPSERT: INSERT ... ON DUPLICATE KEY UPDATE
- Return 200 with updated profile

### CompanyController.php

**GET /api/companies:** Return all companies.
**POST /api/companies:** Validate name, industry, location required. Insert. Return 201.
**PUT /api/companies/{id}:** Same validation. Return 200. Return 404 if not found.

### AdminController.php

**GET /api/admin/users:** Return all users (exclude password_hash from response).

**GET /api/admin/analytics:**
Return JSON:
```json
{
  "total_jobs": 10,
  "active_jobs": 8,
  "total_applications": 25,
  "pending_count": 10,
  "reviewed_count": 5,
  "accepted_count": 7,
  "rejected_count": 3,
  "applications_this_week": 6
}
```
Use COUNT queries with WHERE clauses and WEEK() for this_week.

### ERROR HANDLING — ALL CONTROLLERS

- 200 OK for successful GET/PUT/DELETE
- 201 Created for successful POST
- 400 Bad Request for validation errors (return field-level error messages)
- 401 Unauthorized for missing/invalid JWT
- 403 Forbidden for wrong role or accessing another user's resource
- 404 Not Found for missing records
- 409 Conflict for duplicate application
- 500 Server Error for unexpected DB errors (catch PDOException)

All responses in JSON format: {"success": true/false, "data": ..., "message": "..."}

---

## SECTION 4 — FRONTEND (Vue.js 3)

### frontend/package.json
Dependencies: vue@3, vue-router@4, pinia, axios, chart.js
DevDependencies: vite, @vitejs/plugin-vue, tailwindcss, autoprefixer, postcss

### frontend/vite.config.js
Vue plugin. Server proxy: /api → http://localhost/careerbridge/backend/public

### frontend/tailwind.config.js
Content: ./index.html, ./src/**/*.{vue,js}

### frontend/src/main.js
Create Vue app. Use Pinia. Use Router. Mount to #app.
On app creation: call authStore.initFromStorage() to restore session from localStorage.

---

### frontend/src/services/api.js

Axios instance:
- baseURL: '/api'
- Request interceptor: get token from localStorage, attach as Authorization: Bearer {token}
- Response interceptor: if response status 401 → clear localStorage → redirect to /login

---

### frontend/src/stores/auth.js (Pinia)

```javascript
State: {
  token: null,
  user: null   // { user_id, email, role, full_name }
}

Getters: {
  isAuthenticated: !!state.token,
  isAdmin: state.user?.role === 'admin' || state.user?.role === 'superadmin',
  isStudent: state.user?.role === 'student',
  isSuperAdmin: state.user?.role === 'superadmin'
}

Actions: {
  login(email, password): POST /api/auth/login → store token in state + localStorage → decode JWT payload using atob() to get user info,
  logout(): clear state + localStorage → router.push('/login'),
  initFromStorage(): read token from localStorage → decode → restore state
}
```

Decode JWT without external library:
```javascript
function decodeToken(token) {
  const payload = token.split('.')[1]
  return JSON.parse(atob(payload))
}
```

---

### frontend/src/router/index.js

Routes:
```
/                    → redirect to /student/dashboard or /admin/dashboard based on role
/login               → LoginView (public)
/register            → RegisterView (public)
/student/dashboard   → StudentDashboard (student only)
/student/jobs        → BrowseJobs (student only)
/student/jobs/:id    → JobDetail (student only)
/student/applications→ MyApplications (student only)
/student/profile     → StudentProfile (student only)
/admin/dashboard     → AdminDashboard (admin/superadmin only)
/admin/jobs          → ManageJobs (admin/superadmin only)
/admin/applications  → ManageApplications (admin/superadmin only)
/admin/companies     → ManageCompanies (admin/superadmin only)
/admin/users         → AdminUsers (superadmin only)
```

Navigation guard (beforeEach):
- If route requires auth and no token → redirect to /login
- If route requires role and role doesn't match → redirect to their dashboard
- If logged in user visits /login or /register → redirect to their dashboard

---

### frontend/src/components/NavBar.vue

- Show logo "CareerBridge" on left
- Student nav: Dashboard | Browse Jobs | My Applications | Profile | Logout
- Admin nav: Dashboard | Manage Jobs | Applications | Companies | Users(superadmin only) | Logout
- Mobile: hamburger menu toggling a dropdown
- Active link highlighted using router-link-active class
- Logout calls authStore.logout()

### frontend/src/components/JobCard.vue

Props: job object
Display: title, company_name, type badge (color coded), deadline, short description (truncated to 100 chars)
Emit: @apply clicked, @view-detail clicked
Type badge colors: internship=blue, fulltime=green, parttime=orange

### frontend/src/components/StatusBadge.vue

Prop: status string
Returns: colored span
- pending: yellow background
- reviewed: blue background
- accepted: green background
- rejected: red background

### frontend/src/components/LoadingSpinner.vue

Centered spinning circle using Tailwind animate-spin. Show when prop :loading=true.

### frontend/src/components/ToastNotification.vue

Props: message, type (success/error/info)
Auto-dismiss after 3 seconds using setTimeout
Slide in from top-right using CSS transition
Green for success, red for error, blue for info

### frontend/src/components/ConfirmDialog.vue

Props: message, @confirm, @cancel
Modal overlay with message + Confirm button + Cancel button

---

### LoginView.vue

- Email input + password input
- Validate: both required, email format, password min 8 chars
- Show inline error under each field
- On submit: call authStore.login() → redirect based on role
- Show API error (wrong credentials) as form-level error
- Link to /register

### RegisterView.vue

- Full name, email, password, confirm password, role selector (student/admin)
- Validate all fields including: passwords match, min 8 chars
- POST /api/auth/register → auto-login → redirect
- Link to /login

---

### StudentDashboard.vue

On mount fetch in parallel (Promise.all):
- GET /api/applications → count by status
- GET /api/jobs → count active

Display:
- 4 stat cards: Total Applied, Pending, Accepted, Rejected
- Table of 5 most recent applications: Job Title | Company | Applied Date | StatusBadge | Action
- LoadingSpinner while fetching
- Quick action button: "Browse Jobs" → router.push('/student/jobs')

### BrowseJobs.vue

On mount: GET /api/jobs → store in jobs array

Features:
- Search bar: debounced 400ms using setTimeout/clearTimeout, filters locally by title or company_name
- Type dropdown: All / Internship / Full-time / Part-time — filters locally
- Display as grid of JobCard components
- "Apply" button on card → show inline apply modal (cover letter textarea + Submit)
- On apply: POST /api/applications, show ToastNotification success/error
- After successful apply, disable that card's apply button and show "Applied"
- Empty state: "No jobs found" message with icon if filtered list is empty
- LoadingSpinner while fetching

### JobDetail.vue

Route param: :id
On mount: GET /api/jobs/{id}
Display full job details: title, company, type badge, deadline, full description, requirements
Apply button → same apply modal as BrowseJobs
Show "Already Applied" if user has applied (check GET /api/applications)

### MyApplications.vue

On mount: GET /api/applications
Table: Job Title | Company Name | Applied Date | StatusBadge | Actions
Actions: "Withdraw" button for pending only → ConfirmDialog → DELETE /api/applications/{id}
Filter tabs: All | Pending | Reviewed | Accepted | Rejected (filter locally)
Empty state message
LoadingSpinner

### StudentProfile.vue

On mount: GET /api/profile (pre-fill form, handle 404 gracefully with empty form)
Fields: Matric No, Programme, CGPA (0.00–4.00), Skills (comma-separated tags), Resume Summary
Validate CGPA range client-side
PUT /api/profile on save → show ToastNotification

---

### AdminDashboard.vue

On mount: GET /api/admin/analytics

Display:
- 5 metric cards: Active Listings, Total Applications, Pending, Accepted, Rejected
- Bar chart using Chart.js showing: Pending / Reviewed / Accepted / Rejected counts
- Quick action buttons: "Post New Job" → router.push('/admin/jobs'), "View Applications"
- LoadingSpinner

### ManageJobs.vue

On mount: GET /api/jobs (admin sees all including inactive)
Table: Title | Company | Type | Deadline | Status (Active badge / Inactive) | Edit | Delete

"Add Job" button → modal form:
Fields: Title, Company (dropdown from GET /api/companies), Type, Description, Requirements, Deadline
Validate all required fields
POST /api/jobs → refresh list → close modal → ToastNotification

Edit button → same modal pre-filled → PUT /api/jobs/{id}
Delete button → ConfirmDialog → DELETE /api/jobs/{id} (soft delete)
All operations update list reactively without full page reload

### ManageApplications.vue

On mount: GET /api/applications (admin gets all)
Filter tabs: All | Pending | Reviewed | Accepted | Rejected
Table: Applicant Name | Job Title | Company | Applied Date | Status dropdown | —
Status dropdown per row: select new status → immediately PUT /api/applications/{id}/status → show ToastNotification
Optimistic UI: update status in local array before API confirms

### ManageCompanies.vue

Full CRUD for companies.
List as table: Name | Industry | Location | Edit | Delete
"Add Company" button → modal form
Edit → modal pre-filled
Delete → ConfirmDialog → DELETE (implement delete in CompanyController)

### AdminUsers.vue

On mount: GET /api/admin/users
Table: ID | Full Name | Email | Role badge | Joined Date
Role badges: student=gray, admin=blue, superadmin=purple
Read-only — no delete or edit actions (safety measure)
Search bar to filter locally by name or email

---

## SECTION 5 — UI/UX RULES (Apply everywhere)

- Tailwind CSS only for all styling
- Fully mobile responsive (sm: md: lg: breakpoints)
- Every async operation shows LoadingSpinner
- Every success/error shows ToastNotification
- Every destructive action requires ConfirmDialog
- All forms validate client-side before API call
- Show inline error messages under each invalid field (text-red-500 text-sm)
- Empty states: show a message + icon when lists are empty, never a blank page
- Consistent colour scheme: primary blue (#3B82F6), success green (#10B981), danger red (#EF4444)
- Page transitions: use Vue <Transition name="fade"> with opacity CSS

---

## SECTION 6 — SECURITY RULES (Mandatory)

- JWT_SECRET stored in .env only, never hardcoded
- Every protected route checks JWT in middleware before controller runs
- Role check inside middleware: admin routes reject students with 403
- All DB queries use PDO prepared statements — no string concatenation with user input
- Backend validates ALL input independently of frontend
- Passwords hashed with PASSWORD_BCRYPT, never stored plain
- CORS restricted to localhost:5173 in development

---

## SECTION 7 — README.md

Generate a complete README with:
- Project name and description
- Team: Mohammad Areeb (A22EC4041), Samin Sarwat (A22EC4040), Mariam Hanif (A22EC4034)
- Course: SECJ3483 Web Technology, Academic Session 2025/2026
- Tech stack table
- Prerequisites
- Step-by-step setup (clone, composer install, npm install, .env setup, DB import, run servers)
- Default login credentials (from seed data)
- Full API endpoint reference table with methods, paths, auth requirements

---

## SECTION 8 — GIT SCHEDULE FILES

Generate ALL files below. Every file must contain:
1. Real, complete file content (not placeholders)
2. Exact copy-paste terminal commands
3. Commits spread across Mon / Wed / Fri of that week

The 3 members own these modules:
- **Areeb** → backend: auth, jobs API, DB setup, deployment config
- **Samin** → frontend: Vue setup, student views (profile/applications/jobs), Axios, Pinia
- **Mariam** → frontend: components, admin views, dashboard, NavBar, README

### FORMAT FOR EVERY WEEK FILE:

```markdown
# [Name] — Week [N] ([Date range])
# Branch: feature/[branch-name]

## Day 1 — [Weekday Date]

### Files to create/edit:
**`path/to/file.ext`**
[FULL FILE CONTENT — real working code, not placeholder]

### Commands (copy-paste exactly):
git checkout -b feature/branch-name   ← only on first day of this branch
git add path/to/file.ext
git commit -m "type(scope): description"
git push origin feature/branch-name

---

## Day 2 — [Weekday Date]
...

## Day 3 — [Weekday Date]
...
[Last day of week: add note to open PR on GitHub: feature/branch → main]
```

---

### AREEB — Week 6 (4–10 May): Branch feature/project-setup
Day 1 Mon 4 May: backend/composer.json + backend/src/config/database.php
Day 2 Wed 6 May: database/schema.sql
Day 3 Fri 8 May: backend/.env.example + backend/.htaccess

### AREEB — Week 7 (11–17 May): Branch feature/auth-module
Day 1 Mon 11 May: backend/public/index.php (app bootstrap, CORS, route stubs)
Day 2 Wed 13 May: backend/src/Middleware/JwtMiddleware.php + CorsMiddleware.php
Day 3 Fri 15 May: backend/src/Controllers/AuthController.php (full register + login)

### AREEB — Week 8 (18–24 May): Branch feature/job-listings
Day 1 Mon 18 May: JobController.php GET /api/jobs + GET /api/jobs/{id}
Day 2 Wed 20 May: JobController.php POST /api/jobs + PUT /api/jobs/{id}
Day 3 Fri 22 May: JobController.php DELETE /api/jobs/{id} (soft delete)

### AREEB — Week 9 (25–31 May): Branch feature/seed-and-companies
Day 1 Mon 25 May: database/seed.sql (all seed data)
Day 2 Wed 27 May: CompanyController.php (GET + POST + PUT)
Day 3 Fri 29 May: Register company routes in index.php + test all routes

### AREEB — Week 10 (1–7 Jun): Branch feature/admin-api
Day 1 Mon 1 Jun: AdminController.php analytics endpoint
Day 2 Wed 3 Jun: AdminController.php users endpoint + role middleware checks
Day 3 Fri 5 Jun: Fix CORS headers + add proper error handling to all controllers

### AREEB — Week 11 (8–14 Jun): Branch feature/security-hardening
Day 1 Mon 8 Jun: Audit all controllers for PDO prepared statements
Day 2 Wed 10 Jun: Add input length limits + XSS-safe JSON output
Day 3 Fri 12 Jun: ApplicationController.php full implementation

### AREEB — Week 12–14 (15–21 Jun): Branch feature/deployment
Day 1 Mon 15 Jun: backend/.htaccess production config + .env.example final
Day 2 Wed 17 Jun: README.md backend setup section
Day 3 Fri 19 Jun: Final bug fixes from integration testing

---

### SAMIN — Week 6 (4–10 May): Branch feature/vue-setup
Day 1 Mon 4 May: frontend/package.json + frontend/vite.config.js
Day 2 Wed 6 May: frontend/tailwind.config.js + frontend/src/main.js + frontend/index.html
Day 3 Fri 8 May: frontend/src/services/api.js (Axios instance + interceptors)

### SAMIN — Week 7 (11–17 May): Branch feature/auth-frontend
Day 1 Mon 11 May: frontend/src/stores/auth.js (Pinia store full implementation)
Day 2 Wed 13 May: frontend/src/views/LoginView.vue (form + validation)
Day 3 Fri 15 May: frontend/src/router/index.js (all routes + navigation guards)

### SAMIN — Week 8 (18–24 May): Branch feature/student-profile
Day 1 Mon 18 May: frontend/src/views/RegisterView.vue
Day 2 Wed 20 May: frontend/src/views/StudentProfile.vue
Day 3 Fri 22 May: frontend/src/views/StudentDashboard.vue (stat cards + recent applications)

### SAMIN — Week 9 (25–31 May): Branch feature/browse-jobs
Day 1 Mon 25 May: frontend/src/views/BrowseJobs.vue (fetch + display grid)
Day 2 Wed 27 May: BrowseJobs.vue search debounce + filter dropdown
Day 3 Fri 29 May: BrowseJobs.vue apply modal + POST /api/applications + toast

### SAMIN — Week 10 (1–7 Jun): Branch feature/applications-view
Day 1 Mon 1 Jun: frontend/src/views/MyApplications.vue (table + status badges)
Day 2 Wed 3 Jun: MyApplications.vue withdraw flow + ConfirmDialog integration
Day 3 Fri 5 Jun: frontend/src/views/JobDetail.vue full page

### SAMIN — Week 11 (8–14 Jun): Branch feature/frontend-integration
Day 1 Mon 8 Jun: Axios 401 interceptor → auto logout + redirect
Day 2 Wed 10 Jun: Client-side validation helpers shared across all forms
Day 3 Fri 12 Jun: LoadingSpinner integration on all student views

### SAMIN — Week 12–14 (15–21 Jun): Branch feature/polish-student
Day 1 Mon 15 Jun: Empty state components for all student list pages
Day 2 Wed 17 Jun: Page transition animations (Vue Transition fade)
Day 3 Fri 19 Jun: Final mobile responsiveness fixes on student views

---

### MARIAM — Week 6 (4–10 May): Branch feature/base-components
Day 1 Mon 4 May: frontend/src/App.vue + frontend/src/components/NavBar.vue
Day 2 Wed 6 May: frontend/src/components/JobCard.vue + StatusBadge.vue
Day 3 Fri 8 May: frontend/src/components/LoadingSpinner.vue + ToastNotification.vue + ConfirmDialog.vue

### MARIAM — Week 7 (11–17 May): Branch feature/admin-dashboard
Day 1 Mon 11 May: frontend/src/views/AdminDashboard.vue (metric cards layout)
Day 2 Wed 13 May: AdminDashboard.vue Chart.js bar chart integration
Day 3 Fri 15 May: AdminDashboard.vue fetch analytics + loading state

### MARIAM — Week 8 (18–24 May): Branch feature/manage-jobs
Day 1 Mon 18 May: frontend/src/views/ManageJobs.vue (table + fetch)
Day 2 Wed 20 May: ManageJobs.vue add/edit modal with form validation
Day 3 Fri 22 May: ManageJobs.vue delete with ConfirmDialog + toast + reactive list update

### MARIAM — Week 9 (25–31 May): Branch feature/manage-applications
Day 1 Mon 25 May: frontend/src/views/ManageApplications.vue (table + filter tabs)
Day 2 Wed 27 May: ManageApplications.vue status dropdown per row + optimistic update
Day 3 Fri 29 May: frontend/src/views/ManageCompanies.vue full CRUD

### MARIAM — Week 10 (1–7 Jun): Branch feature/admin-users
Day 1 Mon 1 Jun: frontend/src/views/AdminUsers.vue (table + role badges)
Day 2 Wed 3 Jun: NavBar.vue mobile hamburger menu + responsive layout
Day 3 Fri 5 Jun: Global Tailwind colour theme consistency pass across all admin views

### MARIAM — Week 11 (8–14 Jun): Branch feature/frontend-polish
Day 1 Mon 8 Jun: Toast notification auto-dismiss + slide-in animation
Day 2 Wed 10 Jun: ConfirmDialog keyboard accessibility (Escape to cancel)
Day 3 Fri 12 Jun: Mobile responsiveness audit and fixes on all admin views

### MARIAM — Week 12–14 (15–21 Jun): Branch feature/docs-and-final
Day 1 Mon 15 Jun: README.md complete (team info, setup, API reference)
Day 2 Wed 17 Jun: Final UI polish — spacing, colour consistency, font sizes
Day 3 Fri 19 Jun: Final integration test fixes + presentation slide notes

---

## SECTION 9 — GIT SCHEDULE SUMMARY TABLE

After generating all git-schedule files, print this summary:

| Member | Wk6 | Wk7 | Wk8 | Wk9 | Wk10 | Wk11 | Wk12-14 | Total |
|--------|-----|-----|-----|-----|------|------|---------|-------|
| Areeb  | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| Samin  | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| Mariam | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| **Total** | **9** | **9** | **9** | **9** | **9** | **9** | **9** | **63** |

Each number = commits that week. All branches merged to main via PR at end of each week.

---

## SECTION 10 — FINAL CHECKS

After building everything, verify:

**Backend checks:**
- [ ] All 14 API endpoints return correct HTTP status codes
- [ ] JWT middleware blocks unauthorized access
- [ ] Role check returns 403 for wrong role
- [ ] Duplicate application returns 409
- [ ] All queries use prepared statements (no raw string concat)
- [ ] .env.example has all required keys

**Frontend checks:**
- [ ] Navigation guard redirects unauthenticated users to /login
- [ ] Navigation guard redirects wrong-role users to their dashboard
- [ ] Axios 401 interceptor clears auth and redirects to /login
- [ ] All forms show inline validation before submitting
- [ ] All async operations show LoadingSpinner
- [ ] All success/error operations show ToastNotification
- [ ] BrowseJobs search debounce works (400ms delay)
- [ ] Apply button disables after successful application

**Git schedule checks:**
- [ ] All 21 week files generated (7 weeks × 3 members)
- [ ] Every file has real code not placeholder comments
- [ ] Every file has exact copy-paste commands
- [ ] Commands include correct branch names
- [ ] Last day of each week includes PR note

Report any issues found during the final check.

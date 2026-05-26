# Git Schedule — Commit Plan Summary

Each cell counts the commits planned for that member that week. Every branch is opened with `git checkout -b feature/...` on Day 1 and merged to `main` via Pull Request on Day 3.

The schedule is organised so **each member personally implements full CRUD (Create, Read, Update, Delete) for the modules they own**, end-to-end across backend and frontend.

**Repository:** <https://github.com/azizah-utm/group-project-champion>

## Module ownership

| #  | Module                                    | Owner   | Backend CRUD source                                      | Primary frontend views                                                |
|----|-------------------------------------------|---------|----------------------------------------------------------|------------------------------------------------------------------------|
| M1 | User Authentication & Access Control      | Areeb   | `Modules/Auth/AuthController.php` + JWT/CORS middleware  | `views/auth/Login.vue`, `views/auth/Register.vue`, auth store + guards |
| M2 | Job & Internship Management               | Samin   | `Modules/Jobs/JobController.php` (full CRUD)             | `views/student/BrowseJobs.vue`, `JobDetail.vue`, `admin/ManageJobs.vue` |
| M3 | Application Tracking System               | Samin   | `Modules/Applications/ApplicationController.php` (full CRUD) | `views/student/MyApplications.vue`, `admin/ManageApplications.vue`, `ApplyModal.vue` |
| M4 | Company & External Source Management      | Mariam  | `Modules/Companies/CompanyController.php` (full CRUD)    | `views/admin/ManageCompanies.vue`                                     |
| M5 | Reporting & Analytics                     | Mariam  | `Modules/Admin/AdminController::analytics` + `Modules/Profile/ProfileController.php` | `views/admin/AdminDashboard.vue` (Chart.js), `student/StudentDashboard.vue`, `student/Profile.vue` |
| M6 | System Administration & Notifications     | Areeb   | `Modules/Admin/AdminController::users` + global error handler | `views/admin/AdminUsers.vue`, `ToastNotification.vue`, `ConfirmDialog.vue` |
| M7 | Forum & Discussion                        | Monika  | `Modules/Forums/ForumController.php` — posts CRUD with free-text `tag`, comments add+flat-delete, atomic like toggle, cascade delete | `views/ForumList.vue`, `views/ForumPost.vue`                          |
| M8 | Mock Interview & Technical Prep Scheduler | Monika  | `Modules/Interviews/InterviewController.php` — slot CRUD (admin), bookings (student) with row-locking, admin evaluation (score + feedback) | `views/Interviews.vue` (student), `views/ManageInterviews.vue` (admin) |

## Commit count per member per week

| Member | Wk6 | Wk7 | Wk8 | Wk9 | Wk10 | Wk11 | Wk12-14 | Total |
|--------|-----|-----|-----|-----|------|------|---------|-------|
| Areeb  | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| Samin  | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| Mariam | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| Monika | 3   | 3   | 3   | 3   | 3    | 3    | 3       | 21    |
| **Total** | **12** | **12** | **12** | **12** | **12** | **12** | **12** | **84** |

## Branch index

| Week  | Areeb (M1 + M6)              | Samin (M2 + M3)               | Mariam (M4 + M5)              | Monika (M7 + M8)                       |
|-------|------------------------------|-------------------------------|-------------------------------|----------------------------------------|
| 6     | feature/project-setup        | feature/vue-setup             | feature/base-components       | feature/m7-forum-schema-and-api         |
| 7     | feature/auth-module          | feature/jobs-api              | feature/companies-api         | feature/m7-posts-crud                   |
| 8     | feature/auth-frontend        | feature/jobs-frontend         | feature/companies-frontend    | feature/m7-comments-likes                |
| 9     | feature/admin-users          | feature/applications-api      | feature/analytics-api         | feature/m7-frontend                     |
| 10    | feature/notifications        | feature/applications-frontend | feature/analytics-frontend    | feature/m8-interviews-schema-and-slots  |
| 11    | feature/security-hardening   | feature/jobs-apps-integration | feature/reporting-polish      | feature/m8-bookings-and-evaluation      |
| 12-14 | feature/deployment           | feature/polish-jobs-apps      | feature/docs-and-final        | feature/m7-m8-polish-and-tests          |

## CRUD coverage per member

- **Areeb (M1 + M6):** Create + Read on `users` (register/login), Read on `users` (admin directory). Authorization-cross-cutting.
- **Samin (M2 + M3):** Full CRUD on `jobs` (M2) **and** full CRUD on `applications` (M3) — 8 distinct endpoints.
- **Mariam (M4 + M5):** Full CRUD on `companies` (M4), aggregate Read on `jobs`/`applications` for analytics, plus Create+Update on `student_profiles` via `INSERT … ON DUPLICATE KEY UPDATE`.
- **Monika (M7 + M8):** Full CRUD on `posts` (M7, with cascade delete) **plus** Create+Delete on `comments` (flat path), atomic Create-or-Delete on `post_likes` with computed `COUNT(*)`. For M8: full CRUD on `interview_slots`, plus Create / Update / cancel on `mock_interviews` with row-locking, and admin evaluation (score 0–100 + feedback). 13 distinct endpoints across M7/M8, plus a full Playwright E2E suite covering all 8 modules (36 tests).

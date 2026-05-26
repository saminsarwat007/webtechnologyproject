import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

import LoginView          from '../views/LoginView.vue'
import RegisterView       from '../views/RegisterView.vue'
import StudentDashboard   from '../views/StudentDashboard.vue'
import BrowseJobs         from '../views/BrowseJobs.vue'
import JobDetail          from '../views/JobDetail.vue'
import MyApplications     from '../views/MyApplications.vue'
import StudentProfile     from '../views/StudentProfile.vue'
import AdminDashboard     from '../views/AdminDashboard.vue'
import ManageJobs         from '../views/ManageJobs.vue'
import ManageApplications from '../views/ManageApplications.vue'
import ManageCompanies    from '../views/ManageCompanies.vue'
import AdminUsers         from '../views/AdminUsers.vue'
import ForumList          from '../views/ForumList.vue'
import ForumPost          from '../views/ForumPost.vue'
import Interviews         from '../views/Interviews.vue'
import ManageInterviews   from '../views/ManageInterviews.vue'

const routes = [
  { path: '/',          redirect: () => roleHome() },
  { path: '/login',     name: 'login',    component: LoginView,    meta: { public: true } },
  { path: '/register',  name: 'register', component: RegisterView, meta: { public: true } },

  // ---- Student ---------------------------------------------------------
  { path: '/student/dashboard',     name: 'student-dashboard',    component: StudentDashboard, meta: { roles: ['student'] } },
  { path: '/student/jobs',          name: 'student-jobs',         component: BrowseJobs,       meta: { roles: ['student'] } },
  { path: '/student/jobs/:id',      name: 'student-job-detail',   component: JobDetail,        meta: { roles: ['student'] }, props: true },
  { path: '/student/applications',  name: 'student-applications', component: MyApplications,   meta: { roles: ['student'] } },
  { path: '/student/profile',       name: 'student-profile',      component: StudentProfile,   meta: { roles: ['student'] } },

  // ---- Admin / Superadmin ---------------------------------------------
  { path: '/admin/dashboard',     name: 'admin-dashboard',     component: AdminDashboard,     meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/jobs',          name: 'admin-jobs',          component: ManageJobs,         meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/applications',  name: 'admin-applications',  component: ManageApplications, meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/companies',     name: 'admin-companies',     component: ManageCompanies,    meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/interviews',    name: 'admin-interviews',    component: ManageInterviews,   meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/users',         name: 'admin-users',         component: AdminUsers,         meta: { roles: ['superadmin'] } },

  // ---- Forum (M7 — Monika) — any logged-in user --------------------
  { path: '/forum',         name: 'forum',        component: ForumList, meta: { roles: ['student', 'admin', 'superadmin'] } },
  { path: '/forum/:id',     name: 'forum-post',   component: ForumPost, meta: { roles: ['student', 'admin', 'superadmin'] }, props: true },

  // ---- Interviews (M8 — Monika) — student-facing booking + history -----
  { path: '/interviews',    name: 'interviews',   component: Interviews, meta: { roles: ['student'] } },

  { path: '/:pathMatch(.*)*', redirect: () => roleHome() }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

/**
 * Pick a default landing route based on the logged-in user's role.
 * Falls back to /login when nobody is signed in.
 */
function roleHome () {
  const auth = useAuthStore()
  if (!auth.isAuthenticated) return '/login'
  if (auth.isAdmin)          return '/admin/dashboard'
  return '/student/dashboard'
}

router.beforeEach((to) => {
  const auth = useAuthStore()

  // Public-only pages: redirect already-logged-in users to their dashboard
  if (to.meta.public) {
    if (auth.isAuthenticated) {
      return roleHome()
    }
    return true
  }

  // Protected pages: must be logged in
  if (!auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  // Role gating
  if (Array.isArray(to.meta.roles) && to.meta.roles.length > 0) {
    if (!to.meta.roles.includes(auth.user?.role)) {
      return roleHome()
    }
  }

  return true
})

export default router

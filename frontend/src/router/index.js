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
import ManageLabels       from '../views/ManageLabels.vue'

// M7 — Forum & Discussion
import ForumDashboardView from '../views/ForumDashboardView.vue'
import ForumDetailView    from '../views/ForumDetailView.vue'

// M8 — Mock Interview
import InterviewSlotsView          from '../views/InterviewSlotsView.vue'
import MockInterviewDashboardView  from '../views/MockInterviewDashboardView.vue'
import BookInterviewView           from '../views/BookInterviewView.vue'

const routes = [
  { path: '/', redirect: () => roleHome() },
  { path: '/login',    name: 'login',    component: LoginView,    meta: { public: true } },
  { path: '/register', name: 'register', component: RegisterView, meta: { public: true } },

  // ---- Student -------------------------------------------------------
  { path: '/student/dashboard',     name: 'student-dashboard',    component: StudentDashboard, meta: { roles: ['student'] } },
  { path: '/student/jobs',          name: 'student-jobs',         component: BrowseJobs,       meta: { roles: ['student'] } },
  { path: '/student/jobs/:id',      name: 'student-job-detail',   component: JobDetail,        meta: { roles: ['student'] }, props: true },
  { path: '/student/applications',  name: 'student-applications', component: MyApplications,   meta: { roles: ['student'] } },
  { path: '/student/profile',       name: 'student-profile',      component: StudentProfile,   meta: { roles: ['student'] } },

  // ---- Admin / Superadmin --------------------------------------------
  { path: '/admin/dashboard',    name: 'admin-dashboard',    component: AdminDashboard,     meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/jobs',         name: 'admin-jobs',         component: ManageJobs,         meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/applications', name: 'admin-applications', component: ManageApplications, meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/companies',    name: 'admin-companies',    component: ManageCompanies,    meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/labels',       name: 'admin-labels',       component: ManageLabels,       meta: { roles: ['admin', 'superadmin'] } },
  { path: '/admin/users',        name: 'admin-users',        component: AdminUsers,         meta: { roles: ['superadmin'] } },

  // ---- M7: Forum (any logged-in user) --------------------------------
  { path: '/forum',     name: 'forum',      component: ForumDashboardView, meta: { roles: ['student', 'admin', 'superadmin'] } },
  { path: '/forum/:id', name: 'forum-post', component: ForumDetailView,    meta: { roles: ['student', 'admin', 'superadmin'] }, props: true },

  // ---- M8: Mock Interview (any logged-in user) -----------------------
  { path: '/interview/slots',          name: 'interview-slots',     component: InterviewSlotsView,         meta: { roles: ['student', 'admin', 'superadmin'] } },
  { path: '/interview/dashboard',      name: 'interview-dashboard', component: MockInterviewDashboardView, meta: { roles: ['student', 'admin', 'superadmin'] } },
  { path: '/interview/book/:slotId',   name: 'interview-book',      component: BookInterviewView,          meta: { roles: ['student'] }, props: true },

  { path: '/:pathMatch(.*)*', redirect: () => roleHome() }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

function roleHome () {
  const auth = useAuthStore()
  if (!auth.isAuthenticated) return '/login'
  if (auth.isAdmin)          return '/admin/dashboard'
  return '/student/dashboard'
}

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    if (auth.isAuthenticated) return roleHome()
    return true
  }

  if (!auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (Array.isArray(to.meta.roles) && to.meta.roles.length > 0) {
    if (!to.meta.roles.includes(auth.user?.role)) return roleHome()
  }

  return true
})

export default router

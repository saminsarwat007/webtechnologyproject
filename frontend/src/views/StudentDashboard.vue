<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api.js'
import { useAuthStore } from '../stores/auth.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import StatusBadge from '../components/StatusBadge.vue'

const auth = useAuthStore()
const router = useRouter()

const loading      = ref(true)
const applications = ref([])
const activeJobs   = ref(0)

onMounted(async () => {
  loading.value = true
  try {
    const [appsRes, jobsRes] = await Promise.all([
      api.get('/applications'),
      api.get('/jobs')
    ])
    applications.value = appsRes.data?.data ?? []
    activeJobs.value   = (jobsRes.data?.data ?? []).length
  } catch (_e) {
    applications.value = []
    activeJobs.value = 0
  } finally {
    loading.value = false
  }
})

const stats = computed(() => ({
  total:    applications.value.length,
  pending:  applications.value.filter(a => a.status === 'pending').length,
  accepted: applications.value.filter(a => a.status === 'accepted').length,
  rejected: applications.value.filter(a => a.status === 'rejected').length
}))

const recent = computed(() =>
  [...applications.value]
    .sort((a, b) => new Date(b.applied_at) - new Date(a.applied_at))
    .slice(0, 5)
)

function formatDate (d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <p class="text-sm text-slate-500">Welcome back,</p>
        <h1 class="text-2xl font-semibold text-slate-900">{{ auth.user?.full_name }}</h1>
      </div>
      <button class="btn-primary self-start sm:self-auto" @click="router.push('/student/jobs')">
        Browse Jobs ({{ activeJobs }})
      </button>
    </header>

    <LoadingSpinner v-if="loading" />

    <div v-else>
      <!-- Stat cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
          <p class="text-sm text-slate-500">Total Applied</p>
          <p class="text-3xl font-semibold text-slate-900 mt-1">{{ stats.total }}</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Pending</p>
          <p class="text-3xl font-semibold text-amber-600 mt-1">{{ stats.pending }}</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Accepted</p>
          <p class="text-3xl font-semibold text-emerald-600 mt-1">{{ stats.accepted }}</p>
        </div>
        <div class="card p-5">
          <p class="text-sm text-slate-500">Rejected</p>
          <p class="text-3xl font-semibold text-red-600 mt-1">{{ stats.rejected }}</p>
        </div>
      </div>

      <!-- Recent applications -->
      <div class="card mt-6">
        <header class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
          <h2 class="text-lg font-semibold text-slate-900">Recent applications</h2>
          <RouterLink to="/student/applications" class="text-sm text-brand-600 hover:text-brand-700">View all →</RouterLink>
        </header>

        <div v-if="recent.length === 0" class="px-5 py-12 text-center text-slate-500">
          <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 12h6m-6 4h6M9 8h6M5 5h14v14H5z" />
          </svg>
          <p class="mt-3 text-sm">You haven't applied to any jobs yet.</p>
          <button class="mt-4 btn-primary" @click="router.push('/student/jobs')">Browse jobs</button>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="text-left px-5 py-3">Job Title</th>
                <th class="text-left px-5 py-3">Company</th>
                <th class="text-left px-5 py-3">Applied</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-right px-5 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="app in recent" :key="app.application_id" class="border-t border-slate-100">
                <td class="px-5 py-3 text-slate-900 font-medium">{{ app.job_title }}</td>
                <td class="px-5 py-3 text-slate-600">{{ app.company_name }}</td>
                <td class="px-5 py-3 text-slate-600">{{ formatDate(app.applied_at) }}</td>
                <td class="px-5 py-3"><StatusBadge :status="app.status" /></td>
                <td class="px-5 py-3 text-right">
                  <RouterLink :to="`/student/jobs/${app.job_id}`"
                              class="text-brand-600 hover:text-brand-700 text-sm">View</RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</template>

<!--
  SAMIN — M3: ManageApplications.vue
  This is the page where ADMINS see and manage ALL applications in the system.
  It shows a table with applicant name, job, company, status, and a dropdown
  to change the status. Uses "optimistic update" for a fast UI experience.
  CRUD operations used: READ (GET /applications), UPDATE (PUT /applications/{id}/status)
-->
<script setup>
// Import Vue functions: computed (auto-calculated), onMounted (runs on load), ref (reactive variables)
import { computed, onMounted, ref } from 'vue'
// api is the Axios instance for sending HTTP requests to the backend
import api from '../services/api.js'
// StatusBadge shows a colored pill for the application status
import StatusBadge from '../components/StatusBadge.vue'
// LoadingSpinner shows a spinning circle while data is loading
import LoadingSpinner from '../components/LoadingSpinner.vue'
// useToast shows pop-up messages
import { useToast } from '../composables/useToast.js'

const toast = useToast()
// loading = true while fetching applications from the API
const loading = ref(true)
// applications holds ALL applications in the system (from all students)
const applications = ref([])
// tab holds the currently selected filter tab
const tab = ref('all')
// STATUSES is the list of valid statuses (used in the dropdown)
const STATUSES = ['pending', 'reviewed', 'accepted', 'rejected']

// TABS defines the filter tabs at the top of the table
const TABS = [
  { id: 'all',      label: 'All' },
  { id: 'pending',  label: 'Pending' },
  { id: 'reviewed', label: 'Reviewed' },
  { id: 'accepted', label: 'Accepted' },
  { id: 'rejected', label: 'Rejected' }
]

// When the page loads, fetch all applications
onMounted(async () => {
  try {
    // GET /api/applications — since the admin is logged in, the backend
    // returns ALL applications (not filtered by user_id like for students)
    const { data } = await api.get('/applications')
    applications.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load applications.')
  } finally {
    loading.value = false
  }
})

// filtered is a computed property — automatically updates when tab or applications change
const filtered = computed(() => {
  if (tab.value === 'all') return applications.value
  return applications.value.filter(a => a.status === tab.value)
})

// tabCount returns how many applications are in a given tab
function tabCount (id) {
  if (id === 'all') return applications.value.length
  return applications.value.filter(a => a.status === id).length
}

// changeStatus is called when the admin changes the status dropdown
// CRUD: UPDATE — sends PUT /api/applications/{id}/status with the new status
// This uses "OPTIMISTIC UPDATE" — the UI updates instantly, then the API call happens
// If the API fails, the UI rolls back to the old status
async function changeStatus (app, newStatus) {
  if (newStatus === app.status) return    // no change, do nothing
  const previous = app.status             // save the old status for rollback
  // OPTIMISTIC UPDATE: update the UI immediately (before the API call)
  const idx = applications.value.findIndex(a => a.application_id === app.application_id)
  if (idx !== -1) applications.value[idx] = { ...app, status: newStatus }

  try {
    // Send PUT /api/applications/{id}/status with the new status
    await api.put(`/applications/${app.application_id}/status`, { status: newStatus })
    toast.success(`Status set to ${newStatus}.`)
  } catch (err) {
    // ROLLBACK: if the API failed, restore the old status
    if (idx !== -1) applications.value[idx] = { ...app, status: previous }
    toast.error(err?.response?.data?.message || 'Could not update status.')
  }
}

// formatDate converts a raw date to a nice format (e.g., "Jun 25, 2026")
function formatDate (d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<!--
  TEMPLATE = the HTML the admin sees
-->
<template>
  <!-- Main container -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Page title and subtitle -->
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">Applications</h1>
      <p class="text-sm text-slate-500">Move applications through the hiring pipeline.</p>
    </header>

    <!-- Card containing the tabs and the applications table -->
    <div class="card">
      <!-- Tab navigation bar (same as MyApplications) -->
      <nav class="border-b border-slate-200 px-4 sm:px-5 flex gap-1 overflow-x-auto">
        <button v-for="t in TABS" :key="t.id"
                @click="tab = t.id"
                class="py-3 px-3 text-sm font-medium whitespace-nowrap border-b-2 -mb-px"
                :class="tab === t.id
                  ? 'border-brand-600 text-brand-700'
                  : 'border-transparent text-slate-500 hover:text-slate-700'">
          {{ t.label }}
          <span class="ml-1 text-xs text-slate-400">{{ tabCount(t.id) }}</span>
        </button>
      </nav>

      <!-- Show spinner while loading -->
      <LoadingSpinner v-if="loading" />

      <!-- If no applications in this tab, show a message -->
      <div v-else-if="filtered.length === 0" class="px-5 py-12 text-center text-slate-500">
        <p>No applications in this tab.</p>
      </div>

      <!-- If there are applications, show them in a table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <!-- Table headers -->
          <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
            <tr>
              <th class="text-left px-5 py-3">Applicant</th>
              <th class="text-left px-5 py-3">Job</th>
              <th class="text-left px-5 py-3">Company</th>
              <th class="text-left px-5 py-3">Applied</th>
              <th class="text-left px-5 py-3">Current</th>
              <th class="text-left px-5 py-3">Update status</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loop through filtered applications and show each as a row -->
            <tr v-for="app in filtered" :key="app.application_id" class="border-t border-slate-100">
              <!-- Applicant name and email (from Areeb's users table via JOIN) -->
              <td class="px-5 py-3">
                <p class="font-medium text-slate-900">{{ app.applicant_name }}</p>
                <p class="text-xs text-slate-500">{{ app.applicant_email }}</p>
              </td>
              <!-- Job title (from my jobs table via JOIN) -->
              <td class="px-5 py-3 text-slate-700">{{ app.job_title }}</td>
              <!-- Company name (from Mariam's companies table via JOIN) -->
              <td class="px-5 py-3 text-slate-600">{{ app.company_name }}</td>
              <!-- Date the student applied -->
              <td class="px-5 py-3 text-slate-600">{{ formatDate(app.applied_at) }}</td>
              <!-- Current status badge (colored pill) -->
              <td class="px-5 py-3"><StatusBadge :status="app.status" /></td>
              <!-- Status dropdown — admin can change the status here -->
              <!-- @change calls changeStatus() which does the optimistic update -->
              <td class="px-5 py-3">
                <select :value="app.status"
                        @change="changeStatus(app, $event.target.value)"
                        class="input !py-1 !px-2 text-sm w-36">
                  <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<!--
  SAMIN — M3: MyApplications.vue
  This is the page where STUDENTS see all their job applications.
  It shows a table with tabs to filter by status (pending, reviewed, accepted, rejected).
  Students can withdraw (delete) an application, but only if it's still "pending".
  CRUD operations used: READ (GET /applications), DELETE (DELETE /applications/{id})
-->
<script setup>
// Import Vue functions: computed (auto-calculated), onMounted (runs on load), ref (reactive variables)
import { computed, onMounted, ref } from 'vue'
// api is the Axios instance for sending HTTP requests to the backend
import api from '../services/api.js'
// StatusBadge shows a colored pill for the application status (pending=yellow, reviewed=blue, etc.)
import StatusBadge from '../components/StatusBadge.vue'
// LoadingSpinner shows a spinning circle while data is loading
import LoadingSpinner from '../components/LoadingSpinner.vue'
// ConfirmDialog shows a "Are you sure?" popup before withdrawing
import ConfirmDialog from '../components/ConfirmDialog.vue'
// useToast shows pop-up messages
import { useToast } from '../composables/useToast.js'

const toast = useToast()
// loading = true while fetching applications from the API
const loading      = ref(true)
// applications holds the student's application list from the API
const applications = ref([])
// tab holds the currently selected tab ('all', 'pending', 'reviewed', 'accepted', 'rejected')
const tab          = ref('all')

// confirmOpen controls whether the "Are you sure?" withdraw popup is visible
const confirmOpen  = ref(false)
// targetId holds the ID of the application being withdrawn
const targetId     = ref(null)

// TABS defines the filter tabs at the top of the table
const TABS = [
  { id: 'all',      label: 'All' },       // show all applications
  { id: 'pending',  label: 'Pending' },   // show only pending
  { id: 'reviewed', label: 'Reviewed' },  // show only reviewed
  { id: 'accepted', label: 'Accepted' },  // show only accepted
  { id: 'rejected', label: 'Rejected' }   // show only rejected
]

// When the page loads, call the load() function
onMounted(load)

// load() fetches the student's applications from the API
async function load () {
  loading.value = true
  try {
    // GET /api/applications — the backend returns only THIS student's applications
    // (the backend filters by user_id from the JWT token)
    const { data } = await api.get('/applications')
    applications.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load your applications.')
  } finally {
    loading.value = false
  }
}

// filtered is a computed property — it automatically updates when tab or applications change
// If tab is 'all', return all applications. Otherwise, filter by the selected status.
const filtered = computed(() => {
  if (tab.value === 'all') return applications.value
  return applications.value.filter(a => a.status === tab.value)
})

// tabCount returns how many applications are in a given tab
// This shows a number next to each tab label (e.g., "Pending (3)")
function tabCount (id) {
  if (id === 'all') return applications.value.length
  return applications.value.filter(a => a.status === id).length
}

// askWithdraw opens the "Are you sure?" popup before withdrawing
function askWithdraw (id) {
  targetId.value = id
  confirmOpen.value = true
}

// withdraw is called after the student confirms the withdrawal
// CRUD: DELETE — sends DELETE /api/applications/{id} to the backend
async function withdraw () {
  const id = targetId.value
  confirmOpen.value = false    // close the confirm popup
  if (!id) return
  try {
    // DELETE /api/applications/{id} — backend checks ownership and that status is "pending"
    await api.delete(`/applications/${id}`)
    // Remove the application from the list instantly (no page reload needed)
    applications.value = applications.value.filter(a => a.application_id !== id)
    toast.success('Application withdrawn.')
  } catch (err) {
    // If the API returns an error (e.g., "only pending can be withdrawn"), show it
    toast.error(err?.response?.data?.message || 'Could not withdraw application.')
  } finally {
    targetId.value = null
  }
}

// formatDate converts a raw date to a nice format (e.g., "Jun 25, 2026")
function formatDate (d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<!--
  TEMPLATE = the HTML the student sees
-->
<template>
  <!-- Main container -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Page title and subtitle -->
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">My applications</h1>
      <p class="text-sm text-slate-500">Track every job you've applied for in one place.</p>
    </header>

    <!-- Card containing the tabs and the applications table -->
    <div class="card">
      <!-- Tab navigation bar -->
      <nav class="border-b border-slate-200 px-4 sm:px-5 flex gap-1 overflow-x-auto">
        <!-- Loop through TABS and render a button for each -->
        <button v-for="t in TABS" :key="t.id"
                @click="tab = t.id"
                class="py-3 px-3 text-sm font-medium whitespace-nowrap border-b-2 -mb-px"
                :class="tab === t.id
                  ? 'border-brand-600 text-brand-700'
                  : 'border-transparent text-slate-500 hover:text-slate-700'">
          {{ t.label }}
          <!-- Show count next to each tab label -->
          <span class="ml-1 text-xs text-slate-400">{{ tabCount(t.id) }}</span>
        </button>
      </nav>

      <!-- Show spinner while loading -->
      <LoadingSpinner v-if="loading" />

      <!-- If no applications in this tab, show an empty state with an icon -->
      <div v-else-if="filtered.length === 0" class="px-5 py-12 text-center text-slate-500">
        <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 5h14v14H5z M9 9h6 M9 13h6 M9 17h4" />
        </svg>
        <p class="mt-3 text-sm">No applications in this tab yet.</p>
      </div>

      <!-- If there are applications, show them in a table -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <!-- Table headers -->
          <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
            <tr>
              <th class="text-left px-5 py-3">Job Title</th>
              <th class="text-left px-5 py-3">Company</th>
              <th class="text-left px-5 py-3">Applied</th>
              <th class="text-left px-5 py-3">Status</th>
              <th class="text-right px-5 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Loop through filtered applications and show each as a row -->
            <tr v-for="app in filtered" :key="app.application_id" class="border-t border-slate-100">
              <td class="px-5 py-3 font-medium text-slate-900">{{ app.job_title }}</td>
              <td class="px-5 py-3 text-slate-600">{{ app.company_name }}</td>
              <td class="px-5 py-3 text-slate-600">{{ formatDate(app.applied_at) }}</td>
              <!-- StatusBadge shows a colored pill based on the status -->
              <td class="px-5 py-3"><StatusBadge :status="app.status" /></td>
              <td class="px-5 py-3 text-right">
                <!-- Withdraw button only shows for pending applications -->
                <!-- Once the admin changes the status, the student cannot withdraw -->
                <button v-if="app.status === 'pending'"
                        class="text-red-600 hover:text-red-700 text-sm"
                        @click="askWithdraw(app.application_id)">
                  Withdraw
                </button>
                <!-- For non-pending applications, show a dash (no action available) -->
                <span v-else class="text-slate-400 text-sm">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Confirm dialog for withdraw (shows "Are you sure?" before deleting) -->
    <ConfirmDialog :open="confirmOpen"
                   title="Withdraw application?"
                   message="This action cannot be undone. The application will be removed from your list."
                   confirm-text="Withdraw"
                   variant="danger"
                   @confirm="withdraw"
                   @cancel="confirmOpen = false" />
  </section>
</template>

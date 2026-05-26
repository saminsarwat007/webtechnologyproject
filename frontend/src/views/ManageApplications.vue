<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api.js'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'

const toast = useToast()
const loading = ref(true)
const applications = ref([])
const tab = ref('all')
const STATUSES = ['pending', 'reviewed', 'accepted', 'rejected']

const TABS = [
  { id: 'all',      label: 'All' },
  { id: 'pending',  label: 'Pending' },
  { id: 'reviewed', label: 'Reviewed' },
  { id: 'accepted', label: 'Accepted' },
  { id: 'rejected', label: 'Rejected' }
]

onMounted(async () => {
  try {
    const { data } = await api.get('/applications')
    applications.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load applications.')
  } finally {
    loading.value = false
  }
})

const filtered = computed(() => {
  if (tab.value === 'all') return applications.value
  return applications.value.filter(a => a.status === tab.value)
})

function tabCount (id) {
  if (id === 'all') return applications.value.length
  return applications.value.filter(a => a.status === id).length
}

async function changeStatus (app, newStatus) {
  if (newStatus === app.status) return
  const previous = app.status
  // optimistic update
  const idx = applications.value.findIndex(a => a.application_id === app.application_id)
  if (idx !== -1) applications.value[idx] = { ...app, status: newStatus }

  try {
    await api.put(`/applications/${app.application_id}/status`, { status: newStatus })
    toast.success(`Status set to ${newStatus}.`)
  } catch (err) {
    // rollback
    if (idx !== -1) applications.value[idx] = { ...app, status: previous }
    toast.error(err?.response?.data?.message || 'Could not update status.')
  }
}

function formatDate (d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">Applications</h1>
      <p class="text-sm text-slate-500">Move applications through the hiring pipeline.</p>
    </header>

    <div class="card">
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

      <LoadingSpinner v-if="loading" />

      <div v-else-if="filtered.length === 0" class="px-5 py-12 text-center text-slate-500">
        <p>No applications in this tab.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
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
            <tr v-for="app in filtered" :key="app.application_id" class="border-t border-slate-100">
              <td class="px-5 py-3">
                <p class="font-medium text-slate-900">{{ app.applicant_name }}</p>
                <p class="text-xs text-slate-500">{{ app.applicant_email }}</p>
              </td>
              <td class="px-5 py-3 text-slate-700">{{ app.job_title }}</td>
              <td class="px-5 py-3 text-slate-600">{{ app.company_name }}</td>
              <td class="px-5 py-3 text-slate-600">{{ formatDate(app.applied_at) }}</td>
              <td class="px-5 py-3"><StatusBadge :status="app.status" /></td>
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

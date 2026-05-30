<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api.js'
import StatusBadge from '../components/StatusBadge.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'

const toast = useToast()
const loading      = ref(true)
const applications = ref([])
const tab          = ref('all')

const confirmOpen  = ref(false)
const targetId     = ref(null)

const TABS = [
  { id: 'all',      label: 'All' },
  { id: 'pending',  label: 'Pending' },
  { id: 'reviewed', label: 'Reviewed' },
  { id: 'accepted', label: 'Accepted' },
  { id: 'rejected', label: 'Rejected' }
]

onMounted(load)

async function load () {
  loading.value = true
  try {
    const { data } = await api.get('/applications')
    applications.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load your applications.')
  } finally {
    loading.value = false
  }
}

const filtered = computed(() => {
  if (tab.value === 'all') return applications.value
  return applications.value.filter(a => a.status === tab.value)
})

function tabCount (id) {
  if (id === 'all') return applications.value.length
  return applications.value.filter(a => a.status === id).length
}

function askWithdraw (id) {
  targetId.value = id
  confirmOpen.value = true
}
async function withdraw () {
  const id = targetId.value
  confirmOpen.value = false
  if (!id) return
  try {
    await api.delete(`/applications/${id}`)
    applications.value = applications.value.filter(a => a.application_id !== id)
    toast.success('Application withdrawn.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not withdraw application.')
  } finally {
    targetId.value = null
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
      <h1 class="text-2xl font-semibold text-slate-900">My applications</h1>
      <p class="text-sm text-slate-500">Track every job you've applied for in one place.</p>
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
        <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 5h14v14H5z M9 9h6 M9 13h6 M9 17h4" />
        </svg>
        <p class="mt-3 text-sm">No applications in this tab yet.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
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
            <tr v-for="app in filtered" :key="app.application_id" class="border-t border-slate-100">
              <td class="px-5 py-3 font-medium text-slate-900">{{ app.job_title }}</td>
              <td class="px-5 py-3 text-slate-600">{{ app.company_name }}</td>
              <td class="px-5 py-3 text-slate-600">{{ formatDate(app.applied_at) }}</td>
              <td class="px-5 py-3"><StatusBadge :status="app.status" /></td>
              <td class="px-5 py-3 text-right">
                <button v-if="app.status === 'pending'"
                        class="text-red-600 hover:text-red-700 text-sm"
                        @click="askWithdraw(app.application_id)">
                  Withdraw
                </button>
                <span v-else class="text-slate-400 text-sm">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog :open="confirmOpen"
                   title="Withdraw application?"
                   message="This action cannot be undone. The application will be removed from your list."
                   confirm-text="Withdraw"
                   variant="danger"
                   @confirm="withdraw"
                   @cancel="confirmOpen = false" />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'

const toast = useToast()
const loading   = ref(true)
const jobs      = ref([])
const companies = ref([])

const modalOpen = ref(false)
const editingId = ref(null)

const form = reactive({
  company_id: '',
  title: '',
  type: 'internship',
  description: '',
  requirements: '',
  deadline: ''
})
const errors = reactive({
  company_id: '', title: '', type: '', description: '', deadline: ''
})

const confirmOpen = ref(false)
const deleteId    = ref(null)

const todayStr = computed(() => new Date().toISOString().slice(0, 10))

onMounted(async () => {
  await Promise.all([loadJobs(), loadCompanies()])
})

async function loadJobs () {
  loading.value = true
  try {
    const { data } = await api.get('/jobs')
    jobs.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load jobs.')
  } finally {
    loading.value = false
  }
}

async function loadCompanies () {
  try {
    const { data } = await api.get('/companies')
    companies.value = data?.data ?? []
  } catch (_e) {
    /* surfaced when the form is opened */
  }
}

function openCreate () {
  editingId.value = null
  form.company_id   = companies.value[0]?.company_id ?? ''
  form.title        = ''
  form.type         = 'internship'
  form.description  = ''
  form.requirements = ''
  form.deadline     = ''
  Object.keys(errors).forEach(k => errors[k] = '')
  modalOpen.value = true
}

function openEdit (job) {
  editingId.value   = job.job_id
  form.company_id   = job.company_id
  form.title        = job.title
  form.type         = job.type
  form.description  = job.description
  form.requirements = job.requirements ?? ''
  form.deadline     = job.deadline?.slice(0, 10) ?? ''
  Object.keys(errors).forEach(k => errors[k] = '')
  modalOpen.value = true
}

function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')
  if (!form.company_id)                                       errors.company_id = 'Company is required.'
  if (!form.title.trim())                                     errors.title = 'Title is required.'
  else if (form.title.length > 150)                           errors.title = 'Title must be 150 chars or fewer.'
  if (!['internship', 'fulltime', 'parttime'].includes(form.type)) errors.type = 'Choose a valid type.'
  if (!form.description.trim())                               errors.description = 'Description is required.'
  if (!form.deadline)                                         errors.deadline = 'Deadline is required.'
  else if (form.deadline < todayStr.value)                    errors.deadline = 'Deadline must be in the future.'
  return Object.values(errors).every(v => v === '')
}

async function save () {
  if (!validate()) return
  try {
    if (editingId.value) {
      const { data } = await api.put(`/jobs/${editingId.value}`, { ...form })
      jobs.value = jobs.value.map(j => j.job_id === editingId.value ? data.data : j)
      toast.success('Job updated.')
    } else {
      const { data } = await api.post('/jobs', { ...form })
      jobs.value = [data.data, ...jobs.value]
      toast.success('Job created.')
    }
    modalOpen.value = false
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not save job.')
  }
}

function askDelete (id) {
  deleteId.value = id
  confirmOpen.value = true
}
async function doDelete () {
  const id = deleteId.value
  confirmOpen.value = false
  if (!id) return
  try {
    await api.delete(`/jobs/${id}`)
    jobs.value = jobs.value.map(j => j.job_id === id ? { ...j, is_active: 0 } : j)
    toast.success('Job deactivated.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete job.')
  } finally {
    deleteId.value = null
  }
}

function formatDate (d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Manage jobs</h1>
        <p class="text-sm text-slate-500">Create, edit, and retire job listings.</p>
      </div>
      <button class="btn-primary" @click="openCreate">Add job</button>
    </header>

    <LoadingSpinner v-if="loading" />

    <div v-else class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
          <tr>
            <th class="text-left px-5 py-3">Title</th>
            <th class="text-left px-5 py-3">Company</th>
            <th class="text-left px-5 py-3">Type</th>
            <th class="text-left px-5 py-3">Deadline</th>
            <th class="text-left px-5 py-3">Status</th>
            <th class="text-right px-5 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="jobs.length === 0">
            <td colspan="6" class="px-5 py-12 text-center text-slate-500">No jobs yet — click "Add job".</td>
          </tr>
          <tr v-for="job in jobs" :key="job.job_id" class="border-t border-slate-100">
            <td class="px-5 py-3 font-medium text-slate-900">{{ job.title }}</td>
            <td class="px-5 py-3 text-slate-600">{{ job.company_name }}</td>
            <td class="px-5 py-3 text-slate-600 capitalize">{{ job.type }}</td>
            <td class="px-5 py-3 text-slate-600">{{ formatDate(job.deadline) }}</td>
            <td class="px-5 py-3">
              <span class="badge" :class="job.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                {{ job.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-5 py-3 text-right space-x-3">
              <button class="text-brand-600 hover:text-brand-700 text-sm" @click="openEdit(job)">Edit</button>
              <button class="text-red-600 hover:text-red-700 text-sm"
                      @click="askDelete(job.job_id)" :disabled="!job.is_active">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create / edit modal -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="modalOpen = false">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-2xl w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">{{ editingId ? 'Edit job' : 'New job' }}</h3>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="label">Title</label>
              <input v-model="form.title" class="input" :class="{ 'input--error': errors.title }" />
              <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
            </div>
            <div>
              <label class="label">Company</label>
              <select v-model="form.company_id" class="input" :class="{ 'input--error': errors.company_id }">
                <option value="">Select…</option>
                <option v-for="c in companies" :key="c.company_id" :value="c.company_id">{{ c.name }}</option>
              </select>
              <p v-if="errors.company_id" class="mt-1 text-sm text-red-500">{{ errors.company_id }}</p>
            </div>
            <div>
              <label class="label">Type</label>
              <select v-model="form.type" class="input" :class="{ 'input--error': errors.type }">
                <option value="internship">Internship</option>
                <option value="fulltime">Full-time</option>
                <option value="parttime">Part-time</option>
              </select>
              <p v-if="errors.type" class="mt-1 text-sm text-red-500">{{ errors.type }}</p>
            </div>
            <div>
              <label class="label">Deadline</label>
              <input v-model="form.deadline" type="date" :min="todayStr" class="input"
                     :class="{ 'input--error': errors.deadline }" />
              <p v-if="errors.deadline" class="mt-1 text-sm text-red-500">{{ errors.deadline }}</p>
            </div>
          </div>

          <div class="mt-4">
            <label class="label">Description</label>
            <textarea v-model="form.description" rows="4" class="input"
                      :class="{ 'input--error': errors.description }" />
            <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
          </div>
          <div class="mt-4">
            <label class="label">Requirements</label>
            <textarea v-model="form.requirements" rows="3" class="input" />
          </div>

          <div class="mt-6 flex justify-end gap-2">
            <button class="btn-secondary" @click="modalOpen = false">Cancel</button>
            <button class="btn-primary" @click="save">
              {{ editingId ? 'Save changes' : 'Create job' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <ConfirmDialog :open="confirmOpen"
                   title="Deactivate job?"
                   message="Students will no longer see this listing. The record stays in the database for audit."
                   confirm-text="Deactivate"
                   variant="danger"
                   @confirm="doDelete"
                   @cancel="confirmOpen = false" />
  </section>
</template>

<!--
  SAMIN — M2: ManageJobs.vue
  This is the page where ADMINS manage job listings.
  Admins can create new jobs, edit existing ones, and deactivate (soft delete) jobs.
  It shows a table of all jobs with Edit and Delete buttons.
  CRUD operations used: READ (GET /jobs), CREATE (POST /jobs), UPDATE (PUT /jobs/{id}), DELETE (DELETE /jobs/{id})
-->
<script setup>
// Import Vue functions:
// - computed: for auto-calculated values
// - onMounted: runs code when the page loads
// - reactive: for objects with multiple fields (like a form)
// - ref: for single reactive values
import { computed, onMounted, reactive, ref } from 'vue'
// api is the Axios instance for sending HTTP requests to the backend
import api from '../services/api.js'
// LoadingSpinner shows a spinning circle while data is loading
import LoadingSpinner from '../components/LoadingSpinner.vue'
// ConfirmDialog shows a "Are you sure?" popup before deleting
import ConfirmDialog from '../components/ConfirmDialog.vue'
// useToast shows pop-up messages
import { useToast } from '../composables/useToast.js'

const toast = useToast()
// loading = true while fetching jobs from the API
const loading   = ref(true)
// jobs holds the list of all jobs (active and inactive)
const jobs      = ref([])
// companies holds the list of companies (for the dropdown in the form)
// This comes from Mariam's API (GET /api/companies)
const companies = ref([])

// modalOpen controls whether the Create/Edit popup is visible
const modalOpen = ref(false)
// editingId holds the ID of the job being edited (null = creating a new job)
const editingId = ref(null)

// form is a reactive object that holds all the form fields
// reactive is used instead of ref because it's an object with multiple fields
const form = reactive({
  company_id: '',        // which company this job belongs to (dropdown)
  title: '',             // job title
  type: 'internship',    // job type (default: internship)
  description: '',       // full job description
  requirements: '',      // what the candidate needs (optional)
  deadline: ''           // application deadline (date)
})

// errors holds validation error messages for each field
// When a field has an error, the message shows in red below the input
const errors = reactive({
  company_id: '', title: '', type: '', description: '', deadline: ''
})

// confirmOpen controls whether the "Are you sure?" delete popup is visible
const confirmOpen = ref(false)
// deleteId holds the ID of the job being deleted
const deleteId    = ref(null)

// todayStr gives today's date in YYYY-MM-DD format (used to prevent past deadlines)
const todayStr = computed(() => new Date().toISOString().slice(0, 10))

// When the page loads, fetch jobs AND companies at the same time
onMounted(async () => {
  await Promise.all([loadJobs(), loadCompanies()])
})

// loadJobs fetches all jobs from the API
async function loadJobs () {
  loading.value = true
  try {
    // GET /api/jobs — fetch all jobs
    const { data } = await api.get('/jobs')
    jobs.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load jobs.')
  } finally {
    loading.value = false
  }
}

// loadCompanies fetches all companies from Mariam's API
// We need this to fill the company dropdown in the form
async function loadCompanies () {
  try {
    // GET /api/companies — fetch all companies (Mariam's module)
    const { data } = await api.get('/companies')
    companies.value = data?.data ?? []
  } catch (_e) {
    // If this fails, we silently ignore it — the error will show when the form opens
  }
}

// openCreate opens the popup form to create a NEW job
function openCreate () {
  editingId.value = null                    // null means we're creating, not editing
  form.company_id   = companies.value[0]?.company_id ?? ''  // default to first company
  form.title        = ''
  form.type         = 'internship'
  form.description  = ''
  form.requirements = ''
  form.deadline     = ''
  Object.keys(errors).forEach(k => errors[k] = '')  // clear all error messages
  modalOpen.value = true                     // show the popup
}

// openEdit opens the popup form to EDIT an existing job
function openEdit (job) {
  editingId.value   = job.job_id            // store the ID so we know we're editing
  form.company_id   = job.company_id        // fill the form with the job's current data
  form.title        = job.title
  form.type         = job.type
  form.description  = job.description
  form.requirements = job.requirements ?? ''
  form.deadline     = job.deadline?.slice(0, 10) ?? ''  // extract just the date part (YYYY-MM-DD)
  Object.keys(errors).forEach(k => errors[k] = '')      // clear all error messages
  modalOpen.value = true                     // show the popup
}

// validate checks all form fields before sending to the API
// Returns true if all fields are valid, false if any have errors
function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')  // clear previous errors
  if (!form.company_id)                                       errors.company_id = 'Company is required.'
  if (!form.title.trim())                                     errors.title = 'Title is required.'
  else if (form.title.length > 150)                           errors.title = 'Title must be 150 chars or fewer.'
  if (!['internship', 'fulltime', 'parttime'].includes(form.type)) errors.type = 'Choose a valid type.'
  if (!form.description.trim())                               errors.description = 'Description is required.'
  if (!form.deadline)                                         errors.deadline = 'Deadline is required.'
  else if (form.deadline < todayStr.value)                    errors.deadline = 'Deadline must be in the future.'
  // If all error messages are empty strings, validation passed
  return Object.values(errors).every(v => v === '')
}

// save is called when the admin clicks "Create job" or "Save changes"
// CRUD: CREATE (POST /api/jobs) or CRUD: UPDATE (PUT /api/jobs/{id})
async function save () {
  if (!validate()) return    // stop if validation fails
  try {
    if (editingId.value) {
      // EDITING: send PUT /api/jobs/{id} with the updated data
      const { data } = await api.put(`/jobs/${editingId.value}`, { ...form })
      // Replace the old job in the list with the updated one
      jobs.value = jobs.value.map(j => j.job_id === editingId.value ? data.data : j)
      toast.success('Job updated.')
    } else {
      // CREATING: send POST /api/jobs with the new job data
      const { data } = await api.post('/jobs', { ...form })
      // Add the new job to the TOP of the list
      jobs.value = [data.data, ...jobs.value]
      toast.success('Job created.')
    }
    modalOpen.value = false   // close the popup
  } catch (err) {
    // If the API returns an error, show it
    toast.error(err?.response?.data?.message || 'Could not save job.')
  }
}

// askDelete opens the "Are you sure?" popup before deleting
function askDelete (id) {
  deleteId.value = id
  confirmOpen.value = true
}

// doDelete is called after the admin confirms the delete
// CRUD: DELETE — sends DELETE /api/jobs/{id} (soft delete, sets is_active = 0)
async function doDelete () {
  const id = deleteId.value
  confirmOpen.value = false    // close the confirm popup
  if (!id) return
  try {
    // DELETE /api/jobs/{id} — backend sets is_active = 0 (soft delete)
    await api.delete(`/jobs/${id}`)
    // Update the UI: set is_active to 0 for this job (shows as "Inactive")
    jobs.value = jobs.value.map(j => j.job_id === id ? { ...j, is_active: 0 } : j)
    toast.success('Job deactivated.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete job.')
  } finally {
    deleteId.value = null
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
    <!-- Header with title and "Add job" button -->
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Manage jobs</h1>
        <p class="text-sm text-slate-500">Create, edit, and retire job listings.</p>
      </div>
      <!-- "Add job" button opens the create form -->
      <button class="btn-primary" @click="openCreate">Add job</button>
    </header>

    <!-- Show spinner while loading -->
    <LoadingSpinner v-if="loading" />

    <!-- Once loaded, show the jobs table -->
    <div v-else class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <!-- Table headers -->
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
          <!-- If there are no jobs, show a message -->
          <tr v-if="jobs.length === 0">
            <td colspan="6" class="px-5 py-12 text-center text-slate-500">No jobs yet — click "Add job".</td>
          </tr>
          <!-- Loop through all jobs and show each as a row -->
          <tr v-for="job in jobs" :key="job.job_id" class="border-t border-slate-100">
            <td class="px-5 py-3 font-medium text-slate-900">{{ job.title }}</td>
            <td class="px-5 py-3 text-slate-600">{{ job.company_name }}</td>
            <!-- capitalize makes "internship" show as "Internship" -->
            <td class="px-5 py-3 text-slate-600 capitalize">{{ job.type }}</td>
            <td class="px-5 py-3 text-slate-600">{{ formatDate(job.deadline) }}</td>
            <!-- Status badge: green for Active, gray for Inactive -->
            <td class="px-5 py-3">
              <span class="badge" :class="job.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                {{ job.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <!-- Edit and Delete buttons -->
            <td class="px-5 py-3 text-right space-x-3">
              <button class="text-brand-600 hover:text-brand-700 text-sm" @click="openEdit(job)">Edit</button>
              <!-- Delete button is disabled if the job is already inactive -->
              <button class="text-red-600 hover:text-red-700 text-sm"
                      @click="askDelete(job.job_id)" :disabled="!job.is_active">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Create/Edit modal (popup form) -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="modalOpen = false">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-2xl w-full p-6">
          <!-- Title changes based on whether we're creating or editing -->
          <h3 class="text-lg font-semibold text-slate-900">{{ editingId ? 'Edit job' : 'New job' }}</h3>

          <!-- Form fields in a 2-column grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <!-- Title input -->
            <div>
              <label class="label">Title</label>
              <input v-model="form.title" class="input" :class="{ 'input--error': errors.title }" />
              <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
            </div>
            <!-- Company dropdown (filled from Mariam's API) -->
            <div>
              <label class="label">Company</label>
              <select v-model="form.company_id" class="input" :class="{ 'input--error': errors.company_id }">
                <option value="">Select…</option>
                <option v-for="c in companies" :key="c.company_id" :value="c.company_id">{{ c.name }}</option>
              </select>
              <p v-if="errors.company_id" class="mt-1 text-sm text-red-500">{{ errors.company_id }}</p>
            </div>
            <!-- Type dropdown -->
            <div>
              <label class="label">Type</label>
              <select v-model="form.type" class="input" :class="{ 'input--error': errors.type }">
                <option value="internship">Internship</option>
                <option value="fulltime">Full-time</option>
                <option value="parttime">Part-time</option>
              </select>
              <p v-if="errors.type" class="mt-1 text-sm text-red-500">{{ errors.type }}</p>
            </div>
            <!-- Deadline date picker (min = today, so you can't pick a past date) -->
            <div>
              <label class="label">Deadline</label>
              <input v-model="form.deadline" type="date" :min="todayStr" class="input"
                     :class="{ 'input--error': errors.deadline }" />
              <p v-if="errors.deadline" class="mt-1 text-sm text-red-500">{{ errors.deadline }}</p>
            </div>
          </div>

          <!-- Description textarea -->
          <div class="mt-4">
            <label class="label">Description</label>
            <textarea v-model="form.description" rows="4" class="input"
                      :class="{ 'input--error': errors.description }" />
            <p v-if="errors.description" class="mt-1 text-sm text-red-500">{{ errors.description }}</p>
          </div>
          <!-- Requirements textarea (optional, no validation) -->
          <div class="mt-4">
            <label class="label">Requirements</label>
            <textarea v-model="form.requirements" rows="3" class="input" />
          </div>

          <!-- Cancel and Save buttons -->
          <div class="mt-6 flex justify-end gap-2">
            <button class="btn-secondary" @click="modalOpen = false">Cancel</button>
            <button class="btn-primary" @click="save">
              {{ editingId ? 'Save changes' : 'Create job' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Confirm dialog for delete (shows "Are you sure?" before deactivating) -->
    <ConfirmDialog :open="confirmOpen"
                   title="Deactivate job?"
                   message="Students will no longer see this listing. The record stays in the database for audit."
                   confirm-text="Deactivate"
                   variant="danger"
                   @confirm="doDelete"
                   @cancel="confirmOpen = false" />
  </section>
</template>

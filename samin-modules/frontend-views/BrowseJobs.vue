<!--
  SAMIN — M2: BrowseJobs.vue
  This is the page where STUDENTS browse all active jobs.
  It shows a grid of job cards with a search bar and a type filter.
  Students can also apply for a job from here using a popup modal.
  CRUD operations used: READ (GET /jobs), CREATE (POST /applications)
-->
<script setup>
// Import Vue functions we need:
// - computed: for values that auto-update when other values change
// - onMounted: runs code when the page first loads
// - ref: creates reactive variables (when they change, the screen updates)
// - watch: watches a variable and runs code when it changes
import { computed, onMounted, ref, watch } from 'vue'
// useRouter lets us navigate to another page programmatically
import { useRouter } from 'vue-router'
// api is the Axios instance that sends HTTP requests to our backend
// It automatically attaches the JWT token to every request
import api from '../services/api.js'
// JobCard is a reusable component that shows one job as a card
import JobCard from '../components/JobCard.vue'
// LoadingSpinner is a spinning circle shown while data is loading
import LoadingSpinner from '../components/LoadingSpinner.vue'
// useToast shows pop-up messages (success or error) at the bottom of the screen
import { useToast } from '../composables/useToast.js'

// router lets us navigate to other pages (like the job detail page)
const router = useRouter()
// toast lets us show pop-up messages
const toast  = useToast()

// loading = true means the spinner is showing (data is still being fetched)
const loading      = ref(true)
// jobs will hold the list of jobs from the API (starts empty)
const jobs         = ref([])
// appliedJobIds is a Set (like a list but faster for checking "is X in it?")
// It stores the job IDs the student has already applied to, so we can
// disable the Apply button for those jobs
const appliedJobIds = ref(new Set())

// search holds the text the student types in the search box
const search       = ref('')
// debouncedQ is the search text after a 400ms delay (so we don't search on every keystroke)
const debouncedQ   = ref('')
// typeFilter holds the selected job type filter ('all', 'internship', 'fulltime', 'parttime')
const typeFilter   = ref('all')

// modalOpen controls whether the Apply popup is visible (true = visible)
const modalOpen    = ref(false)
// modalJob holds the job object the student is applying to (shown in the popup)
const modalJob     = ref(null)
// coverLetter holds the text the student types in the cover letter field
const coverLetter  = ref('')
// submitting = true while the Apply request is being sent (disables the button)
const submitting   = ref(false)

// debounceTimer is used to delay the search (so we don't search on every keystroke)
let debounceTimer = null
// Watch the search box — when the student types, wait 400ms then update debouncedQ
// This is called "debounce" — it prevents searching on every single keystroke
watch(search, (val) => {
  clearTimeout(debounceTimer) // cancel the previous timer
  debounceTimer = setTimeout(() => { debouncedQ.value = val.trim().toLowerCase() }, 400) // start a new 400ms timer
})

// onMounted runs when the page first loads
// We fetch jobs AND the student's existing applications at the same time (parallel)
onMounted(async () => {
  try {
    // Promise.all runs both requests at the same time (faster than one after the other)
    // If the applications request fails, we catch it and return empty (so the page still works)
    const [jobsRes, appsRes] = await Promise.all([
      api.get('/jobs'),                    // GET /api/jobs — fetch all active jobs
      api.get('/applications').catch(() => ({ data: { data: [] } }))  // GET /api/applications — fetch student's applications
    ])
    // Store the jobs in the jobs variable (or empty array if no data)
    jobs.value = jobsRes.data?.data ?? []
    // Convert the applications into a Set of job IDs
    // This lets us quickly check "has this student already applied to job X?"
    appliedJobIds.value = new Set((appsRes.data?.data ?? []).map(a => Number(a.job_id)))
  } catch (_e) {
    // If the jobs request fails, show an error message
    toast.error('Could not load jobs.')
  } finally {
    // Always stop the loading spinner, whether it succeeded or failed
    loading.value = false
  }
})

// filteredJobs is a computed property — it automatically recalculates whenever
// jobs, typeFilter, or debouncedQ changes. No need to manually re-filter.
const filteredJobs = computed(() => {
  return jobs.value.filter(j => {
    // If a type filter is selected and the job's type doesn't match, skip it
    if (typeFilter.value !== 'all' && j.type !== typeFilter.value) return false
    // If search is empty, show all jobs (that passed the type filter)
    if (debouncedQ.value === '') return true
    // Combine the job title and company name into one lowercase string
    const haystack = `${j.title} ${j.company_name}`.toLowerCase()
    // Check if the search text appears in the title or company name
    return haystack.includes(debouncedQ.value)
  })
})

// openApply is called when the student clicks "Apply" on a job card
// It opens the popup modal with the job info
function openApply (job) {
  modalJob.value = job           // store which job we're applying to
  coverLetter.value = ''         // clear the cover letter field
  modalOpen.value = true         // show the popup
}

// closeModal closes the popup and clears the form
function closeModal () {
  modalOpen.value = false        // hide the popup
  modalJob.value = null          // clear the job
  coverLetter.value = ''         // clear the cover letter
}

// submitApply is called when the student clicks "Submit application" in the popup
// CRUD: CREATE — sends POST /api/applications to create a new application
async function submitApply () {
  if (!modalJob.value) return    // safety check: if no job, do nothing
  submitting.value = true        // disable the button (show "Submitting...")
  try {
    // Send POST /api/applications with the job ID and cover letter
    await api.post('/applications', {
      job_id: modalJob.value.job_id,
      cover_letter: coverLetter.value.trim()
    })
    // Add this job ID to the appliedJobIds Set so the Apply button stays disabled
    appliedJobIds.value.add(Number(modalJob.value.job_id))
    // Show a success message
    toast.success('Application submitted!')
    // Close the popup
    closeModal()
  } catch (err) {
    // If the API returns an error (e.g., "already applied"), show it
    const msg = err?.response?.data?.message || 'Could not submit application.'
    toast.error(msg)
  } finally {
    // Re-enable the button
    submitting.value = false
  }
}

// viewDetail is called when the student clicks "View details" on a job card
// It navigates to the JobDetail page for that specific job
function viewDetail (job) {
  router.push(`/student/jobs/${job.job_id}`)
}
</script>

<!--
  TEMPLATE = the HTML part of the page
  This is what the student actually sees
-->
<template>
  <!-- Main container with padding and max width -->
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <!-- Page title and subtitle -->
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">Browse jobs</h1>
      <p class="text-sm text-slate-500">Discover internships and full-time roles.</p>
    </header>

    <!-- Search bar and filter dropdown inside a card -->
    <div class="card p-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
      <!-- Search input with a magnifying glass icon -->
      <div class="relative flex-1">
        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.35-4.35" />
        </svg>
        <!-- v-model binds this input to the "search" variable -->
        <input v-model="search" type="text" placeholder="Search by title or company"
               class="input pl-9" />
      </div>
      <!-- Type filter dropdown — v-model binds to "typeFilter" -->
      <select v-model="typeFilter" class="input sm:w-48">
        <option value="all">All types</option>
        <option value="internship">Internship</option>
        <option value="fulltime">Full-time</option>
        <option value="parttime">Part-time</option>
      </select>
    </div>

    <!-- Show spinner while loading -->
    <LoadingSpinner v-if="loading" />

    <!-- Once loading is done -->
    <div v-else>
      <!-- If no jobs match the filters, show an empty state with an icon -->
      <div v-if="filteredJobs.length === 0" class="card p-12 text-center text-slate-500">
        <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 21l-4.35-4.35" /><circle cx="11" cy="11" r="7" />
        </svg>
        <p class="mt-3 text-sm">No jobs match your filters.</p>
      </div>

      <!-- If there are jobs, show them in a responsive grid (1 col on mobile, 2 on tablet, 3 on desktop) -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Loop through filteredJobs and render a JobCard for each -->
        <!-- :already-applied checks if this job's ID is in the appliedJobIds Set -->
        <!-- @apply and @view-detail are events from the JobCard component -->
        <JobCard v-for="job in filteredJobs" :key="job.job_id" :job="job"
                 :already-applied="appliedJobIds.has(Number(job.job_id))"
                 @apply="openApply" @view-detail="viewDetail" />
      </div>
    </div>

    <!-- Apply modal (popup) — only shows when modalOpen is true -->
    <Transition name="fade">
      <!-- Fixed overlay covers the whole screen, clicking outside closes the modal -->
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <!-- Dark semi-transparent background -->
        <div class="absolute inset-0 bg-slate-900/50" />
        <!-- White card with the apply form -->
        <div class="relative card max-w-lg w-full p-6">
          <!-- Job title and company name in the popup -->
          <h3 class="text-lg font-semibold text-slate-900">Apply for {{ modalJob?.title }}</h3>
          <p class="text-sm text-slate-500">{{ modalJob?.company_name }}</p>
          <!-- Cover letter textarea (optional, max 5000 characters) -->
          <label class="label mt-4" for="cover">Cover letter (optional)</label>
          <textarea id="cover" v-model="coverLetter" rows="6" class="input"
                    placeholder="Tell the recruiter why you're a great fit…" maxlength="5000" />
          <!-- Character counter -->
          <p class="text-xs text-slate-400 mt-1">{{ coverLetter.length }} / 5000</p>
          <!-- Cancel and Submit buttons -->
          <div class="mt-5 flex justify-end gap-2">
            <button class="btn-secondary" @click="closeModal" :disabled="submitting">Cancel</button>
            <button class="btn-primary" @click="submitApply" :disabled="submitting">
              {{ submitting ? 'Submitting…' : 'Submit application' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </section>
</template>

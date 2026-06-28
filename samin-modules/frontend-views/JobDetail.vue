<!--
  SAMIN — M2: JobDetail.vue
  This is the page where STUDENTS see the full details of a single job.
  It shows the job title, company, deadline, description, requirements,
  and an Apply button (with a popup modal like BrowseJobs).
  CRUD operations used: READ (GET /jobs/{id}), CREATE (POST /applications)
-->
<script setup>
// Import Vue functions: computed (auto-calculated values), onMounted (runs on load), ref (reactive variables)
import { computed, onMounted, ref } from 'vue'
// useRoute gives us access to the URL parameters (like the job ID in the URL)
// useRouter lets us navigate to other pages
import { useRoute, useRouter } from 'vue-router'
// api is the Axios instance for sending HTTP requests to the backend
import api from '../services/api.js'
// LoadingSpinner shows a spinning circle while data is loading
import LoadingSpinner from '../components/LoadingSpinner.vue'
// useToast shows pop-up messages
import { useToast } from '../composables/useToast.js'

// route gives us the current URL (we need it to get the job ID from the URL)
const route  = useRoute()
// router lets us go back to the previous page
const router = useRouter()
// toast shows pop-up messages
const toast  = useToast()

// loading = true while fetching data from the API
const loading       = ref(true)
// job holds the job data from the API (starts as null)
const job           = ref(null)
// alreadyApplied = true if the student has already applied to this job
const alreadyApplied= ref(false)
// modalOpen controls whether the Apply popup is visible
const modalOpen     = ref(false)
// coverLetter holds the text the student types in the cover letter field
const coverLetter   = ref('')
// submitting = true while the Apply request is being sent
const submitting    = ref(false)

// typePalette maps job types to Tailwind CSS color classes (for the badge)
const typePalette = {
  internship: 'bg-blue-100   text-blue-800',      // blue badge for internships
  fulltime:   'bg-emerald-100 text-emerald-800',   // green badge for full-time
  parttime:   'bg-orange-100 text-orange-800'      // orange badge for part-time
}
// typeLabels maps the raw type values to nice display text
const typeLabels = { internship: 'Internship', fulltime: 'Full-time', parttime: 'Part-time' }

// typeClasses picks the right CSS color class based on the job's type
const typeClasses = computed(() => typePalette[job.value?.type] ?? 'bg-slate-100 text-slate-700')
// typeLabel picks the nice display text based on the job's type
const typeLabel   = computed(() => typeLabels[job.value?.type] ?? job.value?.type)

// formattedDeadline converts the raw date (2026-06-25) to a nice format (June 25, 2026)
const formattedDeadline = computed(() => {
  if (!job.value?.deadline) return ''
  return new Date(job.value.deadline).toLocaleDateString(undefined, {
    year: 'numeric', month: 'long', day: 'numeric'
  })
})

// When the page loads, call the load() function to fetch the job data
onMounted(async () => {
  await load()
})

// load() fetches the job details and the student's applications (to check if already applied)
async function load () {
  loading.value = true
  try {
    // Get the job ID from the URL (e.g., /student/jobs/5 -> id = 5)
    const id = route.params.id
    // Fetch the job details AND the student's applications at the same time
    const [jobRes, appsRes] = await Promise.all([
      api.get(`/jobs/${id}`),   // GET /api/jobs/{id} — fetch this specific job
      api.get('/applications').catch(() => ({ data: { data: [] } }))  // GET /api/applications — fetch student's applications
    ])
    // Store the job data (or null if not found)
    job.value = jobRes.data?.data ?? null
    // Check if any of the student's applications match this job's ID
    const apps = appsRes.data?.data ?? []
    alreadyApplied.value = apps.some(a => Number(a.job_id) === Number(id))
  } catch (_e) {
    // If the API fails, show an error
    toast.error('Could not load job.')
  } finally {
    // Stop the loading spinner
    loading.value = false
  }
}

// submitApply is called when the student clicks "Submit application" in the popup
// CRUD: CREATE — sends POST /api/applications to create a new application
async function submitApply () {
  if (!job.value) return       // safety check
  submitting.value = true      // disable the button
  try {
    // Send POST /api/applications with the job ID and cover letter
    await api.post('/applications', {
      job_id: job.value.job_id,
      cover_letter: coverLetter.value.trim()
    })
    alreadyApplied.value = true   // mark as applied (hides the Apply button)
    modalOpen.value = false       // close the popup
    coverLetter.value = ''        // clear the cover letter
    toast.success('Application submitted!')  // show success message
  } catch (err) {
    // If the API returns an error, show it
    toast.error(err?.response?.data?.message || 'Could not submit application.')
  } finally {
    submitting.value = false  // re-enable the button
  }
}
</script>

<!--
  TEMPLATE = the HTML the student sees
-->
<template>
  <!-- Main container (narrower than BrowseJobs since this is a detail page) -->
  <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Back button — router.back() goes to the previous page -->
    <button class="text-sm text-slate-500 hover:text-slate-700 mb-4" @click="router.back()">
      ← Back
    </button>

    <!-- Show spinner while loading -->
    <LoadingSpinner v-if="loading" />

    <!-- If the job was not found, show a message -->
    <div v-else-if="!job" class="card p-12 text-center text-slate-500">
      <p>Job not found.</p>
    </div>

    <!-- If the job was found, show all the details -->
    <article v-else class="card p-6 sm:p-8">
      <!-- Header: job title, company, and a colored type badge -->
      <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">{{ job.title }}</h1>
          <p class="text-sm text-slate-500 mt-1">
            {{ job.company_name }} · {{ job.company_location }}
          </p>
        </div>
        <!-- Colored badge showing the job type (Internship / Full-time / Part-time) -->
        <span class="badge self-start" :class="typeClasses">{{ typeLabel }}</span>
      </header>

      <!-- Three info boxes: Deadline, Industry, Posted by -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 text-sm">
        <div class="rounded-lg bg-slate-50 px-4 py-3">
          <p class="text-slate-500">Deadline</p>
          <p class="font-medium text-slate-900">{{ formattedDeadline }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-4 py-3">
          <p class="text-slate-500">Industry</p>
          <p class="font-medium text-slate-900">{{ job.company_industry }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-4 py-3">
          <p class="text-slate-500">Posted by</p>
          <p class="font-medium text-slate-900">{{ job.posted_by_name }}</p>
        </div>
      </div>

      <!-- Job description section -->
      <section class="mt-6">
        <h2 class="text-base font-semibold text-slate-900">Description</h2>
        <!-- whitespace-pre-line keeps line breaks from the database -->
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ job.description }}</p>
      </section>

      <!-- Job requirements section (only shows if requirements exist) -->
      <section v-if="job.requirements" class="mt-6">
        <h2 class="text-base font-semibold text-slate-900">Requirements</h2>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ job.requirements }}</p>
      </section>

      <!-- Apply button or "Already applied" badge -->
      <div class="mt-8 flex justify-end">
        <!-- If not applied yet, show the Apply button -->
        <button v-if="!alreadyApplied" class="btn-primary" @click="modalOpen = true">Apply now</button>
        <!-- If already applied, show a green badge -->
        <span v-else class="badge bg-emerald-100 text-emerald-800">Already applied</span>
      </div>
    </article>

    <!-- Apply modal (popup) — same as BrowseJobs -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="modalOpen = false">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-lg w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">Apply for {{ job?.title }}</h3>
          <p class="text-sm text-slate-500">{{ job?.company_name }}</p>
          <label class="label mt-4">Cover letter (optional)</label>
          <textarea v-model="coverLetter" rows="6" class="input"
                    placeholder="Tell the recruiter why you're a great fit…" maxlength="5000" />
          <p class="text-xs text-slate-400 mt-1">{{ coverLetter.length }} / 5000</p>
          <div class="mt-5 flex justify-end gap-2">
            <button class="btn-secondary" @click="modalOpen = false" :disabled="submitting">Cancel</button>
            <button class="btn-primary" @click="submitApply" :disabled="submitting">
              {{ submitting ? 'Submitting…' : 'Submit application' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </section>
</template>

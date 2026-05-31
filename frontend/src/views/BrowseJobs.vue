<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api.js'
import JobCard from '../components/JobCard.vue'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'

const router = useRouter()
const toast  = useToast()

const loading      = ref(true)
const jobs         = ref([])
const appliedJobIds = ref(new Set())

const search       = ref('')
const debouncedQ   = ref('')
const typeFilter   = ref('all')

const modalOpen    = ref(false)
const modalJob     = ref(null)
const coverLetter  = ref('')
const submitting   = ref(false)

let debounceTimer = null
watch(search, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { debouncedQ.value = val.trim().toLowerCase() }, 400)
})

onMounted(async () => {
  try {
    const [jobsRes, appsRes] = await Promise.all([
      api.get('/jobs'),
      api.get('/applications').catch(() => ({ data: { data: [] } }))
    ])
    jobs.value = jobsRes.data?.data ?? []
    appliedJobIds.value = new Set((appsRes.data?.data ?? []).map(a => Number(a.job_id)))
  } catch (_e) {
    toast.error('Could not load jobs.')
  } finally {
    loading.value = false
  }
})

const filteredJobs = computed(() => {
  return jobs.value.filter(j => {
    if (typeFilter.value !== 'all' && j.type !== typeFilter.value) return false
    if (debouncedQ.value === '') return true
    const haystack = `${j.title} ${j.company_name}`.toLowerCase()
    return haystack.includes(debouncedQ.value)
  })
})

function openApply (job) {
  modalJob.value = job
  coverLetter.value = ''
  modalOpen.value = true
}
function closeModal () {
  modalOpen.value = false
  modalJob.value = null
  coverLetter.value = ''
}
async function submitApply () {
  if (!modalJob.value) return
  submitting.value = true
  try {
    await api.post('/applications', {
      job_id: modalJob.value.job_id,
      cover_letter: coverLetter.value.trim()
    })
    appliedJobIds.value.add(Number(modalJob.value.job_id))
    toast.success('Application submitted!')
    closeModal()
  } catch (err) {
    const msg = err?.response?.data?.message || 'Could not submit application.'
    toast.error(msg)
  } finally {
    submitting.value = false
  }
}

function viewDetail (job) {
  router.push(`/student/jobs/${job.job_id}`)
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">Browse jobs</h1>
      <p class="text-sm text-slate-500">Discover internships and full-time roles.</p>
    </header>

    <div class="card p-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.35-4.35" />
        </svg>
        <input v-model="search" type="text" placeholder="Search by title or company"
               class="input pl-9" />
      </div>
      <select v-model="typeFilter" class="input sm:w-48">
        <option value="all">All types</option>
        <option value="internship">Internship</option>
        <option value="fulltime">Full-time</option>
        <option value="parttime">Part-time</option>
      </select>
    </div>

    <LoadingSpinner v-if="loading" />

    <div v-else>
      <div v-if="filteredJobs.length === 0" class="card p-12 text-center text-slate-500">
        <svg class="mx-auto h-10 w-10 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 21l-4.35-4.35" /><circle cx="11" cy="11" r="7" />
        </svg>
        <p class="mt-3 text-sm">No jobs match your filters.</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <JobCard v-for="job in filteredJobs" :key="job.job_id" :job="job"
                 :already-applied="appliedJobIds.has(Number(job.job_id))"
                 @apply="openApply" @view-detail="viewDetail" />
      </div>
    </div>

    <!-- Apply modal -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-lg w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">Apply for {{ modalJob?.title }}</h3>
          <p class="text-sm text-slate-500">{{ modalJob?.company_name }}</p>
          <label class="label mt-4" for="cover">Cover letter (optional)</label>
          <textarea id="cover" v-model="coverLetter" rows="6" class="input"
                    placeholder="Tell the recruiter why you're a great fit…" maxlength="5000" />
          <p class="text-xs text-slate-400 mt-1">{{ coverLetter.length }} / 5000</p>
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

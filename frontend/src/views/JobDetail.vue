<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()

const loading       = ref(true)
const job           = ref(null)
const alreadyApplied= ref(false)
const modalOpen     = ref(false)
const coverLetter   = ref('')
const submitting    = ref(false)

const typePalette = {
  internship: 'bg-blue-100   text-blue-800',
  fulltime:   'bg-emerald-100 text-emerald-800',
  parttime:   'bg-orange-100 text-orange-800'
}
const typeLabels = { internship: 'Internship', fulltime: 'Full-time', parttime: 'Part-time' }

const typeClasses = computed(() => typePalette[job.value?.type] ?? 'bg-slate-100 text-slate-700')
const typeLabel   = computed(() => typeLabels[job.value?.type] ?? job.value?.type)

const formattedDeadline = computed(() => {
  if (!job.value?.deadline) return ''
  return new Date(job.value.deadline).toLocaleDateString(undefined, {
    year: 'numeric', month: 'long', day: 'numeric'
  })
})

onMounted(async () => {
  await load()
})

async function load () {
  loading.value = true
  try {
    const id = route.params.id
    const [jobRes, appsRes] = await Promise.all([
      api.get(`/jobs/${id}`),
      api.get('/applications').catch(() => ({ data: { data: [] } }))
    ])
    job.value = jobRes.data?.data ?? null
    const apps = appsRes.data?.data ?? []
    alreadyApplied.value = apps.some(a => Number(a.job_id) === Number(id))
  } catch (_e) {
    toast.error('Could not load job.')
  } finally {
    loading.value = false
  }
}

async function submitApply () {
  if (!job.value) return
  submitting.value = true
  try {
    await api.post('/applications', {
      job_id: job.value.job_id,
      cover_letter: coverLetter.value.trim()
    })
    alreadyApplied.value = true
    modalOpen.value = false
    coverLetter.value = ''
    toast.success('Application submitted!')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not submit application.')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <button class="text-sm text-slate-500 hover:text-slate-700 mb-4" @click="router.back()">
      ← Back
    </button>

    <LoadingSpinner v-if="loading" />

    <div v-else-if="!job" class="card p-12 text-center text-slate-500">
      <p>Job not found.</p>
    </div>

    <article v-else class="card p-6 sm:p-8">
      <header class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">{{ job.title }}</h1>
          <p class="text-sm text-slate-500 mt-1">
            {{ job.company_name }} · {{ job.company_location }}
          </p>
        </div>
        <span class="badge self-start" :class="typeClasses">{{ typeLabel }}</span>
      </header>

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

      <section class="mt-6">
        <h2 class="text-base font-semibold text-slate-900">Description</h2>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ job.description }}</p>
      </section>

      <section v-if="job.requirements" class="mt-6">
        <h2 class="text-base font-semibold text-slate-900">Requirements</h2>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-line">{{ job.requirements }}</p>
      </section>

      <div class="mt-8 flex justify-end">
        <button v-if="!alreadyApplied" class="btn-primary" @click="modalOpen = true">Apply now</button>
        <span v-else class="badge bg-emerald-100 text-emerald-800">Already applied</span>
      </div>
    </article>

    <!-- Apply modal -->
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

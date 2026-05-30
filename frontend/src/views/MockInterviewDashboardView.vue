<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'
import { useAuthStore } from '../stores/auth.js'

/**
 * Module 8 — Mock Interview Dashboard
 *
 * Students: view their personal bookings, reschedule domain, or cancel.
 * Admins: view all bookings and evaluate completed sessions.
 */

const toast = useToast()
const auth  = useAuthStore()

const loading    = ref(true)
const interviews = ref([])

// ---- Admin: evaluate modal ------------------------------------------
const evalOpen       = ref(false)
const evalSubmitting = ref(false)
const evalId         = ref(null)
const evalForm       = reactive({ score: '', feedback_text: '' })
const evalErrors     = reactive({ score: '', feedback_text: '' })

function openEval (id) {
  evalId.value = id
  evalForm.score         = ''
  evalForm.feedback_text = ''
  evalErrors.score         = ''
  evalErrors.feedback_text = ''
  evalOpen.value = true
}

function closeEval () {
  if (evalSubmitting.value) return
  evalOpen.value = false
}

// ---- Student: edit booking modal ------------------------------------
const bookingOpen       = ref(false)
const bookingSubmitting = ref(false)
const bookingId         = ref(null)
const bookingForm       = reactive({ job_category: '' })
const bookingErrors     = reactive({ job_category: '' })

const JOB_CATEGORIES = [
  'Software Engineering',
  'Data Science',
  'UI/UX Design',
  'Cybersecurity',
  'Product Management',
  'DevOps',
  'Other'
]

function openEditBooking (interview) {
  bookingId.value        = interview.interview_id
  bookingForm.job_category = interview.job_category
  bookingErrors.job_category = ''
  bookingOpen.value = true
}

function closeBooking () {
  if (bookingSubmitting.value) return
  bookingOpen.value = false
}

// ---- Confirm cancel -------------------------------------------------
const confirmOpen = ref(false)
const cancelTarget = ref(null)

function askCancel (id) {
  cancelTarget.value = id
  confirmOpen.value = true
}

// ---- Load -----------------------------------------------------------
onMounted(loadData)

async function loadData () {
  loading.value = true
  try {
    const endpoint = auth.isAdmin ? '/interviews/admin/manage' : '/interviews/mysessions'
    const { data } = await api.get(endpoint)
    interviews.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load interview sessions.')
  } finally {
    loading.value = false
  }
}

// ---- Filters --------------------------------------------------------
const statusFilter = ref('all')

const filteredInterviews = computed(() => {
  if (statusFilter.value === 'all') return interviews.value
  return interviews.value.filter(i => i.status === statusFilter.value)
})

const statusCounts = computed(() => {
  const c = { all: interviews.value.length, pending: 0, completed: 0, cancelled: 0 }
  interviews.value.forEach(i => { if (i.status in c) c[i.status]++ })
  return c
})

// ---- Admin evaluate -------------------------------------------------
function validateEval () {
  evalErrors.score         = ''
  evalErrors.feedback_text = ''
  const s = Number(evalForm.score)
  if (evalForm.score === '' || Number.isNaN(s)) {
    evalErrors.score = 'Score is required.'
  } else if (s < 0 || s > 100) {
    evalErrors.score = 'Score must be 0–100.'
  }
  if (!evalForm.feedback_text.trim()) {
    evalErrors.feedback_text = 'Feedback is required.'
  }
  return !evalErrors.score && !evalErrors.feedback_text
}

async function submitEval () {
  if (!validateEval()) return
  evalSubmitting.value = true
  try {
    const { data } = await api.put(`/interviews/admin/evaluate/${evalId.value}`, {
      score:         Number(evalForm.score),
      feedback_text: evalForm.feedback_text.trim()
    })
    const updated = data?.data
    if (updated) {
      const idx = interviews.value.findIndex(i => i.interview_id === evalId.value)
      if (idx !== -1) interviews.value[idx] = { ...interviews.value[idx], ...updated }
    }
    toast.success('Evaluation submitted.')
    evalOpen.value = false
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not submit evaluation.')
  } finally {
    evalSubmitting.value = false
  }
}

// ---- Student update booking -----------------------------------------
async function saveBooking () {
  bookingErrors.job_category = ''
  if (!bookingForm.job_category.trim()) {
    bookingErrors.job_category = 'Please select a domain.'
    return
  }
  bookingSubmitting.value = true
  try {
    const { data } = await api.put(`/interviews/bookings/${bookingId.value}`, {
      job_category: bookingForm.job_category
    })
    const updated = data?.data
    if (updated) {
      const idx = interviews.value.findIndex(i => i.interview_id === bookingId.value)
      if (idx !== -1) interviews.value[idx] = { ...interviews.value[idx], ...updated }
    }
    toast.success('Booking updated.')
    bookingOpen.value = false
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not update booking.')
  } finally {
    bookingSubmitting.value = false
  }
}

// ---- Student cancel booking -----------------------------------------
async function cancelBooking () {
  const id = cancelTarget.value
  confirmOpen.value = false
  cancelTarget.value = null
  try {
    await api.put(`/interviews/bookings/${id}`, { status: 'cancelled' })
    const idx = interviews.value.findIndex(i => i.interview_id === id)
    if (idx !== -1) interviews.value[idx].status = 'cancelled'
    toast.success('Booking cancelled.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not cancel booking.')
  }
}

// ---- Helpers ---------------------------------------------------------
function formatDateTime (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, {
    weekday: 'short', day: 'numeric', month: 'short',
    year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}

const statusClass = {
  pending:   'bg-amber-50 text-amber-700',
  completed: 'bg-emerald-50 text-emerald-700',
  cancelled: 'bg-slate-100 text-slate-500'
}
</script>

<template>
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">
          {{ auth.isAdmin ? 'All Interview Sessions' : 'My Mock Interviews' }}
        </h1>
        <p class="text-sm text-slate-500">
          {{ auth.isAdmin
            ? 'Manage bookings and submit evaluations for completed sessions.'
            : 'Track your mock interview bookings and view feedback.' }}
        </p>
      </div>
      <RouterLink v-if="auth.isStudent" to="/interview/slots" class="btn-secondary text-sm">
        Browse open slots →
      </RouterLink>
    </header>

    <!-- Status filter tabs -->
    <div class="flex gap-2 mb-5 flex-wrap">
      <button v-for="s in ['all', 'pending', 'completed', 'cancelled']" :key="s"
              class="px-3 py-1.5 rounded-full text-sm font-medium border transition-colors"
              :class="statusFilter === s
                ? 'bg-indigo-600 text-white border-indigo-600'
                : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'"
              @click="statusFilter = s">
        {{ s.charAt(0).toUpperCase() + s.slice(1) }}
        <span class="ml-1 text-xs opacity-70">({{ statusCounts[s] }})</span>
      </button>
    </div>

    <LoadingSpinner v-if="loading" />

    <div v-else-if="filteredInterviews.length === 0"
         class="card p-12 text-center text-slate-500">
      <svg class="mx-auto w-10 h-10 text-slate-300 mb-3" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5">
        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
        <rect x="9" y="3" width="6" height="4" rx="1"/>
        <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/>
      </svg>
      <p class="text-sm">No interviews {{ statusFilter !== 'all' ? `with status "${statusFilter}"` : '' }}.</p>
    </div>

    <div v-else class="space-y-4">
      <div v-for="iv in filteredInterviews" :key="iv.interview_id" class="card p-5">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
          <!-- Info -->
          <div class="space-y-1 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="badge text-xs" :class="statusClass[iv.status] ?? 'bg-slate-100 text-slate-600'">
                {{ iv.status }}
              </span>
              <span class="text-sm font-semibold text-slate-900">{{ iv.job_category }}</span>
            </div>
            <p class="text-sm text-slate-600">
              📅 {{ formatDateTime(iv.scheduled_at) }}
            </p>
            <p v-if="auth.isAdmin && iv.student_name" class="text-xs text-slate-500">
              Student: {{ iv.student_name }}
            </p>
            <p v-if="iv.score !== null && iv.score !== undefined"
               class="text-sm font-medium text-emerald-700">
              Score: {{ iv.score }} / 100
            </p>
            <p v-if="iv.feedback_text" class="text-sm text-slate-600 mt-1 italic">
              "{{ iv.feedback_text }}"
            </p>
          </div>

          <!-- Actions -->
          <div class="flex gap-2 flex-wrap sm:flex-col sm:items-end">
            <!-- Admin: evaluate pending sessions -->
            <button v-if="auth.isAdmin && iv.status === 'pending'"
                    class="btn-primary !py-1.5 !px-3 text-sm"
                    @click="openEval(iv.interview_id)">
              Evaluate
            </button>

            <!-- Student: edit domain (pending only) -->
            <button v-if="auth.isStudent && iv.status === 'pending'"
                    class="btn-secondary !py-1.5 !px-3 text-sm"
                    @click="openEditBooking(iv)">
              Edit domain
            </button>

            <!-- Student: cancel (pending only) -->
            <button v-if="auth.isStudent && iv.status === 'pending'"
                    class="btn-danger !py-1.5 !px-3 text-sm"
                    @click="askCancel(iv.interview_id)">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin: Evaluate modal -->
    <Transition name="fade">
      <div v-if="evalOpen"
           class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeEval">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-1">Submit Evaluation</h3>
          <p class="text-sm text-slate-500 mb-4">Provide a score and written feedback for this session.</p>

          <form @submit.prevent="submitEval" novalidate class="space-y-4">
            <div>
              <label class="label" for="eval-score">Score (0–100) <span class="text-red-500">*</span></label>
              <input id="eval-score" type="number" v-model="evalForm.score"
                     class="input" :class="{ 'input--error': evalErrors.score }"
                     min="0" max="100" placeholder="e.g. 78" />
              <p v-if="evalErrors.score" class="mt-1 text-sm text-red-500">{{ evalErrors.score }}</p>
            </div>
            <div>
              <label class="label" for="eval-feedback">Feedback <span class="text-red-500">*</span></label>
              <textarea id="eval-feedback" v-model="evalForm.feedback_text" rows="4"
                        class="input" :class="{ 'input--error': evalErrors.feedback_text }"
                        placeholder="Describe the student's performance…" />
              <p v-if="evalErrors.feedback_text" class="mt-1 text-sm text-red-500">
                {{ evalErrors.feedback_text }}
              </p>
            </div>
            <div class="flex justify-end gap-2 pt-1">
              <button type="button" class="btn-secondary" @click="closeEval" :disabled="evalSubmitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="evalSubmitting">
                {{ evalSubmitting ? 'Submitting…' : 'Submit' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- Student: Edit booking modal -->
    <Transition name="fade">
      <div v-if="bookingOpen"
           class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeBooking">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-sm w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-1">Edit Interview Domain</h3>
          <p class="text-sm text-slate-500 mb-4">Change the technical domain for your session.</p>

          <form @submit.prevent="saveBooking" novalidate class="space-y-4">
            <div>
              <label class="label" for="booking-cat">Domain <span class="text-red-500">*</span></label>
              <select id="booking-cat" v-model="bookingForm.job_category"
                      class="input" :class="{ 'input--error': bookingErrors.job_category }">
                <option value="">Select a domain…</option>
                <option v-for="cat in JOB_CATEGORIES" :key="cat" :value="cat">{{ cat }}</option>
              </select>
              <p v-if="bookingErrors.job_category" class="mt-1 text-sm text-red-500">
                {{ bookingErrors.job_category }}
              </p>
            </div>
            <div class="flex justify-end gap-2 pt-1">
              <button type="button" class="btn-secondary" @click="closeBooking" :disabled="bookingSubmitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="bookingSubmitting">
                {{ bookingSubmitting ? 'Saving…' : 'Save' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <ConfirmDialog
      :open="confirmOpen"
      title="Cancel this booking?"
      message="This will mark the session as cancelled. You can book another slot afterwards."
      confirm-text="Yes, cancel"
      @confirm="cancelBooking"
      @cancel="confirmOpen = false" />
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import StatusBadge from '../components/StatusBadge.vue'
import { useToast } from '../composables/useToast.js'

/**
 * Module 8 — Mock Interview & Technical Prep Scheduler (Owner: Monika)
 *
 * Admin-facing page with two sections:
 *   - Slots:    create open availability windows + delete unbooked ones
 *   - Bookings: see every student's booking and submit score + feedback
 */

const toast = useToast()

const tab = ref('slots')   // 'slots' | 'bookings'
const TABS = [
  { id: 'slots',    label: 'Slots' },
  { id: 'bookings', label: 'Bookings' }
]

const loadingSlots    = ref(false)
const loadingBookings = ref(false)
const slots    = ref([])
const bookings = ref([])

// ---- Create slot modal ------------------------------------------------
const slotModalOpen = ref(false)
const slotSubmitting = ref(false)
const slotForm   = reactive({ scheduled_at: '' })
const slotErrors = reactive({ scheduled_at: '' })

// ---- Evaluate booking modal ------------------------------------------
const evalModalOpen   = ref(false)
const evalSubmitting  = ref(false)
const evalBooking     = ref(null)
const evalForm   = reactive({ score: '', feedback_text: '', status: 'completed' })
const evalErrors = reactive({ score: '', feedback_text: '' })

onMounted(async () => {
  await Promise.all([loadSlots(), loadBookings()])
})

async function loadSlots () {
  loadingSlots.value = true
  try {
    // Pass `?all=1` so admins can see booked slots too.
    const { data } = await api.get('/interviews/slots?all=1')
    slots.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load slots.')
  } finally {
    loadingSlots.value = false
  }
}

async function loadBookings () {
  loadingBookings.value = true
  try {
    const { data } = await api.get('/interviews/admin/manage')
    bookings.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load bookings.')
  } finally {
    loadingBookings.value = false
  }
}

// ---- Slot creation ---------------------------------------------------
function openSlotModal () {
  slotForm.scheduled_at = ''
  slotErrors.scheduled_at = ''
  slotModalOpen.value = true
}

function closeSlotModal () {
  if (slotSubmitting.value) return
  slotModalOpen.value = false
}

async function submitSlot () {
  slotErrors.scheduled_at = ''
  if (!slotForm.scheduled_at) {
    slotErrors.scheduled_at = 'Please pick a date and time.'
    return
  }
  if (new Date(slotForm.scheduled_at).getTime() <= Date.now()) {
    slotErrors.scheduled_at = 'Date/time must be in the future.'
    return
  }

  slotSubmitting.value = true
  try {
    await api.post('/interviews/slots', {
      // The form value is in 'YYYY-MM-DDTHH:mm' format (datetime-local input).
      // The backend's strtotime() parses both that and ISO strings.
      scheduled_at: slotForm.scheduled_at
    })
    toast.success('Slot created.')
    slotModalOpen.value = false
    await loadSlots()
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors?.scheduled_at) slotErrors.scheduled_at = apiErrors.scheduled_at
    toast.error(err?.response?.data?.message || 'Could not create slot.')
  } finally {
    slotSubmitting.value = false
  }
}

async function deleteSlot (slot) {
  if (!confirm(`Delete the slot on ${formatDateTime(slot.scheduled_at)}?`)) return
  try {
    await api.delete(`/interviews/slots/${slot.slot_id}`)
    toast.success('Slot deleted.')
    await loadSlots()
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete slot.')
  }
}

// ---- Evaluation -------------------------------------------------------
function openEvalModal (booking) {
  evalBooking.value = booking
  evalForm.score          = booking.score ?? ''
  evalForm.feedback_text  = booking.feedback_text ?? ''
  evalForm.status         = booking.status === 'cancelled' ? 'cancelled' : 'completed'
  evalErrors.score = ''
  evalErrors.feedback_text = ''
  evalModalOpen.value = true
}

function closeEvalModal () {
  if (evalSubmitting.value) return
  evalModalOpen.value = false
}

function validateEval () {
  evalErrors.score = ''
  evalErrors.feedback_text = ''
  const n = Number(evalForm.score)
  if (evalForm.score === '' || Number.isNaN(n) || n < 0 || n > 100) {
    evalErrors.score = 'Score must be between 0 and 100.'
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
    await api.put(`/interviews/admin/evaluate/${evalBooking.value.interview_id}`, {
      score:         Number(evalForm.score),
      feedback_text: evalForm.feedback_text.trim(),
      status:        evalForm.status
    })
    toast.success('Booking evaluated.')
    evalModalOpen.value = false
    await loadBookings()
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors?.score)         evalErrors.score = apiErrors.score
    if (apiErrors?.feedback_text) evalErrors.feedback_text = apiErrors.feedback_text
    toast.error(err?.response?.data?.message || 'Could not save evaluation.')
  } finally {
    evalSubmitting.value = false
  }
}

// ---- Formatting -------------------------------------------------------
function formatDateTime (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const todayLocalISO = computed(() => {
  // Used as the `min` attribute on the datetime-local input.
  const d = new Date()
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().slice(0, 16)
})

const tabCount = computed(() => ({
  slots: slots.value.length,
  bookings: bookings.value.length
}))
</script>

<template>
  <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Manage mock interviews</h1>
        <p class="text-sm text-slate-500">
          Open new availability slots and evaluate completed sessions.
        </p>
      </div>
      <button class="btn-primary" @click="openSlotModal">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Add slot
      </button>
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
          <span class="ml-1 text-xs text-slate-400">{{ tabCount[t.id] }}</span>
        </button>
      </nav>

      <!-- ===== Slots ===== -->
      <div v-if="tab === 'slots'">
        <LoadingSpinner v-if="loadingSlots" />

        <div v-else-if="slots.length === 0" class="px-5 py-12 text-center text-slate-500">
          <p>No slots yet. Click <em>Add slot</em> to publish your first availability window.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="text-left px-5 py-3">Scheduled at</th>
                <th class="text-left px-5 py-3">Interviewer</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-right px-5 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in slots" :key="s.slot_id" class="border-t border-slate-100">
                <td class="px-5 py-3 text-slate-700">{{ formatDateTime(s.scheduled_at) }}</td>
                <td class="px-5 py-3 text-slate-600">{{ s.interviewer_name }}</td>
                <td class="px-5 py-3">
                  <span class="badge"
                        :class="s.is_booked
                          ? 'bg-amber-100 text-amber-700'
                          : 'bg-emerald-100 text-emerald-700'">
                    {{ s.is_booked ? 'Booked' : 'Open' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-right">
                  <button v-if="!s.is_booked"
                          class="text-xs text-red-600 hover:text-red-700"
                          @click="deleteSlot(s)">
                    Delete
                  </button>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ===== Bookings ===== -->
      <div v-else>
        <LoadingSpinner v-if="loadingBookings" />

        <div v-else-if="bookings.length === 0" class="px-5 py-12 text-center text-slate-500">
          <p>No bookings yet.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
              <tr>
                <th class="text-left px-5 py-3">Student</th>
                <th class="text-left px-5 py-3">When</th>
                <th class="text-left px-5 py-3">Category</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Score</th>
                <th class="text-right px-5 py-3">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in bookings" :key="m.interview_id" class="border-t border-slate-100">
                <td class="px-5 py-3 text-slate-700">{{ m.student_name }}</td>
                <td class="px-5 py-3 text-slate-600">{{ formatDateTime(m.scheduled_at) }}</td>
                <td class="px-5 py-3 text-slate-600">{{ m.job_category }}</td>
                <td class="px-5 py-3"><StatusBadge :status="m.status" /></td>
                <td class="px-5 py-3 text-slate-700">
                  {{ m.score === null || m.score === undefined ? '—' : `${m.score} / 100` }}
                </td>
                <td class="px-5 py-3 text-right">
                  <button class="btn-secondary !py-1 !px-2 text-xs"
                          :disabled="m.status === 'cancelled'"
                          @click="openEvalModal(m)">
                    {{ m.status === 'completed' ? 'Edit' : 'Evaluate' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===== New slot modal ===== -->
    <Transition name="fade">
      <div v-if="slotModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeSlotModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">New availability slot</h3>
          <p class="text-sm text-slate-500 mt-1">
            You'll be the interviewer for this slot.
          </p>

          <form @submit.prevent="submitSlot" novalidate class="space-y-4 mt-4">
            <div>
              <label class="label" for="slot-when">Scheduled date &amp; time</label>
              <input id="slot-when" v-model="slotForm.scheduled_at"
                     type="datetime-local" class="input"
                     :class="{ 'input--error': slotErrors.scheduled_at }"
                     :min="todayLocalISO" />
              <p v-if="slotErrors.scheduled_at" class="mt-1 text-sm text-red-500">
                {{ slotErrors.scheduled_at }}
              </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="btn-secondary" @click="closeSlotModal" :disabled="slotSubmitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="slotSubmitting">
                {{ slotSubmitting ? 'Saving…' : 'Create slot' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ===== Evaluate modal ===== -->
    <Transition name="fade">
      <div v-if="evalModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeEvalModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-lg w-full p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-semibold text-slate-900">Evaluate booking</h3>
          <p v-if="evalBooking" class="text-sm text-slate-500 mt-1">
            {{ evalBooking.student_name }} · {{ evalBooking.job_category }} ·
            {{ formatDateTime(evalBooking.scheduled_at) }}
          </p>

          <form @submit.prevent="submitEval" novalidate class="space-y-4 mt-4">
            <div>
              <label class="label" for="eval-score">Score (0 – 100)</label>
              <input id="eval-score" v-model="evalForm.score"
                     type="number" min="0" max="100" class="input"
                     :class="{ 'input--error': evalErrors.score }" />
              <p v-if="evalErrors.score" class="mt-1 text-sm text-red-500">
                {{ evalErrors.score }}
              </p>
            </div>
            <div>
              <label class="label" for="eval-feedback">Feedback</label>
              <textarea id="eval-feedback" v-model="evalForm.feedback_text"
                        rows="5" class="input" maxlength="5000"
                        :class="{ 'input--error': evalErrors.feedback_text }"
                        placeholder="Strengths, weaknesses, recommended next steps…" />
              <p v-if="evalErrors.feedback_text" class="mt-1 text-sm text-red-500">
                {{ evalErrors.feedback_text }}
              </p>
            </div>
            <div>
              <label class="label">Final status</label>
              <div class="flex items-center gap-4 text-sm">
                <label class="inline-flex items-center gap-1.5">
                  <input type="radio" value="completed" v-model="evalForm.status" />
                  Completed
                </label>
                <label class="inline-flex items-center gap-1.5">
                  <input type="radio" value="cancelled" v-model="evalForm.status" />
                  Cancelled
                </label>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="btn-secondary" @click="closeEvalModal" :disabled="evalSubmitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="evalSubmitting">
                {{ evalSubmitting ? 'Saving…' : 'Save evaluation' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </section>
</template>

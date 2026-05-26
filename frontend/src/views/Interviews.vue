<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import StatusBadge from '../components/StatusBadge.vue'
import { useToast } from '../composables/useToast.js'

/**
 * Module 8 — Mock Interview & Technical Prep Scheduler (Owner: Monika)
 *
 * Student-facing page with two tabs:
 *   - Available slots (open + future, from /api/interviews/slots)
 *   - My sessions     (the student's own bookings + scores/feedback)
 */

const toast = useToast()

const tab = ref('slots')   // 'slots' | 'sessions'
const TABS = [
  { id: 'slots',    label: 'Available slots' },
  { id: 'sessions', label: 'My sessions' }
]

const loadingSlots    = ref(false)
const loadingSessions = ref(false)
const slots    = ref([])
const sessions = ref([])

// ---- Booking modal (used for both new bookings and category edits) ----
const modalOpen   = ref(false)
const submitting  = ref(false)
// mode === 'book'   → create a new booking against a slot
// mode === 'edit'   → change job_category on an existing pending booking
const modalMode   = ref('book')
const modalSlot   = ref(null)
const modalSession = ref(null)
const form   = reactive({ job_category: '' })
const errors = reactive({ job_category: '' })

const JOB_CATEGORIES = [
  'Software Engineering',
  'Data Science',
  'UI/UX Design',
  'DevOps / SRE',
  'Cybersecurity',
  'Product Management',
  'Other'
]

onMounted(async () => {
  await Promise.all([loadSlots(), loadSessions()])
})

async function loadSlots () {
  loadingSlots.value = true
  try {
    const { data } = await api.get('/interviews/slots')
    slots.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load open slots.')
  } finally {
    loadingSlots.value = false
  }
}

async function loadSessions () {
  loadingSessions.value = true
  try {
    const { data } = await api.get('/interviews/my-sessions')
    sessions.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load your sessions.')
  } finally {
    loadingSessions.value = false
  }
}

// ---- Book a slot -----------------------------------------------------
function openBookModal (slot) {
  modalMode.value   = 'book'
  modalSlot.value   = slot
  modalSession.value = null
  form.job_category  = ''
  errors.job_category = ''
  modalOpen.value    = true
}

function openEditModal (session) {
  modalMode.value     = 'edit'
  modalSlot.value     = null
  modalSession.value  = session
  form.job_category   = session.job_category || ''
  errors.job_category = ''
  modalOpen.value     = true
}

function closeModal () {
  if (submitting.value) return
  modalOpen.value = false
}

function validate () {
  errors.job_category = ''
  if (!form.job_category.trim()) {
    errors.job_category = 'Please pick a target job category.'
    return false
  }
  if (form.job_category.length > 100) {
    errors.job_category = 'Job category must be 100 chars or fewer.'
    return false
  }
  return true
}

async function submitModal () {
  if (!validate()) return
  submitting.value = true

  try {
    if (modalMode.value === 'book') {
      await api.post('/interviews/bookings', {
        slot_id:      modalSlot.value.slot_id,
        job_category: form.job_category.trim()
      })
      toast.success('Slot booked.')
    } else {
      await api.put(`/interviews/bookings/${modalSession.value.interview_id}`, {
        job_category: form.job_category.trim()
      })
      toast.success('Booking updated.')
    }
    modalOpen.value = false
    await Promise.all([loadSlots(), loadSessions()])
    if (modalMode.value === 'book') tab.value = 'sessions'
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors?.job_category) errors.job_category = apiErrors.job_category
    toast.error(err?.response?.data?.message || 'Could not save booking.')
  } finally {
    submitting.value = false
  }
}

async function cancelBooking (session) {
  if (!confirm(`Cancel your booking on ${formatDateTime(session.scheduled_at)}?`)) return
  try {
    await api.put(`/interviews/bookings/${session.interview_id}`, { cancel: true })
    toast.success('Booking cancelled.')
    await Promise.all([loadSlots(), loadSessions()])
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not cancel booking.')
  }
}

// ---- Formatting ------------------------------------------------------
function formatDateTime (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, {
    weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}

const tabCount = computed(() => ({
  slots: slots.value.length,
  sessions: sessions.value.length
}))
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">Mock Interviews</h1>
      <p class="text-sm text-slate-500">
        Book a 1:1 mock technical interview with a career administrator and review your past scores + feedback.
      </p>
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

      <!-- ===== Slots tab ===== -->
      <div v-if="tab === 'slots'">
        <LoadingSpinner v-if="loadingSlots" />

        <div v-else-if="slots.length === 0" class="px-5 py-12 text-center text-slate-500">
          <p>No open slots right now. Check back soon!</p>
        </div>

        <ul v-else class="divide-y divide-slate-100">
          <li v-for="s in slots" :key="s.slot_id"
              class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <p class="font-medium text-slate-900">{{ formatDateTime(s.scheduled_at) }}</p>
              <p class="text-xs text-slate-500">Interviewer: {{ s.interviewer_name }}</p>
            </div>
            <button class="btn-primary !py-1.5 !px-3 text-sm w-full sm:w-auto"
                    @click="openBookModal(s)">
              Book this slot
            </button>
          </li>
        </ul>
      </div>

      <!-- ===== Sessions tab ===== -->
      <div v-else>
        <LoadingSpinner v-if="loadingSessions" />

        <div v-else-if="sessions.length === 0" class="px-5 py-12 text-center text-slate-500">
          <p>You haven't booked any mock interviews yet.</p>
        </div>

        <ul v-else class="divide-y divide-slate-100">
          <li v-for="m in sessions" :key="m.interview_id" class="px-5 py-4 space-y-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
              <div>
                <p class="font-medium text-slate-900">{{ formatDateTime(m.scheduled_at) }}</p>
                <p class="text-xs text-slate-500">
                  Category: {{ m.job_category }} · Interviewer: {{ m.interviewer_name }}
                </p>
              </div>
              <div class="flex items-center gap-2">
                <StatusBadge :status="m.status" />
                <button v-if="m.status === 'pending'"
                        class="btn-secondary !py-1 !px-2 text-xs"
                        @click="openEditModal(m)">
                  Edit
                </button>
                <button v-if="m.status === 'pending'"
                        class="btn-danger !py-1 !px-2 text-xs"
                        @click="cancelBooking(m)">
                  Cancel
                </button>
              </div>
            </div>

            <div v-if="m.status === 'completed'" class="rounded-md bg-slate-50 px-3 py-3 mt-1">
              <p class="text-xs text-slate-500 mb-1">
                <span class="font-semibold text-slate-700">Score:</span>
                <span class="ml-1 text-slate-900">{{ m.score ?? '—' }} / 100</span>
              </p>
              <p class="text-xs text-slate-500 whitespace-pre-wrap">
                <span class="font-semibold text-slate-700">Feedback:</span>
                {{ m.feedback_text || 'No feedback recorded.' }}
              </p>
            </div>
          </li>
        </ul>
      </div>
    </div>

    <!-- ===== Booking modal ===== -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">
            {{ modalMode === 'book' ? 'Book mock interview' : 'Edit booking' }}
          </h3>
          <p v-if="modalMode === 'book' && modalSlot" class="text-sm text-slate-500 mt-1">
            {{ formatDateTime(modalSlot.scheduled_at) }} · {{ modalSlot.interviewer_name }}
          </p>
          <p v-else-if="modalSession" class="text-sm text-slate-500 mt-1">
            {{ formatDateTime(modalSession.scheduled_at) }} · {{ modalSession.interviewer_name }}
          </p>

          <form @submit.prevent="submitModal" novalidate class="space-y-4 mt-4">
            <div>
              <label class="label" for="booking-cat">Target job category</label>
              <select id="booking-cat" v-model="form.job_category" class="input"
                      :class="{ 'input--error': errors.job_category }">
                <option value="">Select…</option>
                <option v-for="c in JOB_CATEGORIES" :key="c" :value="c">{{ c }}</option>
              </select>
              <p v-if="errors.job_category" class="mt-1 text-sm text-red-500">
                {{ errors.job_category }}
              </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="btn-secondary" @click="closeModal" :disabled="submitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Saving…' : (modalMode === 'book' ? 'Book' : 'Save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </section>
</template>

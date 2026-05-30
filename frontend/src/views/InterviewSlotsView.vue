<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'
import { useAuthStore } from '../stores/auth.js'

/**
 * Module 8 — Interview Slots (Admin view)
 * Admins can create open availability slots and delete unbooked ones.
 * All logged-in users can view available slots.
 */

const toast = useToast()
const auth  = useAuthStore()

const loading   = ref(true)
const slots     = ref([])
const filterUpcoming = ref(true)

// ---- Create slot modal (admin only) ---------------------------------
const modalOpen  = ref(false)
const submitting = ref(false)
const form       = reactive({ scheduled_at: '' })
const errors     = reactive({ scheduled_at: '' })

function openModal () {
  form.scheduled_at = ''
  errors.scheduled_at = ''
  modalOpen.value = true
}

function closeModal () {
  if (submitting.value) return
  modalOpen.value = false
}

// ---- Confirm delete dialog ------------------------------------------
const confirmOpen  = ref(false)
const targetSlotId = ref(null)

function askDelete (id) {
  targetSlotId.value = id
  confirmOpen.value = true
}

// ---- Load ------------------------------------------------------------
onMounted(loadSlots)

async function loadSlots () {
  loading.value = true
  try {
    const { data } = await api.get('/interviews/slots')
    slots.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load interview slots.')
  } finally {
    loading.value = false
  }
}

// ---- Filtered slots --------------------------------------------------
const filteredSlots = computed(() => {
  if (!filterUpcoming.value) return slots.value
  const now = new Date()
  return slots.value.filter(s => new Date(s.scheduled_at) >= now)
})

// ---- Validate --------------------------------------------------------
function validate () {
  errors.scheduled_at = ''
  if (!form.scheduled_at) {
    errors.scheduled_at = 'Date and time are required.'
    return false
  }
  if (new Date(form.scheduled_at) <= new Date()) {
    errors.scheduled_at = 'Slot must be in the future.'
    return false
  }
  return true
}

// ---- Create slot -----------------------------------------------------
async function createSlot () {
  if (!validate()) return
  submitting.value = true
  try {
    await api.post('/interviews/slots', { scheduled_at: form.scheduled_at })
    toast.success('Slot created!')
    modalOpen.value = false
    await loadSlots()
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not create slot.')
  } finally {
    submitting.value = false
  }
}

// ---- Delete slot -----------------------------------------------------
async function deleteSlot () {
  const id = targetSlotId.value
  confirmOpen.value = false
  targetSlotId.value = null
  try {
    await api.delete(`/interviews/slots/${id}`)
    slots.value = slots.value.filter(s => s.slot_id !== id)
    toast.success('Slot deleted.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete slot.')
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

// min attribute for datetime-local input
const minDateTime = computed(() => {
  const d = new Date()
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
  return d.toISOString().slice(0, 16)
})
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Interview Slots</h1>
        <p class="text-sm text-slate-500">
          {{ auth.isAdmin ? 'Manage open availability windows for mock interviews.' : 'Browse available mock interview slots.' }}
        </p>
      </div>
      <button v-if="auth.isAdmin" class="btn-primary" @click="openModal">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Add slot
      </button>
    </header>

    <!-- Filter toggle -->
    <div class="flex items-center gap-3 mb-4">
      <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
        <input type="checkbox" v-model="filterUpcoming" class="rounded border-slate-300" />
        Show upcoming only
      </label>
      <span class="text-xs text-slate-400">
        {{ filteredSlots.length }} slot{{ filteredSlots.length !== 1 ? 's' : '' }}
      </span>
    </div>

    <LoadingSpinner v-if="loading" />

    <div v-else-if="filteredSlots.length === 0"
         class="card p-12 text-center text-slate-500">
      <svg class="mx-auto w-10 h-10 text-slate-300 mb-3" viewBox="0 0 24 24" fill="none"
           stroke="currentColor" stroke-width="1.5">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
        <line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
      <p class="text-sm">No slots available.</p>
      <p v-if="auth.isAdmin" class="text-xs mt-1">Click "Add slot" to create one.</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="slot in filteredSlots" :key="slot.slot_id"
           class="card p-4 flex flex-col gap-3">
        <!-- Status badge -->
        <div class="flex items-start justify-between">
          <span
            class="badge text-xs"
            :class="slot.is_booked
              ? 'bg-amber-50 text-amber-700'
              : 'bg-emerald-50 text-emerald-700'">
            {{ slot.is_booked ? 'Booked' : 'Available' }}
          </span>
          <button v-if="auth.isAdmin && !slot.is_booked"
                  class="text-xs text-red-500 hover:text-red-700"
                  @click="askDelete(slot.slot_id)">
            Delete
          </button>
        </div>

        <!-- Date/time -->
        <div>
          <p class="text-sm font-semibold text-slate-900">
            {{ formatDateTime(slot.scheduled_at) }}
          </p>
          <p v-if="slot.interviewer_name" class="text-xs text-slate-500 mt-1">
            Interviewer: {{ slot.interviewer_name }}
          </p>
        </div>

        <!-- Book button for students -->
        <RouterLink v-if="auth.isStudent && !slot.is_booked"
                    :to="`/interview/book/${slot.slot_id}`"
                    class="btn-primary text-center text-sm">
          Book this slot
        </RouterLink>
        <p v-else-if="auth.isStudent && slot.is_booked"
           class="text-xs text-slate-400 italic">Already booked</p>
      </div>
    </div>

    <!-- Create slot modal -->
    <Transition name="fade">
      <div v-if="modalOpen"
           class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900 mb-1">Add Interview Slot</h3>
          <p class="text-sm text-slate-500 mb-4">Choose a date and time for the availability window.</p>

          <form @submit.prevent="createSlot" novalidate class="space-y-4">
            <div>
              <label class="label" for="slot-dt">
                Date &amp; Time <span class="text-red-500">*</span>
              </label>
              <input id="slot-dt" type="datetime-local" v-model="form.scheduled_at"
                     class="input" :class="{ 'input--error': errors.scheduled_at }"
                     :min="minDateTime" />
              <p v-if="errors.scheduled_at" class="mt-1 text-sm text-red-500">
                {{ errors.scheduled_at }}
              </p>
            </div>

            <div class="flex justify-end gap-2 pt-1">
              <button type="button" class="btn-secondary" @click="closeModal" :disabled="submitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Creating…' : 'Create slot' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <ConfirmDialog
      :open="confirmOpen"
      title="Delete this slot?"
      message="This will permanently remove the slot. This action cannot be undone."
      confirm-text="Delete"
      @confirm="deleteSlot"
      @cancel="confirmOpen = false" />
  </section>
</template>

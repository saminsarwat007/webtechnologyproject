<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api.js'
import { useToast } from '../composables/useToast.js'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()

const slotId  = Number(route.params.slotId)
const loading = ref(true)
const slot    = ref(null)
const submitting = ref(false)

const JOB_CATEGORIES = [
  'Software Engineering', 'Data Science', 'UI/UX Design',
  'Cybersecurity', 'Product Management', 'DevOps', 'Other'
]

const form   = reactive({ job_category: '' })
const errors = reactive({ job_category: '' })

onMounted(async () => {
  try {
    const { data } = await api.get('/interviews/slots')
    slot.value = (data?.data ?? []).find(s => s.slot_id === slotId) ?? null
    if (!slot.value) {
      toast.error('Slot not found.')
      router.push('/interview/slots')
    }
  } catch (_e) {
    toast.error('Could not load slot.')
    router.push('/interview/slots')
  } finally {
    loading.value = false
  }
})

async function book () {
  errors.job_category = ''
  if (!form.job_category) {
    errors.job_category = 'Please select a domain.'
    return
  }
  submitting.value = true
  try {
    await api.post('/interviews/bookings', {
      slot_id:      slotId,
      job_category: form.job_category
    })
    toast.success('Slot booked! Good luck with your interview.')
    router.push('/interview/dashboard')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not book slot.')
  } finally {
    submitting.value = false
  }
}

function formatDateTime (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  return d.toLocaleString(undefined, {
    weekday: 'long', day: 'numeric', month: 'long',
    year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}
</script>

<template>
  <section class="max-w-lg mx-auto px-4 sm:px-6 py-8">
    <button class="text-sm text-slate-500 hover:text-slate-700 mb-4"
            @click="router.push('/interview/slots')">
      ← Back to slots
    </button>

    <div class="card p-6">
      <h1 class="text-xl font-semibold text-slate-900 mb-1">Book a Mock Interview</h1>

      <div v-if="slot" class="mb-5 p-3 bg-indigo-50 rounded-lg text-sm text-indigo-800">
        📅 {{ formatDateTime(slot.scheduled_at) }}
      </div>

      <form @submit.prevent="book" novalidate class="space-y-4">
        <div>
          <label class="label" for="cat">
            Technical Domain <span class="text-red-500">*</span>
          </label>
          <select id="cat" v-model="form.job_category"
                  class="input" :class="{ 'input--error': errors.job_category }">
            <option value="">Select a domain…</option>
            <option v-for="c in JOB_CATEGORIES" :key="c" :value="c">{{ c }}</option>
          </select>
          <p v-if="errors.job_category" class="mt-1 text-sm text-red-500">{{ errors.job_category }}</p>
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="submitting">
          {{ submitting ? 'Booking…' : 'Confirm booking' }}
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'
import { useAuthStore } from '../stores/auth.js'

const auth  = useAuthStore()
const toast = useToast()

const loading = ref(true)
const saving  = ref(false)

const form = reactive({
  matric_no: '',
  programme: '',
  cgpa: '',
  skills: '',
  resume_text: ''
})
const errors = reactive({
  matric_no: '', programme: '', cgpa: '', skills: '', resume_text: ''
})

onMounted(async () => {
  try {
    const { data } = await api.get('/profile')
    const p = data?.data
    if (p) {
      form.matric_no   = p.matric_no    ?? ''
      form.programme   = p.programme    ?? ''
      form.cgpa        = p.cgpa != null ? String(p.cgpa) : ''
      form.skills      = p.skills       ?? ''
      form.resume_text = p.resume_text  ?? ''
    }
  } catch (err) {
    if (err?.response?.status !== 404) {
      toast.error('Could not load profile.')
    }
  } finally {
    loading.value = false
  }
})

function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')

  if (!form.matric_no.trim())            errors.matric_no = 'Matric number is required.'
  else if (form.matric_no.length > 20)   errors.matric_no = 'Matric number must be 20 chars or fewer.'

  if (!form.programme.trim())            errors.programme = 'Programme is required.'
  else if (form.programme.length > 100)  errors.programme = 'Programme must be 100 chars or fewer.'

  if (form.cgpa !== '') {
    const n = Number(form.cgpa)
    if (Number.isNaN(n))         errors.cgpa = 'CGPA must be a number.'
    else if (n < 0 || n > 4)     errors.cgpa = 'CGPA must be between 0.00 and 4.00.'
  }

  return Object.values(errors).every(v => v === '')
}

async function save () {
  if (!validate()) return
  saving.value = true
  try {
    const payload = {
      matric_no:   form.matric_no.trim(),
      programme:   form.programme.trim(),
      cgpa:        form.cgpa === '' ? null : Number(form.cgpa),
      skills:      form.skills.trim(),
      resume_text: form.resume_text.trim()
    }
    await api.put('/profile', payload)
    toast.success('Profile saved.')
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors && typeof apiErrors === 'object') {
      Object.keys(apiErrors).forEach(k => { if (k in errors) errors[k] = apiErrors[k] })
    }
    toast.error(err?.response?.data?.message || 'Could not save profile.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">My profile</h1>
      <p class="text-sm text-slate-500">Recruiters see this when you apply for a job.</p>
    </header>

    <LoadingSpinner v-if="loading" />

    <form v-else class="card p-6 space-y-5" @submit.prevent="save" novalidate>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label">Full name</label>
          <input class="input" :value="auth.user?.full_name" disabled />
        </div>
        <div>
          <label class="label">Email</label>
          <input class="input" :value="auth.user?.email" disabled />
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="label" for="matric_no">Matric number</label>
          <input id="matric_no" v-model="form.matric_no" class="input" :class="{ 'input--error': errors.matric_no }" placeholder="A22EC4040" />
          <p v-if="errors.matric_no" class="mt-1 text-sm text-red-500">{{ errors.matric_no }}</p>
        </div>
        <div>
          <label class="label" for="programme">Programme</label>
          <input id="programme" v-model="form.programme" class="input" :class="{ 'input--error': errors.programme }" placeholder="Bachelor of Computer Science (Software Engineering)" />
          <p v-if="errors.programme" class="mt-1 text-sm text-red-500">{{ errors.programme }}</p>
        </div>
      </div>

      <div>
        <label class="label" for="cgpa">CGPA <span class="text-slate-400">(0.00 – 4.00)</span></label>
        <input id="cgpa" v-model="form.cgpa" type="number" step="0.01" min="0" max="4"
               class="input sm:w-40" :class="{ 'input--error': errors.cgpa }" placeholder="3.50" />
        <p v-if="errors.cgpa" class="mt-1 text-sm text-red-500">{{ errors.cgpa }}</p>
      </div>

      <div>
        <label class="label" for="skills">Skills <span class="text-slate-400">(comma separated)</span></label>
        <input id="skills" v-model="form.skills" class="input" placeholder="Vue.js, PHP, MySQL, Git" />
      </div>

      <div>
        <label class="label" for="resume_text">Resume summary</label>
        <textarea id="resume_text" v-model="form.resume_text" rows="6" class="input"
                  placeholder="A short paragraph recruiters will read first." />
      </div>

      <div class="flex justify-end">
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? 'Saving…' : 'Save profile' }}
        </button>
      </div>
    </form>
  </section>
</template>

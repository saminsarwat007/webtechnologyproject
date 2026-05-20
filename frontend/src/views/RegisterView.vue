<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import { useToast } from '../composables/useToast.js'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const form = reactive({
  full_name: '',
  email: '',
  password: '',
  confirm_password: '',
  role: 'student'
})
const errors = reactive({
  full_name: '', email: '', password: '', confirm_password: '', role: ''
})
const formError = ref('')
const loading   = ref(false)

function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')

  if (!form.full_name.trim())               errors.full_name = 'Full name is required.'
  else if (form.full_name.length > 100)     errors.full_name = 'Full name must be 100 characters or fewer.'

  if (!form.email)                          errors.email = 'Email is required.'
  else if (!/^\S+@\S+\.\S+$/.test(form.email)) errors.email = 'Enter a valid email.'

  if (!form.password)                       errors.password = 'Password is required.'
  else if (form.password.length < 8)        errors.password = 'Password must be at least 8 characters.'

  if (!form.confirm_password)               errors.confirm_password = 'Please confirm your password.'
  else if (form.confirm_password !== form.password) errors.confirm_password = 'Passwords do not match.'

  if (!['student', 'admin'].includes(form.role)) errors.role = 'Choose a valid role.'

  return Object.values(errors).every(v => v === '')
}

async function submit () {
  formError.value = ''
  if (!validate()) return
  loading.value = true
  try {
    const user = await auth.register({
      full_name: form.full_name.trim(),
      email:     form.email.trim().toLowerCase(),
      password:  form.password,
      role:      form.role
    })
    toast.success('Account created. Welcome to CareerBridge!')
    router.push(user.role === 'student' ? '/student/dashboard' : '/admin/dashboard')
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors && typeof apiErrors === 'object') {
      Object.keys(apiErrors).forEach(k => { if (k in errors) errors[k] = apiErrors[k] })
    }
    formError.value = err?.response?.data?.message || 'Could not create your account. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="min-h-screen flex items-center justify-center px-4 py-10 bg-gradient-to-br from-brand-50 to-white">
    <div class="card w-full max-w-md p-8">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Create your account</h1>
        <p class="text-sm text-slate-500">Join CareerBridge in under a minute</p>
      </div>

      <form @submit.prevent="submit" novalidate class="space-y-4">
        <div>
          <label class="label" for="full_name">Full name</label>
          <input id="full_name" v-model="form.full_name" type="text"
                 class="input" :class="{ 'input--error': errors.full_name }" placeholder="Jane Doe" />
          <p v-if="errors.full_name" class="mt-1 text-sm text-red-500">{{ errors.full_name }}</p>
        </div>
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" v-model="form.email" type="email" autocomplete="email"
                 class="input" :class="{ 'input--error': errors.email }" placeholder="you@student.utm.my" />
          <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="label" for="password">Password</label>
            <input id="password" v-model="form.password" type="password" autocomplete="new-password"
                   class="input" :class="{ 'input--error': errors.password }" placeholder="At least 8 chars" />
            <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
          </div>
          <div>
            <label class="label" for="confirm_password">Confirm password</label>
            <input id="confirm_password" v-model="form.confirm_password" type="password" autocomplete="new-password"
                   class="input" :class="{ 'input--error': errors.confirm_password }" placeholder="Repeat password" />
            <p v-if="errors.confirm_password" class="mt-1 text-sm text-red-500">{{ errors.confirm_password }}</p>
          </div>
        </div>
        <div>
          <label class="label">I am a</label>
          <div class="grid grid-cols-2 gap-2">
            <label class="cursor-pointer rounded-lg border px-3 py-2 text-sm flex items-center gap-2"
                   :class="form.role === 'student' ? 'border-brand-500 bg-brand-50' : 'border-slate-300'">
              <input type="radio" v-model="form.role" value="student" class="text-brand-600" />
              Student
            </label>
            <label class="cursor-pointer rounded-lg border px-3 py-2 text-sm flex items-center gap-2"
                   :class="form.role === 'admin' ? 'border-brand-500 bg-brand-50' : 'border-slate-300'">
              <input type="radio" v-model="form.role" value="admin" class="text-brand-600" />
              Admin
            </label>
          </div>
          <p v-if="errors.role" class="mt-1 text-sm text-red-500">{{ errors.role }}</p>
        </div>

        <p v-if="formError" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {{ formError }}
        </p>

        <button type="submit" class="btn-primary w-full" :disabled="loading">
          {{ loading ? 'Creating…' : 'Create account' }}
        </button>
      </form>

      <p class="text-sm text-slate-500 text-center mt-6">
        Already have an account?
        <RouterLink to="/login" class="text-brand-600 hover:text-brand-700 font-medium">Sign in</RouterLink>
      </p>
    </div>
  </section>
</template>

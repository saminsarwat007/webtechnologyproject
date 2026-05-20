<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const auth   = useAuthStore()
const route  = useRoute()
const router = useRouter()

const form   = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const formError = ref('')
const loading   = ref(false)

function validate () {
  errors.email = ''
  errors.password = ''
  if (!form.email)                                   errors.email = 'Email is required.'
  else if (!/^\S+@\S+\.\S+$/.test(form.email))       errors.email = 'Enter a valid email.'
  if (!form.password)                                errors.password = 'Password is required.'
  else if (form.password.length < 8)                 errors.password = 'Password must be at least 8 characters.'
  return !errors.email && !errors.password
}

async function submit () {
  formError.value = ''
  if (!validate()) return
  loading.value = true
  try {
    const user = await auth.login(form.email.trim().toLowerCase(), form.password)
    const dest = route.query.redirect
      || (user.role === 'student' ? '/student/dashboard' : '/admin/dashboard')
    router.push(dest)
  } catch (err) {
    formError.value = err?.response?.data?.message || 'Could not log you in. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <section class="min-h-screen flex items-center justify-center px-4 py-10 bg-gradient-to-br from-brand-50 to-white">
    <div class="card w-full max-w-md p-8">
      <div class="text-center mb-6">
        <div class="inline-flex w-12 h-12 rounded-xl bg-brand-600 items-center justify-center text-white mb-3">
          <svg viewBox="0 0 24 24" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 14h16M8 8l-4 4 4 4M16 8l4 4-4 4" />
          </svg>
        </div>
        <h1 class="text-2xl font-semibold text-slate-900">Welcome back</h1>
        <p class="text-sm text-slate-500">Sign in to your CareerBridge account</p>
      </div>

      <form @submit.prevent="submit" novalidate class="space-y-4">
        <div>
          <label class="label" for="email">Email</label>
          <input id="email" v-model="form.email" type="email" autocomplete="email"
                 class="input" :class="{ 'input--error': errors.email }" placeholder="you@student.utm.my" />
          <p v-if="errors.email" class="mt-1 text-sm text-red-500">{{ errors.email }}</p>
        </div>
        <div>
          <label class="label" for="password">Password</label>
          <input id="password" v-model="form.password" type="password" autocomplete="current-password"
                 class="input" :class="{ 'input--error': errors.password }" placeholder="••••••••" />
          <p v-if="errors.password" class="mt-1 text-sm text-red-500">{{ errors.password }}</p>
        </div>

        <p v-if="formError" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
          {{ formError }}
        </p>

        <button type="submit" class="btn-primary w-full" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>

      <p class="text-sm text-slate-500 text-center mt-6">
        New to CareerBridge?
        <RouterLink to="/register" class="text-brand-600 hover:text-brand-700 font-medium">Create an account</RouterLink>
      </p>
    </div>
  </section>
</template>

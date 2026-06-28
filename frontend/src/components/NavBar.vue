<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

const auth   = useAuthStore()
const router = useRouter()
const open   = ref(false)

const links = computed(() => {
  if (auth.isStudent) {
    return [
      { to: '/student/dashboard',    label: 'Dashboard' },
      { to: '/student/jobs',         label: 'Browse Jobs' },
      { to: '/student/applications', label: 'My Applications' },
      { to: '/forum',                label: 'Forum' },
      { to: '/interview/slots',      label: 'Interview Slots' },
      { to: '/interview/dashboard',  label: 'My Interviews' },
      { to: '/student/profile',      label: 'Profile' }
    ]
  }
  if (auth.isAdmin) {
    const items = [
      { to: '/admin/dashboard',    label: 'Dashboard' },
      { to: '/admin/jobs',         label: 'Manage Jobs' },
      { to: '/admin/applications', label: 'Applications' },
      { to: '/admin/companies',    label: 'Companies' },
      { to: '/forum',              label: 'Forum' },
      { to: '/interview/slots',    label: 'Interview Slots' },
      { to: '/interview/dashboard',label: 'All Sessions' },
      { to: '/admin/labels',       label: 'Labels' }
    ]
    if (auth.isSuperAdmin) {
      items.push({ to: '/admin/users', label: 'Users' })
    }
    return items
  }
  return []
})

async function handleLogout () {
  await auth.logout(false)
  router.push('/login')
  open.value = false
}
</script>

<template>
  <header class="bg-white border-b border-slate-200 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Logo -->
        <RouterLink to="/" class="flex items-center gap-2 text-indigo-600 font-bold text-lg">
          <span class="inline-flex w-8 h-8 rounded-lg bg-indigo-600 items-center justify-center text-white">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
              <path d="M4 14h16M8 8l-4 4 4 4M16 8l4 4-4 4"/>
            </svg>
          </span>
          CareerBridge
        </RouterLink>

        <!-- Desktop links -->
        <nav class="hidden md:flex items-center gap-1">
          <RouterLink v-for="l in links" :key="l.to" :to="l.to"
            class="px-3 py-2 rounded-md text-sm font-medium text-slate-600 hover:text-indigo-700 hover:bg-indigo-50"
            active-class="text-indigo-700 bg-indigo-50">
            {{ l.label }}
          </RouterLink>
          <span class="ml-3 text-sm text-slate-500">{{ auth.user?.full_name }}</span>
          <button @click="handleLogout" class="ml-3 btn-secondary !py-1.5 !px-3 text-sm">Logout</button>
        </nav>

        <!-- Mobile toggler -->
        <button class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-slate-600 hover:bg-slate-100"
                @click="open = !open" :aria-expanded="open" aria-label="Toggle navigation">
          <svg v-if="!open" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          <svg v-else class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>

      <!-- Mobile menu -->
      <div v-if="open" class="md:hidden pb-3 space-y-1">
        <RouterLink v-for="l in links" :key="l.to" :to="l.to" @click="open = false"
          class="block px-3 py-2 rounded-md text-base font-medium text-slate-700 hover:bg-indigo-50 hover:text-indigo-700"
          active-class="text-indigo-700 bg-indigo-50">
          {{ l.label }}
        </RouterLink>
        <div class="border-t border-slate-200 pt-3 px-3">
          <p class="text-sm text-slate-500 mb-2">{{ auth.user?.full_name }}</p>
          <button @click="handleLogout" class="btn-secondary w-full">Logout</button>
        </div>
      </div>
    </div>
  </header>
</template>

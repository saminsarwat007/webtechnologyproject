<script setup>
import { computed, onMounted, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'

const toast = useToast()
const loading = ref(true)
const users   = ref([])
const search  = ref('')

const palette = {
  student:    'bg-slate-100 text-slate-700',
  admin:      'bg-blue-100 text-blue-800',
  superadmin: 'bg-purple-100 text-purple-800'
}

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/users')
    users.value = data?.data ?? []
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not load users.')
  } finally {
    loading.value = false
  }
})

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return users.value
  return users.value.filter(u =>
    u.full_name.toLowerCase().includes(q) ||
    u.email.toLowerCase().includes(q)
  )
})

function formatDate (d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header>
      <h1 class="text-2xl font-semibold text-slate-900">Users</h1>
      <p class="text-sm text-slate-500">Read-only directory of every CareerBridge account.</p>
    </header>

    <div class="card p-4">
      <div class="relative max-w-md">
        <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.35-4.35" />
        </svg>
        <input v-model="search" type="text" placeholder="Search by name or email" class="input pl-9" />
      </div>
    </div>

    <LoadingSpinner v-if="loading" />

    <div v-else class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
          <tr>
            <th class="text-left px-5 py-3">ID</th>
            <th class="text-left px-5 py-3">Full Name</th>
            <th class="text-left px-5 py-3">Email</th>
            <th class="text-left px-5 py-3">Role</th>
            <th class="text-left px-5 py-3">Joined</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filtered.length === 0">
            <td colspan="5" class="px-5 py-12 text-center text-slate-500">No users match your search.</td>
          </tr>
          <tr v-for="u in filtered" :key="u.user_id" class="border-t border-slate-100">
            <td class="px-5 py-3 text-slate-500">#{{ u.user_id }}</td>
            <td class="px-5 py-3 font-medium text-slate-900">{{ u.full_name }}</td>
            <td class="px-5 py-3 text-slate-600">{{ u.email }}</td>
            <td class="px-5 py-3">
              <span class="badge" :class="palette[u.role] || 'bg-slate-100 text-slate-700'">
                {{ u.role }}
              </span>
            </td>
            <td class="px-5 py-3 text-slate-600">{{ formatDate(u.created_at) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

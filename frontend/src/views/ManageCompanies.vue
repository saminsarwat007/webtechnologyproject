<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'

const toast = useToast()
const loading   = ref(true)
const companies = ref([])

const modalOpen = ref(false)
const editingId = ref(null)
const form = reactive({
  name: '', industry: '', location: '', description: ''
})
const errors = reactive({ name: '', industry: '', location: '' })

const confirmOpen = ref(false)
const deleteId    = ref(null)

onMounted(load)

async function load () {
  loading.value = true
  try {
    const { data } = await api.get('/companies')
    companies.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load companies.')
  } finally {
    loading.value = false
  }
}

function openCreate () {
  editingId.value = null
  form.name = ''
  form.industry = ''
  form.location = ''
  form.description = ''
  Object.keys(errors).forEach(k => errors[k] = '')
  modalOpen.value = true
}

function openEdit (c) {
  editingId.value = c.company_id
  form.name        = c.name
  form.industry    = c.industry
  form.location    = c.location
  form.description = c.description ?? ''
  Object.keys(errors).forEach(k => errors[k] = '')
  modalOpen.value = true
}

function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')
  if (!form.name.trim())     errors.name = 'Name is required.'
  if (!form.industry.trim()) errors.industry = 'Industry is required.'
  if (!form.location.trim()) errors.location = 'Location is required.'
  return Object.values(errors).every(v => v === '')
}

async function save () {
  if (!validate()) return
  try {
    if (editingId.value) {
      const { data } = await api.put(`/companies/${editingId.value}`, { ...form })
      companies.value = companies.value.map(c => c.company_id === editingId.value ? data.data : c)
      toast.success('Company updated.')
    } else {
      const { data } = await api.post('/companies', { ...form })
      companies.value = [data.data, ...companies.value]
      toast.success('Company created.')
    }
    modalOpen.value = false
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not save company.')
  }
}

function askDelete (id) {
  deleteId.value = id
  confirmOpen.value = true
}
async function doDelete () {
  const id = deleteId.value
  confirmOpen.value = false
  if (!id) return
  try {
    await api.delete(`/companies/${id}`)
    companies.value = companies.value.filter(c => c.company_id !== id)
    toast.success('Company deleted.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete company.')
  } finally {
    deleteId.value = null
  }
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Companies</h1>
        <p class="text-sm text-slate-500">Maintain the directory of partner organisations.</p>
      </div>
      <button class="btn-primary" @click="openCreate">Add company</button>
    </header>

    <LoadingSpinner v-if="loading" />

    <div v-else class="card overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
          <tr>
            <th class="text-left px-5 py-3">Name</th>
            <th class="text-left px-5 py-3">Industry</th>
            <th class="text-left px-5 py-3">Location</th>
            <th class="text-right px-5 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="companies.length === 0">
            <td colspan="4" class="px-5 py-12 text-center text-slate-500">No companies yet.</td>
          </tr>
          <tr v-for="c in companies" :key="c.company_id" class="border-t border-slate-100">
            <td class="px-5 py-3 font-medium text-slate-900">{{ c.name }}</td>
            <td class="px-5 py-3 text-slate-600">{{ c.industry }}</td>
            <td class="px-5 py-3 text-slate-600">{{ c.location }}</td>
            <td class="px-5 py-3 text-right space-x-3">
              <button class="text-brand-600 hover:text-brand-700 text-sm" @click="openEdit(c)">Edit</button>
              <button class="text-red-600 hover:text-red-700 text-sm" @click="askDelete(c.company_id)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="modalOpen = false">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-lg w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">{{ editingId ? 'Edit company' : 'New company' }}</h3>
          <div class="space-y-4 mt-4">
            <div>
              <label class="label">Name</label>
              <input v-model="form.name" class="input" :class="{ 'input--error': errors.name }" />
              <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="label">Industry</label>
                <input v-model="form.industry" class="input" :class="{ 'input--error': errors.industry }" />
                <p v-if="errors.industry" class="mt-1 text-sm text-red-500">{{ errors.industry }}</p>
              </div>
              <div>
                <label class="label">Location</label>
                <input v-model="form.location" class="input" :class="{ 'input--error': errors.location }" />
                <p v-if="errors.location" class="mt-1 text-sm text-red-500">{{ errors.location }}</p>
              </div>
            </div>
            <div>
              <label class="label">Description</label>
              <textarea v-model="form.description" rows="3" class="input" />
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-2">
            <button class="btn-secondary" @click="modalOpen = false">Cancel</button>
            <button class="btn-primary" @click="save">{{ editingId ? 'Save changes' : 'Create' }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <ConfirmDialog :open="confirmOpen"
                   title="Delete company?"
                   message="This action permanently removes the record. Companies with linked jobs cannot be deleted."
                   confirm-text="Delete"
                   variant="danger"
                   @confirm="doDelete"
                   @cancel="confirmOpen = false" />
  </section>
</template>

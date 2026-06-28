<script setup>
import { onMounted, reactive, ref } from 'vue'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'

/**
 * Module 8 — Label & Tag Management (Owner: Monika)
 *
 * Admin-only CRUD for forum labels. Admin can rename or delete labels;
 * deleting is rejected by the API if any live post still references the label.
 */

const toast = useToast()

const loading = ref(true)
const labels  = ref([])

const modalOpen = ref(false)
const editingId = ref(null)
const submitting = ref(false)

const form   = reactive({ name: '' })
const errors = reactive({ name: '' })

const confirmOpen = ref(false)
const deleteId    = ref(null)
const deleteName  = ref('')

onMounted(load)

async function load () {
  loading.value = true
  try {
    const { data } = await api.get('/labels')
    labels.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load labels.')
  } finally {
    loading.value = false
  }
}

function openCreate () {
  editingId.value = null
  form.name = ''
  errors.name = ''
  modalOpen.value = true
}

function openEdit (label) {
  editingId.value = label.label_id
  form.name = label.name
  errors.name = ''
  modalOpen.value = true
}

function closeModal () {
  if (submitting.value) return
  modalOpen.value = false
}

function validate () {
  errors.name = ''
  const v = form.name.trim()
  if (!v)              errors.name = 'Label name is required.'
  else if (v.length > 60) errors.name = 'Label must be 60 chars or fewer.'
  return !errors.name
}

async function submit () {
  if (!validate()) return
  submitting.value = true
  try {
    const payload = { name: form.name.trim() }
    if (editingId.value === null) {
      await api.post('/labels', payload)
      toast.success('Label created.')
    } else {
      await api.put(`/labels/${editingId.value}`, payload)
      toast.success('Label updated.')
    }
    modalOpen.value = false
    await load()
  } catch (err) {
    const fieldErrors = err?.response?.data?.errors
    if (fieldErrors?.name) errors.name = fieldErrors.name
    toast.error(err?.response?.data?.message || 'Could not save label.')
  } finally {
    submitting.value = false
  }
}

function askDelete (label) {
  deleteId.value   = label.label_id
  deleteName.value = label.name
  confirmOpen.value = true
}

async function confirmDelete () {
  const id = deleteId.value
  confirmOpen.value = false
  if (!id) return
  try {
    await api.delete(`/labels/${id}`)
    toast.success('Label deleted.')
    await load()
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete label.')
  }
}

function formatDate (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Manage labels</h1>
        <p class="text-sm text-slate-500">Create, rename, or delete the labels students use to tag forum posts.</p>
      </div>
      <button class="btn-primary" @click="openCreate">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        New label
      </button>
    </header>

    <LoadingSpinner v-if="loading" />

    <div v-else-if="labels.length === 0" class="card p-12 text-center text-slate-500">
      <p class="text-sm">No labels yet. Create the first one!</p>
    </div>

    <div v-else class="card overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Label</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Posts</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Created by</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wide">Created</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wide">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white">
          <tr v-for="l in labels" :key="l.label_id">
            <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ l.name }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ l.post_count }}</td>
            <td class="px-4 py-3 text-sm text-slate-600">{{ l.created_by_name }}</td>
            <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(l.created_at) }}</td>
            <td class="px-4 py-3 text-right text-sm space-x-2">
              <button class="text-brand-600 hover:text-brand-700" @click="openEdit(l)">Edit</button>
              <button class="text-red-600 hover:text-red-700" @click="askDelete(l)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ===== Modal ===== -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-md w-full p-6">
          <h3 class="text-lg font-semibold text-slate-900">
            {{ editingId === null ? 'New label' : 'Rename label' }}
          </h3>
          <form @submit.prevent="submit" novalidate class="mt-4 space-y-4">
            <div>
              <label class="label" for="label-name">Name</label>
              <input id="label-name" v-model="form.name" class="input"
                     :class="{ 'input--error': errors.name }"
                     maxlength="60" placeholder="e.g. Interview Tips" />
              <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn-secondary" @click="closeModal" :disabled="submitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Saving…' : (editingId === null ? 'Create' : 'Save') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <ConfirmDialog
      :open="confirmOpen"
      title="Delete this label?"
      :message="`Are you sure you want to delete &quot;${deleteName}&quot;? This is blocked if any posts still use it.`"
      confirm-text="Delete"
      @confirm="confirmDelete"
      @cancel="confirmOpen = false" />
  </section>
</template>

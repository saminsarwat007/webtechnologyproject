<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'
import { useAuthStore } from '../stores/auth.js'

/**
 * Module 7 — Forum & Discussion
 * Two-column layout: posts feed (left) + label sidebar (right).
 * Posts reference a row in the `labels` table via label_id.
 */

const router = useRouter()
const toast  = useToast()
const auth   = useAuthStore()

const loadingPosts  = ref(true)
const posts         = ref([])
const labels        = ref([])     // [{ label_id, name, post_count }, ...]
const activeLabelId = ref(null)   // null = "All posts"
const sortBy        = ref('newest') // 'newest' or 'popular'
const search        = ref('')
const debouncedQ    = ref('')

const activeLabelName = computed(() => {
  if (activeLabelId.value === null) return ''
  const found = labels.value.find(l => l.label_id === activeLabelId.value)
  return found ? found.name : ''
})

let debounceTimer = null
watch(search, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { debouncedQ.value = val.trim().toLowerCase() }, 350)
})

function setLabel (labelId) {
  activeLabelId.value = labelId
}

// ---- New post modal -------------------------------------------------
const modalOpen  = ref(false)
const submitting = ref(false)
const form       = reactive({ title: '', content: '', label_id: null })
const errors     = reactive({ title: '', content: '', label_id: '' })

function openModal () {
  form.title    = ''
  form.content  = ''
  form.label_id = null
  Object.keys(errors).forEach(k => errors[k] = '')
  modalOpen.value = true
}

function closeModal () {
  if (submitting.value) return
  modalOpen.value = false
}

// ---- Load posts -----------------------------------------------------
onMounted(loadPosts)

async function loadPosts () {
  loadingPosts.value = true
  try {
    const [postsRes, labelsRes] = await Promise.all([
      api.get('/forums', { params: { sort: sortBy.value } }),
      api.get('/labels')
    ])
    posts.value  = postsRes.data?.data ?? []
    labels.value = labelsRes.data?.data ?? []
  } catch (_e) {
    toast.error('Could not load forum posts.')
  } finally {
    loadingPosts.value = false
  }
}

function setSort (value) {
  if (sortBy.value === value) return
  sortBy.value = value
  loadPosts()
}

// ---- Filtered list --------------------------------------------------
const filteredPosts = computed(() => {
  return posts.value.filter(p => {
    if (activeLabelId.value !== null && p.label_id !== activeLabelId.value) return false
    if (!debouncedQ.value) return true
    const hay = `${p.title} ${p.content} ${p.author_name}`.toLowerCase()
    return hay.includes(debouncedQ.value)
  })
})

// ---- Like toggle ----------------------------------------------------
async function toggleLike (post) {
  try {
    const { data } = await api.post(`/forums/${post.post_id}/like`)
    post.likes       = data?.data?.likes       ?? post.likes
    post.liked_by_me = data?.data?.liked_by_me ?? !post.liked_by_me
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not update like.')
  }
}

// ---- Validation -----------------------------------------------------
function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')
  if (!form.title.trim())              errors.title   = 'Title is required.'
  else if (form.title.length > 150)    errors.title   = 'Title must be 150 chars or fewer.'
  if (!form.content.trim())            errors.content = 'Content is required.'
  else if (form.content.length > 5000) errors.content = 'Content must be 5 000 chars or fewer.'
  return Object.values(errors).every(v => v === '')
}

async function submitPost () {
  if (!validate()) return
  submitting.value = true
  try {
    await api.post('/forums', {
      title:    form.title.trim(),
      content:  form.content.trim(),
      label_id: form.label_id
    })
    toast.success('Post created!')
    modalOpen.value = false
    await loadPosts()
  } catch (err) {
    const apiErrors = err?.response?.data?.errors
    if (apiErrors && typeof apiErrors === 'object') {
      Object.keys(apiErrors).forEach(k => { if (k in errors) errors[k] = apiErrors[k] })
    }
    toast.error(err?.response?.data?.message || 'Could not create post.')
  } finally {
    submitting.value = false
  }
}

function viewPost (id) { router.push(`/forum/${id}`) }

function formatDate (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' })
}

function snippet (text, n = 180) {
  if (!text) return ''
  return text.length > n ? text.slice(0, n).trim() + '…' : text
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Community Forum</h1>
        <p class="text-sm text-slate-500">Share interview tips, ask for advice, and learn from peers.</p>
      </div>
      <button v-if="auth.isStudent" class="btn-primary" @click="openModal">
        <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        New post
      </button>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_16rem] gap-6">
      <!-- Posts column -->
      <div class="space-y-4">
        <!-- Search bar -->
        <div class="card p-4">
          <div class="relative">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/>
            </svg>
            <input v-model="search" type="text" class="input pl-9"
                   placeholder="Search posts by title, content, or author" />
          </div>
          <div v-if="activeLabelId !== null" class="mt-3 flex items-center text-sm text-slate-600">
            <span class="mr-2">Filtered by:</span>
            <span class="badge bg-indigo-50 text-indigo-700">{{ activeLabelName }}</span>
            <button class="ml-2 text-xs text-slate-500 hover:text-slate-700" @click="setLabel(null)">
              Clear ✕
            </button>
          </div>
        </div>

        <!-- Sort toggle -->
        <div class="flex items-center gap-2 mb-3">
          <span class="text-xs text-slate-500">Sort:</span>
          <button
            @click="setSort('newest')"
            class="px-3 py-1 rounded-full text-xs font-medium border transition-colors"
            :class="sortBy === 'newest'
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
            Newest
          </button>
          <button
            @click="setSort('popular')"
            class="px-3 py-1 rounded-full text-xs font-medium border transition-colors"
            :class="sortBy === 'popular'
              ? 'bg-indigo-600 text-white border-indigo-600'
              : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
            Popular
          </button>
        </div>

        <LoadingSpinner v-if="loadingPosts" />

        <div v-else-if="filteredPosts.length === 0"
             class="card p-12 text-center text-slate-500">
          <p class="text-sm">No posts match your filters. Be the first to start a thread!</p>
        </div>

        <article v-for="p in filteredPosts" :key="p.post_id"
                 class="card p-5 hover:shadow-md transition-shadow cursor-pointer"
                 @click="viewPost(p.post_id)">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <h2 class="text-base font-semibold text-slate-900 truncate">
                {{ p.is_deleted ? '[deleted post]' : p.title }}
              </h2>
              <p class="mt-1 text-sm text-slate-600">{{ snippet(p.content) }}</p>
              <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span>By {{ p.author_name }}</span>
                <span aria-hidden>·</span>
                <span>{{ formatDate(p.created_at) }}</span>
                <span v-if="p.label_name" aria-hidden>·</span>
                <span v-if="p.label_name"
                      class="badge bg-slate-100 text-slate-700">
                  {{ p.label_name }}
                </span>
              </div>
            </div>

            <div class="flex flex-col items-end gap-2 shrink-0">
              <button
                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm border transition-colors"
                :class="p.liked_by_me
                  ? 'border-red-200 bg-red-50 text-red-600'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                :disabled="p.is_deleted"
                @click.stop="toggleLike(p)">
                <svg class="w-4 h-4" viewBox="0 0 24 24"
                     :fill="p.liked_by_me ? 'currentColor' : 'none'"
                     stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                {{ p.likes ?? 0 }}
              </button>
              <span class="text-xs text-slate-500">
                {{ p.comment_count ?? 0 }}
                {{ Number(p.comment_count) === 1 ? 'comment' : 'comments' }}
              </span>
            </div>
          </div>
        </article>
      </div>

      <!-- Labels sidebar -->
      <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
        <div class="card p-4">
          <h3 class="text-sm font-semibold text-slate-900 mb-3">Browse by Label</h3>
          <ul class="space-y-1">
            <li>
              <button
                :class="activeLabelId === null
                  ? 'bg-indigo-50 text-indigo-700'
                  : 'hover:bg-slate-50 text-slate-700'"
                class="w-full text-left rounded-md px-2 py-1.5 flex justify-between items-center"
                @click="setLabel(null)">
                <span class="truncate">All posts</span>
                <span class="text-xs text-slate-400">{{ posts.length }}</span>
              </button>
            </li>
            <li v-for="lab in labels" :key="lab.label_id">
              <button
                :class="activeLabelId === lab.label_id
                  ? 'bg-indigo-50 text-indigo-700'
                  : 'hover:bg-slate-50 text-slate-700'"
                class="w-full text-left rounded-md px-2 py-1.5 flex justify-between items-center"
                @click="setLabel(lab.label_id)">
                <span class="truncate">{{ lab.name }}</span>
                <span class="text-xs text-slate-400">{{ lab.post_count }}</span>
              </button>
            </li>
            <li v-if="!loadingPosts && labels.length === 0"
                class="text-xs text-slate-500 px-2 py-1">No labels yet.</li>
          </ul>
        </div>

        <!-- Interview shortcuts -->
        <div class="card p-4 text-sm space-y-2">
          <h3 class="font-semibold text-slate-900">Mock Interviews</h3>
          <RouterLink v-if="auth.isStudent"
                      to="/interview/dashboard"
                      class="block text-indigo-600 hover:text-indigo-700">
            My sessions →
          </RouterLink>
          <RouterLink v-if="auth.isAdmin"
                      to="/interview/slots"
                      class="block text-indigo-600 hover:text-indigo-700">
            Manage slots →
          </RouterLink>
        </div>
      </aside>
    </div>

    <!-- New post modal -->
    <Transition name="fade">
      <div v-if="modalOpen"
           class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-xl w-full p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-semibold text-slate-900">New Forum Post</h3>
          <p class="text-sm text-slate-500 mb-4">Start a discussion or ask the community a question.</p>

          <form @submit.prevent="submitPost" novalidate class="space-y-4">
            <!-- Title -->
            <div>
              <label class="label" for="post-title">Title <span class="text-red-500">*</span></label>
              <input id="post-title" v-model="form.title" class="input"
                     :class="{ 'input--error': errors.title }"
                     maxlength="150" placeholder="What's on your mind?" />
              <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ form.title.length }} / 150</p>
            </div>

            <!-- Content -->
            <div>
              <label class="label" for="post-content">Content <span class="text-red-500">*</span></label>
              <textarea id="post-content" v-model="form.content" rows="5" class="input"
                        :class="{ 'input--error': errors.content }"
                        maxlength="5000"
                        placeholder="Share details, context, or your question…" />
              <p v-if="errors.content" class="mt-1 text-sm text-red-500">{{ errors.content }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ form.content.length }} / 5 000</p>
            </div>

            <!-- Label -->
            <div>
              <label class="label" for="post-label">Label</label>
              <select id="post-label" v-model="form.label_id" class="input">
                <option :value="null">— No label —</option>
                <option v-for="lab in labels" :key="lab.label_id" :value="lab.label_id">
                  {{ lab.name }}
                </option>
              </select>
            </div>

            <div class="flex justify-end gap-2 pt-1">
              <button type="button" class="btn-secondary" @click="closeModal" :disabled="submitting">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="submitting">
                {{ submitting ? 'Posting…' : 'Post' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </section>
</template>

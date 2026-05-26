<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import { useToast } from '../composables/useToast.js'
import { useAuthStore } from '../stores/auth.js'

/**
 * Module 7 — Forum & Discussion (Owner: Monika)
 *
 * Two-column layout: posts on the left (sorted by likes, with search & tag
 * filter) and a tag sidebar on the right derived from the post list itself.
 * Tags are free-text strings (max 60 chars) — there's no separate labels
 * table any more.
 */

const router = useRouter()
const toast  = useToast()
const auth   = useAuthStore()

const loadingPosts = ref(true)
const posts        = ref([])

const search     = ref('')
const debouncedQ = ref('')
const activeTag  = ref('')   // '' = "all"

let debounceTimer = null
watch(search, (val) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => { debouncedQ.value = val.trim().toLowerCase() }, 350)
})

// ---- New post modal ---------------------------------------------------
const modalOpen  = ref(false)
const submitting = ref(false)
const form   = reactive({ title: '', content: '', tag: 'General' })
const errors = reactive({ title: '', content: '', tag: '' })

function openModal () {
  form.title   = ''
  form.content = ''
  form.tag     = activeTag.value || 'General'
  Object.keys(errors).forEach(k => errors[k] = '')
  modalOpen.value = true
}
function closeModal () {
  if (submitting.value) return
  modalOpen.value = false
}

// ---- Initial load ------------------------------------------------------
onMounted(loadPosts)

async function loadPosts () {
  loadingPosts.value = true
  try {
    const { data } = await api.get('/forums')
    posts.value = data?.data ?? []
  } catch (_e) {
    toast.error('Could not load forum posts.')
  } finally {
    loadingPosts.value = false
  }
}

// Sidebar list — group by tag with post counts (sorted by frequency desc).
const tagBuckets = computed(() => {
  const counts = new Map()
  for (const p of posts.value) {
    const t = (p.tag || 'General').trim()
    counts.set(t, (counts.get(t) || 0) + 1)
  }
  return [...counts.entries()]
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name))
})

const filteredPosts = computed(() => {
  return posts.value.filter(p => {
    if (activeTag.value && (p.tag || 'General') !== activeTag.value) return false
    if (debouncedQ.value === '') return true
    const hay = `${p.title} ${p.content} ${p.author_name} ${p.tag || ''}`.toLowerCase()
    return hay.includes(debouncedQ.value)
  })
})

function setTag (name) {
  activeTag.value = name
}

async function toggleLike (post) {
  try {
    const { data } = await api.post(`/forums/${post.post_id}/like`)
    post.likes       = data?.data?.likes       ?? post.likes
    post.liked_by_me = data?.data?.liked_by_me ?? !post.liked_by_me
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not update like.')
  }
}

function validate () {
  Object.keys(errors).forEach(k => errors[k] = '')
  if (!form.title.trim())               errors.title   = 'Title is required.'
  else if (form.title.length > 150)     errors.title   = 'Title must be 150 chars or fewer.'
  if (!form.content.trim())             errors.content = 'Content is required.'
  else if (form.content.length > 5000)  errors.content = 'Content must be 5000 chars or fewer.'
  if (form.tag && form.tag.length > 60) errors.tag     = 'Tag must be 60 chars or fewer.'
  return Object.values(errors).every(v => v === '')
}

async function submitPost () {
  if (!validate()) return
  submitting.value = true

  try {
    await api.post('/forums', {
      title:   form.title.trim(),
      content: form.content.trim(),
      tag:     form.tag.trim() || 'General'
    })

    toast.success('Post created.')
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

function viewPost (id) {
  router.push(`/forum/${id}`)
}

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
        <h1 class="text-2xl font-semibold text-slate-900">Community forum</h1>
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

    <div class="grid grid-cols-1 lg:grid-cols-[1fr_18rem] gap-6">
      <!-- ===== Posts column ===== -->
      <div class="space-y-4">
        <div class="card p-4">
          <div class="relative">
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="7" /><path d="M21 21l-4.35-4.35" />
            </svg>
            <input v-model="search" type="text" class="input pl-9"
                   placeholder="Search posts by title, content, or tag" />
          </div>
          <div v-if="activeTag" class="mt-3 flex items-center text-sm text-slate-600">
            <span class="mr-2">Filtered by tag:</span>
            <span class="badge bg-brand-50 text-brand-700">{{ activeTag }}</span>
            <button class="ml-2 text-xs text-slate-500 hover:text-slate-700" @click="setTag('')">
              Clear
            </button>
          </div>
        </div>

        <LoadingSpinner v-if="loadingPosts" />

        <div v-else-if="filteredPosts.length === 0" class="card p-12 text-center text-slate-500">
          <p class="text-sm">No posts match your filters. Be the first to start a thread!</p>
        </div>

        <article v-for="p in filteredPosts" :key="p.post_id"
                 class="card p-5 hover:shadow-md transition-shadow cursor-pointer"
                 @click="viewPost(p.post_id)">
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
              <h2 class="text-lg font-semibold text-slate-900 truncate">{{ p.title }}</h2>
              <p class="mt-1 text-sm text-slate-600">{{ snippet(p.content) }}</p>

              <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span>By {{ p.author_name }}</span>
                <span aria-hidden>·</span>
                <span>{{ formatDate(p.created_at) }}</span>
                <span v-if="p.tag" aria-hidden>·</span>
                <button v-if="p.tag"
                        class="badge bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                        @click.stop="setTag(p.tag)">
                  {{ p.tag }}
                </button>
              </div>
            </div>

            <div class="flex flex-col items-end gap-3 shrink-0">
              <button
                class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm border transition-colors"
                :class="p.liked_by_me
                  ? 'border-red-200 bg-red-50 text-red-600'
                  : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                @click.stop="toggleLike(p)">
                <svg class="w-4 h-4" viewBox="0 0 24 24"
                     :fill="p.liked_by_me ? 'currentColor' : 'none'"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                {{ p.likes }}
              </button>
              <span class="text-xs text-slate-500">
                {{ p.comment_count }} {{ Number(p.comment_count) === 1 ? 'comment' : 'comments' }}
              </span>
            </div>
          </div>
        </article>
      </div>

      <!-- ===== Tags sidebar ===== -->
      <aside class="space-y-4 lg:sticky lg:top-20 lg:self-start">
        <div class="card p-4">
          <h3 class="text-sm font-semibold text-slate-900 mb-3">Tags</h3>
          <ul class="space-y-1">
            <li>
              <button class="w-full text-left text-sm px-2 py-1.5 rounded-md flex items-center justify-between"
                      :class="activeTag === ''
                        ? 'bg-brand-50 text-brand-700 font-medium'
                        : 'text-slate-600 hover:bg-slate-50'"
                      @click="setTag('')">
                <span>All posts</span>
                <span class="text-xs text-slate-400">{{ posts.length }}</span>
              </button>
            </li>
            <li v-for="t in tagBuckets" :key="t.name">
              <button class="w-full text-left text-sm px-2 py-1.5 rounded-md flex items-center justify-between"
                      :class="activeTag === t.name
                        ? 'bg-brand-50 text-brand-700 font-medium'
                        : 'text-slate-600 hover:bg-slate-50'"
                      @click="setTag(t.name)">
                <span class="truncate">{{ t.name }}</span>
                <span class="text-xs text-slate-400">{{ t.count }}</span>
              </button>
            </li>
            <li v-if="!loadingPosts && tagBuckets.length === 0" class="text-xs text-slate-500 px-2 py-1">
              No tags yet.
            </li>
          </ul>
        </div>
      </aside>
    </div>

    <!-- ===== New post modal ===== -->
    <Transition name="fade">
      <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4"
           @click.self="closeModal">
        <div class="absolute inset-0 bg-slate-900/50" />
        <div class="relative card max-w-xl w-full p-6 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-semibold text-slate-900">New forum post</h3>
          <p class="text-sm text-slate-500">Start a discussion or ask the community a question.</p>

          <form @submit.prevent="submitPost" novalidate class="space-y-4 mt-4">
            <div>
              <label class="label" for="post-title">Title</label>
              <input id="post-title" v-model="form.title" class="input"
                     :class="{ 'input--error': errors.title }" maxlength="150"
                     placeholder="What's on your mind?" />
              <p v-if="errors.title" class="mt-1 text-sm text-red-500">{{ errors.title }}</p>
            </div>

            <div>
              <label class="label" for="post-content">Content</label>
              <textarea id="post-content" v-model="form.content" rows="6" class="input"
                        :class="{ 'input--error': errors.content }" maxlength="5000"
                        placeholder="Share details, context, or your question…" />
              <p v-if="errors.content" class="mt-1 text-sm text-red-500">{{ errors.content }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ form.content.length }} / 5000</p>
            </div>

            <div>
              <label class="label" for="post-tag">Tag</label>
              <input id="post-tag" v-model="form.tag" class="input" maxlength="60"
                     :class="{ 'input--error': errors.tag }"
                     placeholder="e.g. Interview Tips, Resume Advice, General" />
              <p v-if="errors.tag" class="mt-1 text-sm text-red-500">{{ errors.tag }}</p>
              <p class="mt-1 text-xs text-slate-400">
                Free text — leave empty to use "General".
              </p>
            </div>

            <div class="flex justify-end gap-2 pt-2">
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

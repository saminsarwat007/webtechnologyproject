<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../services/api.js'
import LoadingSpinner from '../components/LoadingSpinner.vue'
import ConfirmDialog from '../components/ConfirmDialog.vue'
import { useToast } from '../composables/useToast.js'
import { useAuthStore } from '../stores/auth.js'

/**
 * Module 7 — Forum & Discussion (Owner: Monika)
 *
 * Single-post view + comments thread.
 *  - Author can edit (toggles to a form) or delete.
 *  - Admin can delete the post and any comment.
 *  - Any logged-in user can like/unlike; students can also add comments.
 *
 * Post deletion is always cascade (CASCADE removes comments + likes).
 * No more soft-delete distinction.
 */

const route   = useRoute()
const router  = useRouter()
const toast   = useToast()
const auth    = useAuthStore()

const postId = computed(() => Number(route.params.id))

const loading  = ref(true)
const post     = ref(null)
const comments = ref([])

const commentText = ref('')
const submittingComment = ref(false)

const editing  = ref(false)
const editForm = reactive({ title: '', content: '', tag: '' })
const editErrors = reactive({ title: '', content: '', tag: '' })
const savingEdit = ref(false)

const confirmOpen   = ref(false)
const confirmAction = ref(null)  // { type:'post' } | { type:'comment', id }

onMounted(loadPost)

async function loadPost () {
  loading.value = true
  try {
    const { data } = await api.get(`/forums/${postId.value}`)
    post.value     = data?.data?.post ?? null
    comments.value = data?.data?.comments ?? []
  } catch (err) {
    if (err?.response?.status === 404) {
      toast.error('Post not found.')
      router.push('/forum')
    } else {
      toast.error('Could not load post.')
    }
  } finally {
    loading.value = false
  }
}

const isOwner = computed(() =>
  post.value && Number(post.value.user_id) === Number(auth.user?.user_id)
)

// ---- Likes -----------------------------------------------------------
async function toggleLike () {
  if (!post.value) return
  try {
    const { data } = await api.post(`/forums/${postId.value}/like`)
    post.value.likes       = data?.data?.likes ?? post.value.likes
    post.value.liked_by_me = data?.data?.liked_by_me ?? !post.value.liked_by_me
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not update like.')
  }
}

// ---- Comments --------------------------------------------------------
async function submitComment () {
  const text = commentText.value.trim()
  if (text === '') return
  submittingComment.value = true
  try {
    const { data } = await api.post(`/forums/${postId.value}/comments`, { content: text })
    if (data?.data) comments.value.push(data.data)
    commentText.value = ''
    toast.success('Comment added.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not add comment.')
  } finally {
    submittingComment.value = false
  }
}

function askDeleteComment (id) {
  confirmAction.value = { type: 'comment', id }
  confirmOpen.value = true
}

async function deleteComment (id) {
  try {
    // Flat path per the revised spec.
    await api.delete(`/forums/comments/${id}`)
    comments.value = comments.value.filter(c => c.comment_id !== id)
    toast.success('Comment deleted.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete comment.')
  }
}

// ---- Edit post -------------------------------------------------------
function startEdit () {
  editForm.title   = post.value.title
  editForm.content = post.value.content
  editForm.tag     = post.value.tag || 'General'
  Object.keys(editErrors).forEach(k => editErrors[k] = '')
  editing.value = true
}

function cancelEdit () {
  if (savingEdit.value) return
  editing.value = false
}

function validateEdit () {
  Object.keys(editErrors).forEach(k => editErrors[k] = '')
  if (!editForm.title.trim())               editErrors.title   = 'Title is required.'
  else if (editForm.title.length > 150)     editErrors.title   = 'Title must be 150 chars or fewer.'
  if (!editForm.content.trim())             editErrors.content = 'Content is required.'
  else if (editForm.content.length > 5000)  editErrors.content = 'Content must be 5000 chars or fewer.'
  if (editForm.tag && editForm.tag.length > 60) editErrors.tag = 'Tag must be 60 chars or fewer.'
  return Object.values(editErrors).every(v => v === '')
}

async function saveEdit () {
  if (!validateEdit()) return
  savingEdit.value = true
  try {
    const { data } = await api.put(`/forums/${postId.value}`, {
      title:   editForm.title.trim(),
      content: editForm.content.trim(),
      tag:     editForm.tag.trim() || 'General'
    })
    if (data?.data) post.value = { ...post.value, ...data.data, liked_by_me: post.value.liked_by_me }
    editing.value = false
    toast.success('Post updated.')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not save changes.')
  } finally {
    savingEdit.value = false
  }
}

// ---- Delete post -----------------------------------------------------
function askDeletePost () {
  confirmAction.value = { type: 'post' }
  confirmOpen.value = true
}

async function deletePost () {
  try {
    await api.delete(`/forums/${postId.value}`)
    toast.success('Post deleted.')
    router.push('/forum')
  } catch (err) {
    toast.error(err?.response?.data?.message || 'Could not delete post.')
  }
}

function onConfirm () {
  const a = confirmAction.value
  confirmOpen.value = false
  if (!a) return
  if (a.type === 'post')    deletePost()
  if (a.type === 'comment') deleteComment(a.id)
}

const confirmCopy = computed(() => {
  if (!confirmAction.value) return { title: '', body: '' }
  if (confirmAction.value.type === 'post') {
    const hasComments = comments.value.length > 0
    return {
      title: 'Delete this post?',
      body:  hasComments
        ? 'This will permanently remove the post and all of its comments + likes.'
        : 'This post will be permanently removed.'
    }
  }
  return { title: 'Delete this comment?', body: 'This action cannot be undone.' }
})

function formatDate (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, {
    day: 'numeric', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  })
}
</script>

<template>
  <section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <button class="text-sm text-slate-500 hover:text-slate-700 mb-4" @click="router.push('/forum')">
      ← Back to forum
    </button>

    <LoadingSpinner v-if="loading" />

    <div v-else-if="!post" class="card p-12 text-center text-slate-500">
      <p>Post not found.</p>
    </div>

    <div v-else class="space-y-6">
      <!-- ===== Post card ===== -->
      <article class="card p-6">
        <!-- Read mode -->
        <template v-if="!editing">
          <header class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h1 class="text-2xl font-semibold text-slate-900 break-words">
                {{ post.title }}
              </h1>
              <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span>By {{ post.author_name }}</span>
                <span aria-hidden>·</span>
                <span>{{ formatDate(post.created_at) }}</span>
                <span v-if="post.tag" aria-hidden>·</span>
                <span v-if="post.tag" class="badge bg-slate-100 text-slate-700">
                  {{ post.tag }}
                </span>
              </div>
            </div>
            <button
              class="shrink-0 inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm border transition-colors"
              :class="post.liked_by_me
                ? 'border-red-200 bg-red-50 text-red-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="toggleLike">
              <svg class="w-4 h-4" viewBox="0 0 24 24"
                   :fill="post.liked_by_me ? 'currentColor' : 'none'"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
              </svg>
              {{ post.likes }}
            </button>
          </header>

          <p class="mt-4 whitespace-pre-wrap text-slate-700 leading-relaxed">
            {{ post.content }}
          </p>

          <div v-if="isOwner || auth.isAdmin"
               class="mt-5 pt-4 border-t border-slate-200 flex flex-wrap gap-2">
            <button v-if="isOwner" class="btn-secondary !py-1.5 !px-3 text-sm" @click="startEdit">
              Edit
            </button>
            <button class="btn-danger !py-1.5 !px-3 text-sm" @click="askDeletePost">
              Delete
            </button>
          </div>
        </template>

        <!-- Edit mode -->
        <template v-else>
          <h2 class="text-lg font-semibold text-slate-900 mb-4">Edit post</h2>
          <form @submit.prevent="saveEdit" novalidate class="space-y-4">
            <div>
              <label class="label" for="edit-title">Title</label>
              <input id="edit-title" v-model="editForm.title" class="input"
                     :class="{ 'input--error': editErrors.title }" maxlength="150" />
              <p v-if="editErrors.title" class="mt-1 text-sm text-red-500">{{ editErrors.title }}</p>
            </div>
            <div>
              <label class="label" for="edit-content">Content</label>
              <textarea id="edit-content" v-model="editForm.content" rows="6" class="input"
                        :class="{ 'input--error': editErrors.content }" maxlength="5000" />
              <p v-if="editErrors.content" class="mt-1 text-sm text-red-500">{{ editErrors.content }}</p>
            </div>
            <div>
              <label class="label" for="edit-tag">Tag</label>
              <input id="edit-tag" v-model="editForm.tag" class="input"
                     :class="{ 'input--error': editErrors.tag }" maxlength="60"
                     placeholder="e.g. Interview Tips" />
              <p v-if="editErrors.tag" class="mt-1 text-sm text-red-500">{{ editErrors.tag }}</p>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn-secondary" @click="cancelEdit" :disabled="savingEdit">
                Cancel
              </button>
              <button type="submit" class="btn-primary" :disabled="savingEdit">
                {{ savingEdit ? 'Saving…' : 'Save changes' }}
              </button>
            </div>
          </form>
        </template>
      </article>

      <!-- ===== Comments ===== -->
      <section class="space-y-4">
        <h2 class="text-lg font-semibold text-slate-900">
          {{ comments.length }} {{ comments.length === 1 ? 'comment' : 'comments' }}
        </h2>

        <!-- Add comment (students only) -->
        <form v-if="auth.isStudent" class="card p-4" @submit.prevent="submitComment">
          <textarea v-model="commentText" rows="3" class="input"
                    maxlength="2000"
                    placeholder="Add a comment…" />
          <div class="flex justify-between items-center mt-2">
            <span class="text-xs text-slate-400">{{ commentText.length }} / 2000</span>
            <button type="submit" class="btn-primary !py-1.5 !px-3 text-sm"
                    :disabled="submittingComment || !commentText.trim()">
              {{ submittingComment ? 'Posting…' : 'Comment' }}
            </button>
          </div>
        </form>

        <div v-if="comments.length === 0" class="card p-8 text-center text-sm text-slate-500">
          No comments yet.
        </div>

        <ul class="space-y-3">
          <li v-for="c in comments" :key="c.comment_id" class="card p-4">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-medium text-slate-900">{{ c.author_name }}</p>
                <p class="text-xs text-slate-500">{{ formatDate(c.created_at) }}</p>
              </div>
              <button
                v-if="auth.isAdmin || Number(c.user_id) === Number(auth.user?.user_id)"
                class="text-xs text-red-600 hover:text-red-700"
                @click="askDeleteComment(c.comment_id)">
                Delete
              </button>
            </div>
            <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap">{{ c.content }}</p>
          </li>
        </ul>
      </section>
    </div>

    <ConfirmDialog
      :open="confirmOpen"
      :title="confirmCopy.title"
      :message="confirmCopy.body"
      confirm-text="Delete"
      @confirm="onConfirm"
      @cancel="confirmOpen = false" />
  </section>
</template>

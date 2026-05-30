<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../../stores/auth.js'

/**
 * CommentSection — renders the list of comments plus the add-comment form.
 * Emits: 'submit' (text), 'delete' (comment_id)
 */
const props = defineProps({
  comments:     { type: Array,   default: () => [] },
  postDeleted:  { type: Boolean, default: false },
  submitting:   { type: Boolean, default: false }
})

const emit = defineEmits(['submit', 'delete'])

const auth = useAuthStore()
const text = ref('')

function handleSubmit () {
  if (!text.value.trim()) return
  emit('submit', text.value.trim())
  text.value = ''
}

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
  <section class="space-y-4">
    <h2 class="text-lg font-semibold text-slate-900">
      {{ comments.length }} {{ comments.length === 1 ? 'comment' : 'comments' }}
    </h2>

    <!-- Add comment (students only, post not deleted) -->
    <form v-if="auth.isStudent && !postDeleted"
          class="card p-4" @submit.prevent="handleSubmit">
      <textarea v-model="text" rows="3" class="input" maxlength="2000"
                placeholder="Add a comment…" />
      <div class="flex justify-between items-center mt-2">
        <span class="text-xs text-slate-400">{{ text.length }} / 2 000</span>
        <button type="submit" class="btn-primary !py-1.5 !px-3 text-sm"
                :disabled="submitting || !text.trim()">
          {{ submitting ? 'Posting…' : 'Comment' }}
        </button>
      </div>
    </form>

    <div v-if="comments.length === 0"
         class="card p-8 text-center text-sm text-slate-500">
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
            class="text-xs text-red-600 hover:text-red-700 shrink-0"
            @click="emit('delete', c.comment_id)">
            Delete
          </button>
        </div>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap">{{ c.content }}</p>
      </li>
    </ul>
  </section>
</template>

<script setup>
/**
 * PostCard — reusable card for a forum post in the feed.
 * Emits: 'like' (post), 'view' (post)
 */
const props = defineProps({
  post: { type: Object, required: true }
})

const emit = defineEmits(['like', 'view'])

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
  <article
    class="card p-5 hover:shadow-md transition-shadow cursor-pointer"
    @click="emit('view', post)">
    <div class="flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <h2 class="text-base font-semibold text-slate-900 truncate">
          {{ post.is_deleted ? '[deleted post]' : post.title }}
        </h2>
        <p class="mt-1 text-sm text-slate-600">{{ snippet(post.content) }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
          <span>By {{ post.author_name }}</span>
          <span aria-hidden>·</span>
          <span>{{ formatDate(post.created_at) }}</span>
          <template v-if="post.tag && post.tag !== 'General'">
            <span aria-hidden>·</span>
            <span class="badge bg-slate-100 text-slate-700">{{ post.tag }}</span>
          </template>
        </div>
      </div>

      <div class="flex flex-col items-end gap-2 shrink-0">
        <button
          class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm border transition-colors"
          :class="post.liked_by_me
            ? 'border-red-200 bg-red-50 text-red-600'
            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
          :disabled="post.is_deleted"
          @click.stop="emit('like', post)">
          <svg class="w-4 h-4" viewBox="0 0 24 24"
               :fill="post.liked_by_me ? 'currentColor' : 'none'"
               stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
          {{ post.likes ?? 0 }}
        </button>
        <span class="text-xs text-slate-500">
          {{ post.comment_count ?? 0 }}
          {{ Number(post.comment_count) === 1 ? 'comment' : 'comments' }}
        </span>
      </div>
    </div>
  </article>
</template>

<script setup>
defineProps({
  loading: { type: Boolean, default: true },
  label:   { type: String,  default: 'Loading…' }
})
</script>

<template>
  <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-slate-500">
    <svg class="animate-spin h-8 w-8 text-brand-600" viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
      <path d="M4 12a8 8 0 0 1 8-8" stroke="currentColor" stroke-width="4" stroke-linecap="round"></path>
    </svg>
    <p class="mt-3 text-sm">{{ label }}</p>
  </div>
</template>

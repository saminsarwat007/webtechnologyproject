<script setup>
import { useToast } from '../composables/useToast.js'

const { state, dismiss } = useToast()

const palette = {
  success: 'bg-emerald-600',
  error:   'bg-red-600',
  info:    'bg-blue-600'
}
</script>

<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)] pointer-events-none">
    <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2">
      <div v-for="t in state.toasts" :key="t.id"
           class="pointer-events-auto rounded-lg shadow-lg text-white px-4 py-3 flex items-start gap-3"
           :class="palette[t.type] || palette.info">
        <span class="text-sm flex-1">{{ t.message }}</span>
        <button class="text-white/80 hover:text-white text-lg leading-none" @click="dismiss(t.id)" aria-label="Dismiss">×</button>
      </div>
    </TransitionGroup>
  </div>
</template>

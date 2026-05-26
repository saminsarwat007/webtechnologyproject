<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue'

const props = defineProps({
  open:        { type: Boolean, required: true },
  title:       { type: String,  default: 'Are you sure?' },
  message:     { type: String,  default: '' },
  confirmText: { type: String,  default: 'Confirm' },
  cancelText:  { type: String,  default: 'Cancel' },
  variant:     { type: String,  default: 'danger' } // 'danger' | 'primary'
})

const emit = defineEmits(['confirm', 'cancel'])

function handleKey (e) {
  if (!props.open) return
  if (e.key === 'Escape') emit('cancel')
  if (e.key === 'Enter')  emit('confirm')
}

onMounted(() => window.addEventListener('keydown', handleKey))
onBeforeUnmount(() => window.removeEventListener('keydown', handleKey))

watch(() => props.open, (val) => {
  // Lock body scroll while modal is open
  if (typeof document === 'undefined') return
  document.body.style.overflow = val ? 'hidden' : ''
})
</script>

<template>
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4"
         @click.self="emit('cancel')">
      <div class="absolute inset-0 bg-slate-900/50" />
      <div class="relative card max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-slate-900">{{ title }}</h3>
        <p v-if="message" class="mt-2 text-sm text-slate-600">{{ message }}</p>
        <div class="mt-6 flex justify-end gap-2">
          <button class="btn-secondary" @click="emit('cancel')">{{ cancelText }}</button>
          <button :class="variant === 'danger' ? 'btn-danger' : 'btn-primary'"
                  @click="emit('confirm')">
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

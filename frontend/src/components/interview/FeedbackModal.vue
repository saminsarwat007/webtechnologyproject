<script setup>
import { reactive } from 'vue'

/**
 * FeedbackModal — admin form to submit score + feedback for a completed interview.
 * Emits: 'submit' ({ score, feedback_text }), 'close'
 */
defineProps({
  open:        { type: Boolean, required: true },
  submitting:  { type: Boolean, default: false }
})

const emit   = defineEmits(['submit', 'close'])
const form   = reactive({ score: '', feedback_text: '' })
const errors = reactive({ score: '', feedback_text: '' })

function validate () {
  errors.score         = ''
  errors.feedback_text = ''
  const s = Number(form.score)
  if (form.score === '' || Number.isNaN(s)) errors.score = 'Score is required.'
  else if (s < 0 || s > 100)               errors.score = 'Score must be 0–100.'
  if (!form.feedback_text.trim())          errors.feedback_text = 'Feedback is required.'
  return !errors.score && !errors.feedback_text
}

function submit () {
  if (!validate()) return
  emit('submit', { score: Number(form.score), feedback_text: form.feedback_text.trim() })
}
</script>

<template>
  <Transition name="fade">
    <div v-if="open"
         class="fixed inset-0 z-40 flex items-center justify-center p-4"
         @click.self="emit('close')">
      <div class="absolute inset-0 bg-slate-900/50" />
      <div class="relative card max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-slate-900 mb-1">Submit Evaluation</h3>
        <p class="text-sm text-slate-500 mb-4">Provide a score and feedback for this session.</p>

        <form @submit.prevent="submit" novalidate class="space-y-4">
          <div>
            <label class="label" for="fm-score">Score (0–100) <span class="text-red-500">*</span></label>
            <input id="fm-score" type="number" v-model="form.score"
                   class="input" :class="{ 'input--error': errors.score }"
                   min="0" max="100" placeholder="e.g. 78" />
            <p v-if="errors.score" class="mt-1 text-sm text-red-500">{{ errors.score }}</p>
          </div>
          <div>
            <label class="label" for="fm-feedback">Feedback <span class="text-red-500">*</span></label>
            <textarea id="fm-feedback" v-model="form.feedback_text" rows="4"
                      class="input" :class="{ 'input--error': errors.feedback_text }"
                      placeholder="Describe the student's performance…" />
            <p v-if="errors.feedback_text" class="mt-1 text-sm text-red-500">
              {{ errors.feedback_text }}
            </p>
          </div>
          <div class="flex justify-end gap-2 pt-1">
            <button type="button" class="btn-secondary" @click="emit('close')" :disabled="submitting">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Submitting…' : 'Submit' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Transition>
</template>

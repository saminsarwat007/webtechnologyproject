<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true }
})

const palette = {
  pending:  'bg-amber-100  text-amber-800',
  reviewed: 'bg-blue-100   text-blue-800',
  accepted: 'bg-emerald-100 text-emerald-800',
  rejected: 'bg-red-100    text-red-800'
}

const classes = computed(() => palette[props.status] ?? 'bg-slate-100 text-slate-700')
const label   = computed(() => props.status.charAt(0).toUpperCase() + props.status.slice(1))
</script>

<template>
  <span class="badge" :class="classes">{{ label }}</span>
</template>

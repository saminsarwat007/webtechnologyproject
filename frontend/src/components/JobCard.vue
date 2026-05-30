<script setup>
import { computed } from 'vue'

const props = defineProps({
  job:           { type: Object,  required: true },
  alreadyApplied:{ type: Boolean, default: false }
})

defineEmits(['apply', 'view-detail'])

const typeLabels = {
  internship: 'Internship',
  fulltime:   'Full-time',
  parttime:   'Part-time'
}
const typePalette = {
  internship: 'bg-blue-100   text-blue-800',
  fulltime:   'bg-emerald-100 text-emerald-800',
  parttime:   'bg-orange-100 text-orange-800'
}

const typeClasses = computed(() => typePalette[props.job.type] ?? 'bg-slate-100 text-slate-700')
const typeLabel   = computed(() => typeLabels[props.job.type]   ?? props.job.type)

const shortDescription = computed(() => {
  const text = (props.job.description ?? '').replace(/\s+/g, ' ').trim()
  return text.length > 100 ? text.slice(0, 100) + '…' : text
})

const formattedDeadline = computed(() => {
  if (!props.job.deadline) return ''
  const d = new Date(props.job.deadline)
  return Number.isNaN(d.getTime())
    ? props.job.deadline
    : d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
})
</script>

<template>
  <article class="card p-5 flex flex-col gap-3 hover:shadow-md transition">
    <header class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-lg font-semibold text-slate-900 leading-tight">{{ job.title }}</h3>
        <p class="text-sm text-slate-500">{{ job.company_name }}</p>
      </div>
      <span class="badge" :class="typeClasses">{{ typeLabel }}</span>
    </header>

    <p class="text-sm text-slate-600 min-h-[40px]">{{ shortDescription }}</p>

    <footer class="flex items-center justify-between mt-auto pt-2 border-t border-slate-100">
      <span class="text-xs text-slate-500">Apply by {{ formattedDeadline }}</span>
      <div class="flex gap-2">
        <button class="btn-secondary !py-1.5 !px-3 text-xs" @click="$emit('view-detail', job)">View</button>
        <button v-if="!alreadyApplied" class="btn-primary !py-1.5 !px-3 text-xs" @click="$emit('apply', job)">Apply</button>
        <button v-else disabled class="btn-secondary !py-1.5 !px-3 text-xs !opacity-70">Applied</button>
      </div>
    </footer>
  </article>
</template>

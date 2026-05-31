<script setup>
/**
 * SlotCard — displays a single interview slot.
 * Emits: 'delete' (slot_id), 'book' (slot)
 */
defineProps({
  slot:    { type: Object,  required: true },
  isAdmin: { type: Boolean, default: false }
})

const emit = defineEmits(['delete', 'book'])

function formatDateTime (iso) {
  if (!iso) return ''
  const d = new Date(iso.replace(' ', 'T'))
  if (Number.isNaN(d.getTime())) return iso
  return d.toLocaleString(undefined, {
    weekday: 'short', day: 'numeric', month: 'short',
    year: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}
</script>

<template>
  <div class="card p-4 flex flex-col gap-3">
    <div class="flex items-start justify-between">
      <span class="badge text-xs"
            :class="slot.is_booked
              ? 'bg-amber-50 text-amber-700'
              : 'bg-emerald-50 text-emerald-700'">
        {{ slot.is_booked ? 'Booked' : 'Available' }}
      </span>
      <button v-if="isAdmin && !slot.is_booked"
              class="text-xs text-red-500 hover:text-red-700"
              @click="emit('delete', slot.slot_id)">
        Delete
      </button>
    </div>

    <div>
      <p class="text-sm font-semibold text-slate-900">{{ formatDateTime(slot.scheduled_at) }}</p>
      <p v-if="slot.interviewer_name" class="text-xs text-slate-500 mt-1">
        Interviewer: {{ slot.interviewer_name }}
      </p>
    </div>

    <button v-if="!isAdmin && !slot.is_booked"
            class="btn-primary text-sm"
            @click="emit('book', slot)">
      Book this slot
    </button>
    <p v-else-if="!isAdmin && slot.is_booked"
       class="text-xs text-slate-400 italic">Already booked</p>
  </div>
</template>

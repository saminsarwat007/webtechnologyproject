import { reactive } from 'vue'

/**
 * Global toast bus. The single <ToastNotification /> in App.vue reads from
 * this reactive list; any view can call useToast().success('hi').
 */
const state = reactive({
  toasts: [] // { id, message, type }
})

let nextId = 1

function push (message, type = 'info', timeout = 3000) {
  const id = nextId++
  state.toasts.push({ id, message, type })
  setTimeout(() => dismiss(id), timeout)
}

function dismiss (id) {
  const idx = state.toasts.findIndex(t => t.id === id)
  if (idx !== -1) state.toasts.splice(idx, 1)
}

export function useToast () {
  return {
    state,
    success: (msg, t) => push(msg, 'success', t),
    error:   (msg, t) => push(msg, 'error', t),
    info:    (msg, t) => push(msg, 'info', t),
    dismiss
  }
}

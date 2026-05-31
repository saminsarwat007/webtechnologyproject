import { ref } from 'vue'
import api from '../services/api.js'

/**
 * useInterview — shared async helpers for Module 8 (Mock Interview).
 */
export function useInterview () {
  const loading = ref(false)
  const error   = ref(null)

  // ---- Slots ----------------------------------------------------------
  async function getSlots () {
    loading.value = true; error.value = null
    try {
      const { data } = await api.get('/interviews/slots')
      return data?.data ?? []
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load slots.'
      return []
    } finally {
      loading.value = false
    }
  }

  async function createSlot (scheduled_at) {
    const { data } = await api.post('/interviews/slots', { scheduled_at })
    return data?.data ?? null
  }

  async function deleteSlot (id) {
    await api.delete(`/interviews/slots/${id}`)
  }

  // ---- Bookings -------------------------------------------------------
  async function getMySessions () {
    loading.value = true; error.value = null
    try {
      const { data } = await api.get('/interviews/mysessions')
      return data?.data ?? []
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load sessions.'
      return []
    } finally {
      loading.value = false
    }
  }

  async function book (slot_id, job_category) {
    const { data } = await api.post('/interviews/bookings', { slot_id, job_category })
    return data?.data ?? null
  }

  async function updateBooking (id, payload) {
    const { data } = await api.put(`/interviews/bookings/${id}`, payload)
    return data?.data ?? null
  }

  // ---- Admin ----------------------------------------------------------
  async function getAllSessions () {
    loading.value = true; error.value = null
    try {
      const { data } = await api.get('/interviews/admin/manage')
      return data?.data ?? []
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load sessions.'
      return []
    } finally {
      loading.value = false
    }
  }

  async function evaluate (id, score, feedback_text) {
    const { data } = await api.put(`/interviews/admin/evaluate/${id}`, { score, feedback_text })
    return data?.data ?? null
  }

  return {
    loading, error,
    getSlots, createSlot, deleteSlot,
    getMySessions, book, updateBooking,
    getAllSessions, evaluate
  }
}

import { ref } from 'vue'
import api from '../services/api.js'

/**
 * useForum — shared async helpers for Module 7 (Forum & Discussion).
 * Keeps API calls in one place so views stay lean.
 */
export function useForum () {
  const loading = ref(false)
  const error   = ref(null)

  async function getPosts () {
    loading.value = true; error.value = null
    try {
      const { data } = await api.get('/forums')
      return data?.data ?? []
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load posts.'
      return []
    } finally {
      loading.value = false
    }
  }

  async function getPost (id) {
    loading.value = true; error.value = null
    try {
      const { data } = await api.get(`/forums/${id}`)
      return data?.data ?? null
    } catch (e) {
      error.value = e?.response?.data?.message || 'Failed to load post.'
      return null
    } finally {
      loading.value = false
    }
  }

  async function createPost (payload) {
    const { data } = await api.post('/forums', payload)
    return data?.data ?? null
  }

  async function updatePost (id, payload) {
    const { data } = await api.put(`/forums/${id}`, payload)
    return data?.data ?? null
  }

  async function deletePost (id) {
    const { data } = await api.delete(`/forums/${id}`)
    return data?.data ?? null
  }

  async function toggleLike (id) {
    const { data } = await api.post(`/forums/${id}/like`)
    return data?.data ?? null
  }

  async function addComment (postId, content) {
    const { data } = await api.post(`/forums/${postId}/comments`, { content })
    return data?.data ?? null
  }

  async function deleteComment (postId, commentId) {
    await api.delete(`/forums/${postId}/comments/${commentId}`)
  }

  return {
    loading, error,
    getPosts, getPost, createPost, updatePost, deletePost,
    toggleLike, addComment, deleteComment
  }
}

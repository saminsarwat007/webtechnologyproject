import axios from 'axios'

/**
 * Shared Axios instance used by every view & store.
 *
 * - Attaches the JWT from localStorage on every request.
 * - On a 401 response, clears auth state and bounces the user back to /login.
 *
 * The router is imported lazily inside the interceptor to avoid a circular
 * import (router/index.js itself imports the auth store which uses this file).
 */
const api = axios.create({
  baseURL: '/api',
  headers: { 'Content-Type': 'application/json' },
  timeout: 15000
})

// ---- Request: attach Bearer token ---------------------------------------
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('cb_token')
  if (token) {
    config.headers = config.headers ?? {}
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// ---- Response: log out on 401 -------------------------------------------
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('cb_token')
      localStorage.removeItem('cb_user')

      // Avoid redirect loop if we're already on /login
      if (typeof window !== 'undefined' && !window.location.pathname.startsWith('/login')) {
        const { default: router } = await import('../router/index.js')
        router.push('/login')
      }
    }
    return Promise.reject(error)
  }
)

export default api

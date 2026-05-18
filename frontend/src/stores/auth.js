import { defineStore } from 'pinia'
import api from '../services/api.js'

/**
 * Decode a JWT payload without verification — we only need the claims
 * (user_id / email / role / full_name / exp) to populate UI state.
 * Verification is the backend's responsibility.
 */
function decodeToken (token) {
  try {
    const part = token.split('.')[1]
    if (!part) return null
    // base64url -> base64
    const b64 = part.replace(/-/g, '+').replace(/_/g, '/')
    const padded = b64 + '='.repeat((4 - (b64.length % 4)) % 4)
    const json = atob(padded)
    return JSON.parse(decodeURIComponent(escape(json)))
  } catch (_e) {
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    user: null // { user_id, email, role, full_name }
  }),

  getters: {
    isAuthenticated: (s) => !!s.token,
    isStudent:       (s) => s.user?.role === 'student',
    isAdmin:         (s) => s.user?.role === 'admin' || s.user?.role === 'superadmin',
    isSuperAdmin:    (s) => s.user?.role === 'superadmin'
  },

  actions: {
    /** Restore session from localStorage on app boot. */
    initFromStorage () {
      const token = localStorage.getItem('cb_token')
      if (!token) return
      const payload = decodeToken(token)
      if (!payload || (payload.exp && payload.exp * 1000 < Date.now())) {
        this.logout(false)
        return
      }
      this.token = token
      this.user = {
        user_id:   payload.user_id,
        email:     payload.email,
        role:      payload.role,
        full_name: payload.full_name
      }
    },

    /** POST /api/auth/login — stores token + user on success. */
    async login (email, password) {
      const { data } = await api.post('/auth/login', { email, password })
      const token = data?.data?.token
      const user  = data?.data?.user
      if (!token || !user) {
        throw new Error('Login response was malformed.')
      }
      this.token = token
      this.user  = user
      localStorage.setItem('cb_token', token)
      localStorage.setItem('cb_user', JSON.stringify(user))
      return user
    },

    /** POST /api/auth/register — auto-logs the user in. */
    async register (payload) {
      const { data } = await api.post('/auth/register', payload)
      const token = data?.data?.token
      const user  = data?.data?.user
      if (!token || !user) {
        throw new Error('Register response was malformed.')
      }
      this.token = token
      this.user  = user
      localStorage.setItem('cb_token', token)
      localStorage.setItem('cb_user', JSON.stringify(user))
      return user
    },

    /** Clear state + storage, and (optionally) bounce to /login. */
    async logout (redirect = true) {
      this.token = null
      this.user  = null
      localStorage.removeItem('cb_token')
      localStorage.removeItem('cb_user')
      if (redirect && typeof window !== 'undefined') {
        const { default: router } = await import('../router/index.js')
        router.push('/login')
      }
    }
  }
})

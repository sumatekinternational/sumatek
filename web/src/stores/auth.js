import { defineStore } from 'pinia'
import { api } from '../lib/api'

export const useAuth = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user: null,
  }),
  getters: {
    isAuthenticated: (s) => !!s.token,
    can: (s) => (perm) => s.user?.permissions?.includes(perm) ?? false,
  },
  actions: {
    async login(email, password) {
      const { data } = await api.post('/auth/login', { email, password, device_name: 'web' })
      this.token = data.token
      this.user = data.user
      localStorage.setItem('token', data.token)
    },
    async fetchMe() {
      const { data } = await api.get('/auth/me')
      this.user = data
    },
    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (_) {
        // ignore — clear locally regardless
      }
      this.token = null
      this.user = null
      localStorage.removeItem('token')
    },
  },
})

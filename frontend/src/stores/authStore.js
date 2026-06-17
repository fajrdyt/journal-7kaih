import { defineStore } from 'pinia'

import {
  getMe,
  login as loginApi,
  logout as logoutApi,
} from '../api/auth'

function unwrap(payload) {
  return payload?.data ?? payload
}

function normalizeRole(role) {
  if (!role) return null

  if (typeof role === 'string') return role

  return role.name ?? role.code ?? null
}

function normalizeUser(user) {
  if (!user) return null

  return {
    ...user,
    display_name: user.full_name ?? user.name ?? user.username ?? 'Pengguna',
    role: normalizeRole(user.role),
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,
    isStudent: (state) => ['siswa', 'student'].includes(state.user?.role),
  },

  actions: {
    async login(payload) {
      const response = await loginApi(payload)
      const data = unwrap(response)

      const token = data.access_token ?? data.token

      if (!token) {
        throw new Error('Token login tidak ditemukan pada response API.')
      }

      this.user = normalizeUser(data.user)
      this.token = token

      localStorage.setItem('token', token)

      if (!this.user) {
        await this.fetchUser()
      }
    },

    async fetchUser() {
      if (!this.token) return null

      this.loading = true

      try {
        const response = await getMe()
        const data = unwrap(response)

        this.user = normalizeUser(data.user ?? data)

        return this.user
      } catch (error) {
        this.clearAuth()

        return null
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        if (this.token) {
          await logoutApi()
        }
      } catch (error) {
        console.error(error)
      } finally {
        this.clearAuth()
      }
    },

    clearAuth() {
      this.user = null
      this.token = null

      localStorage.removeItem('token')
    },
  },
})

import { defineStore } from 'pinia'

import {
  getMe,
  getProfile as getProfileApi,
  login as loginApi,
  logout as logoutApi,
  updatePassword as updatePasswordApi,
  updateProfile as updateProfileApi,
} from '../api/auth'

function unwrap(payload) {
  const data = payload?.data ?? payload

  return data?.data ?? data
}

function normalizeRole(role) {
  if (!role) return null

  if (typeof role === 'string') {
    return role
  }

  return role.name ?? role.code ?? null
}

function normalizeUser(user) {
  if (!user) return null

  return {
    ...user,
    display_name:
      user.full_name ??
      user.name ??
      user.username ??
      'Pengguna',
    role: normalizeRole(user.role),
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.token),

    userRole: (state) => {
      return state.user?.role ?? null
    },

    isStudent: (state) => {
      return ['siswa', 'student'].includes(state.user?.role)
    },
  },

  actions: {
    async login(payload) {
      this.loading = true
      this.error = null

      try {
        const response = await loginApi(payload)
        const data = unwrap(response)

        const token = data?.access_token ?? data?.token

        if (!token) {
          throw new Error(
            'Token login tidak ditemukan pada response API.',
          )
        }

        this.user = normalizeUser(data?.user)
        this.token = token

        localStorage.setItem('token', token)

        if (!this.user) {
          await this.fetchUser()
        }

        return this.user
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          error.message ??
          'Login gagal.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) return null

      this.loading = true
      this.error = null

      try {
        const response = await getMe()
        const data = unwrap(response)

        this.user = normalizeUser(data?.user ?? data)

        return this.user
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          'Gagal mengambil data pengguna.'

        this.clearAuth()

        return null
      } finally {
        this.loading = false
      }
    },

    async fetchProfile() {
      if (!this.token) return null

      this.loading = true
      this.error = null

      try {
        const response = await getProfileApi()
        const data = unwrap(response)
        const profile = data?.user ?? data

        this.user = normalizeUser({
          ...this.user,
          ...profile,
        })

        return this.user
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          'Gagal mengambil profil.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async updateProfile(payload) {
      this.loading = true
      this.error = null

      try {
        const response = await updateProfileApi(payload)
        const data = unwrap(response)
        const updatedProfile = data?.user ?? data

        this.user = normalizeUser({
          ...this.user,
          ...updatedProfile,
        })

        return {
          user: this.user,
          message:
            response?.data?.message ??
            data?.message ??
            'Profil berhasil diperbarui.',
        }
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          'Gagal memperbarui profil.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async updatePassword(payload) {
      this.loading = true
      this.error = null

      try {
        const response = await updatePasswordApi(payload)
        const data = unwrap(response)

        return {
          ...data,
          message:
            response?.data?.message ??
            data?.message ??
            'Password berhasil diperbarui.',
        }
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          'Gagal memperbarui password.'

        throw error
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
      this.error = null

      localStorage.removeItem('token')
    },
  },
})

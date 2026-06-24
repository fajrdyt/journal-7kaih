import { defineStore } from 'pinia'

import {
  getMe,
  getProfile as getProfileApi,
  login as loginApi,
  logout as logoutApi,
  updatePassword as updatePasswordApi,
  updateProfile as updateProfileApi,
} from '@/api/auth'

const TOKEN_KEY = 'token'
const USER_KEY = 'user'

function getStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY) || 'null')
  } catch {
    localStorage.removeItem(USER_KEY)

    return null
  }
}

function unwrap(payload) {
  const data = payload?.data ?? payload

  return data?.data ?? data
}

function normalizeRole(role) {
  const rawRole =
    typeof role === 'object' && role !== null
      ? role.name ??
        role.code ??
        role.slug ??
        role.role_name ??
        null
      : role

  const value = String(rawRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const aliases = {
    student: 'siswa',
    siswa: 'siswa',
    teacher: 'guru',
    guru: 'guru',
    parent: 'orang_tua',
    orang_tua: 'orang_tua',
    orangtua: 'orang_tua',
    admin: 'admin',
  }

  return aliases[value] || value || null
}

function normalizeUser(user) {
  if (!user) {
    return null
  }

  return {
    ...user,
    display_name:
      user.display_name ??
      user.full_name ??
      user.name ??
      user.username ??
      'Pengguna',
    role: normalizeRole(user.role),
  }
}

function dashboardByRole(role) {
  const dashboards = {
    siswa: '/student/dashboard',
    guru: '/teacher/dashboard',
    orang_tua: '/parent/dashboard',
    admin: '/admin/dashboard',
  }

  return dashboards[normalizeRole(role)] || '/login'
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: normalizeUser(getStoredUser()),
    token: localStorage.getItem(TOKEN_KEY) || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated(state) {
      return Boolean(state.token)
    },

    isLoggedIn(state) {
      return Boolean(state.token)
    },

    userRole(state) {
      return normalizeRole(state.user?.role)
    },

    role(state) {
      return normalizeRole(state.user?.role)
    },

    isStudent(state) {
      return normalizeRole(state.user?.role) === 'siswa'
    },

    isSiswa(state) {
      return normalizeRole(state.user?.role) === 'siswa'
    },

    isGuru(state) {
      return normalizeRole(state.user?.role) === 'guru'
    },

    isOrangTua(state) {
      return normalizeRole(state.user?.role) === 'orang_tua'
    },

    isAdmin(state) {
      return normalizeRole(state.user?.role) === 'admin'
    },

    dashboardPath(state) {
      return dashboardByRole(state.user?.role)
    },
  },

  actions: {
    persistUser() {
      if (this.user) {
        localStorage.setItem(USER_KEY, JSON.stringify(this.user))

        return
      }

      localStorage.removeItem(USER_KEY)
    },

    setUser(user) {
      this.user = normalizeUser(user)
      this.persistUser()

      return this.user
    },

    async login(payloadOrIdentifier, password = null) {
      this.loading = true
      this.error = null

      try {
        const response = await loginApi(
          payloadOrIdentifier,
          password,
        )

        const data = unwrap(response)
        const token = data?.access_token ?? data?.token

        if (!token) {
          throw new Error(
            'Token login tidak ditemukan pada response API.',
          )
        }

        this.token = token
        localStorage.setItem(TOKEN_KEY, token)

        this.setUser(data?.user)

        if (!this.user) {
          await this.fetchUser()
        }

        return this.user
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          error.message ??
          'Login gagal.'

        this.clearAuth()

        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) {
        return null
      }

      this.loading = true
      this.error = null

      try {
        const response = await getMe()
        const data = unwrap(response)

        return this.setUser(data?.user ?? data)
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

    async fetchMe() {
      return this.fetchUser()
    },

    async fetchProfile() {
      if (!this.token) {
        return null
      }

      this.loading = true
      this.error = null

      try {
        const response = await getProfileApi()
        const data = unwrap(response)
        const profile = data?.user ?? data

        return this.setUser({
          ...this.user,
          ...profile,
        })
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

        this.setUser({
          ...this.user,
          ...updatedProfile,
        })

        return {
          user: this.user,
          message:
            response?.message ??
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
          ...(data ?? {}),
          message:
            response?.message ??
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
      } finally {
        this.clearAuth()
      }
    },

    clearAuth() {
      this.user = null
      this.token = null
      this.loading = false
      this.error = null

      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(USER_KEY)
    },
  },
})
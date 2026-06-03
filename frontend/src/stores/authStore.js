import { defineStore } from 'pinia'

import {
  login as loginApi,
  logout as logoutApi,
  getMe,
} from '../api/auth'


export const useAuthStore = defineStore('auth', {

  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    userRole: (state) => state.user?.role || null,

  },

  actions: {

    async login(payload) {

      const data = await loginApi(payload)

      this.user = data.user
      this.token = data.token

      localStorage.setItem('token', data.token)
    },

    async fetchUser() {

      if (!this.token) return

      try {

        const user = await getMe()

        this.user = user

      } catch (error) {

        this.logout()
      }
    },

    async logout() {

      try {

        await logoutApi()

      } catch (error) {

        console.error(error)
      }

      this.user = null
      this.token = null

      localStorage.removeItem('token')
    },

  },

})
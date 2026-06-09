import { defineStore } from 'pinia'
import {
  getTodayCheckin,
  submitCheckin,
  getCheckinHistory,
} from '@/api/checkin'

export const useCheckinStore = defineStore('checkin', {
  state: () => ({
    todayCheckin: {
      habits: [],
      notes: '',
    },

    history: [],

    loading: false,

    error: null,
  }),

  getters: {
    completedHabits(state) {
      return state.todayCheckin.habits.filter(
        (habit) => habit.completed,
      ).length
    },

    totalHabits(state) {
      return state.todayCheckin.habits.length
    },

    progressPercentage(state) {
      const total = state.todayCheckin.habits.length

      if (!total) return 0

      const completed = state.todayCheckin.habits.filter(
        (habit) => habit.completed,
      ).length

      return Math.round((completed / total) * 100)
    },
  },

  actions: {
    async fetchTodayCheckin() {
      this.loading = true
      this.error = null

      try {
        const response = await getTodayCheckin()

        this.todayCheckin = response.data.data
      } catch (error) {
        this.error =
          error.response?.data?.message ||
          'Gagal mengambil data check-in'

        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async fetchHistory() {
      this.loading = true
      this.error = null

      try {
        const response = await getCheckinHistory()

        this.history = response.data.data
      } catch (error) {
        this.error =
          error.response?.data?.message ||
          'Gagal mengambil riwayat check-in'

        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async saveCheckin(payload) {
      this.loading = true
      this.error = null

      try {
        const response = await submitCheckin(payload)

        await this.fetchTodayCheckin()

        return response.data
      } catch (error) {
        this.error =
          error.response?.data?.message ||
          'Gagal menyimpan check-in'

        throw error
      } finally {
        this.loading = false
      }
    },

    updateHabit(habitId, updates) {
      const habit = this.todayCheckin.habits.find(
        (item) => item.id === habitId,
      )

      if (!habit) return

      Object.assign(habit, updates)
    },

    resetCheckin() {
      this.todayCheckin = {
        habits: [],
        notes: '',
      }

      this.error = null
    },
  },
})
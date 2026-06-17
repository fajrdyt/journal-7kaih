import { defineStore } from 'pinia'

import {
  getHabits,
  getTodayCheckin,
  saveCheckin,
  getCheckinHistory,
  getStudentRecap,
} from '../api/checkin'

function unwrap(response) {
  return response?.data ?? response
}

function formatDateParam(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function currentMonthParams() {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)

  return {
    start_date: formatDateParam(firstDay),
    end_date: formatDateParam(lastDay),
  }
}

function normalizeRecapResponse(response) {
  const payload = response?.data ?? response
  const root = payload?.data ?? payload
  const summary = root?.summary ?? root?.recap ?? root ?? {}

  const rawHabits =
    root?.habit_summary ??
    root?.habit_summaries ??
    root?.habit_recaps ??
    root?.habit_statistics ??
    root?.habit_stats ??
    root?.habit_breakdown ??
    root?.habits ??
    summary?.habit_summary ??
    summary?.habits ??
    summary?.items ??
    []

  const habits = Array.isArray(rawHabits)
    ? rawHabits.map((habit) => {
        const completedCount = Number(
          habit.completed_count ??
            habit.done_count ??
            habit.total_done ??
            habit.done_total ??
            habit.completed_days ??
            habit.checked_count ??
            habit.value ??
            0,
        )

        const totalCount = Number(
          habit.total_count ??
            habit.total_items ??
            habit.total_days ??
            habit.total_checkins ??
            habit.target ??
            summary.total_checkins ??
            summary.total_days ??
            0,
        )

        const percentage = Number(
          habit.percentage ??
            habit.completion_percentage ??
            habit.completion_rate ??
            habit.percent ??
            (totalCount > 0 ? Math.round((completedCount / totalCount) * 100) : 0),
        )

        return {
          ...habit,
          id: habit.id ?? habit.habit_id ?? habit.habit?.id,
          name:
            habit.name ??
            habit.habit_name ??
            habit.habit?.name ??
            habit.label ??
            'Kebiasaan',
          completed_count: completedCount,
          total_count: totalCount,
          percentage,
        }
      })
    : []

  const totalItems = Number(
    summary.total_items ??
      summary.total_count ??
      root.total_items ??
      root.total_count ??
      0,
  )

  const completedItems = Number(
    summary.completed_items ??
      summary.completed_count ??
      summary.done_count ??
      summary.total_done ??
      root.completed_items ??
      root.completed_count ??
      root.done_count ??
      root.total_done ??
      habits.reduce((total, habit) => total + Number(habit.completed_count || 0), 0),
  )

  return {
    total_days:
      summary.total_days ??
      summary.total_checkins ??
      summary.checkin_count ??
      root.total_days ??
      root.total_checkins ??
      0,

    completed_days:
      summary.completed_days ??
      summary.complete_days ??
      summary.full_completed_days ??
      summary.completed_checkins ??
      summary.complete_checkins ??
      summary.full_checkins ??
      summary.perfect_days ??
      summary.all_done_days ??
      root.completed_days ??
      root.complete_days ??
      root.full_completed_days ??
      root.completed_checkins ??
      root.complete_checkins ??
      root.full_checkins ??
      root.perfect_days ??
      root.all_done_days ??
      0,
      
    total_items: totalItems,

    completed_items: completedItems,

    completion_percentage:
      summary.completion_percentage ??
      summary.completion_rate ??
      summary.percentage ??
      root.completion_percentage ??
      0,

    habits,

    raw: root,
  }
}

function extractHistoryList(response) {
  const payload = response?.data ?? response
  const root = payload?.data ?? payload

  if (Array.isArray(root)) return root
  if (Array.isArray(root?.data)) return root.data
  if (Array.isArray(root?.checkins)) return root.checkins
  if (Array.isArray(root?.items)) return root.items
  if (Array.isArray(root?.records)) return root.records

  return []
}

function normalizeHistoryItem(item) {
  const items = item.items ?? item.checkin_items ?? item.daily_checkin_items ?? []

  const completedCount = items.filter((checkinItem) => {
    return Boolean(checkinItem.is_done ?? checkinItem.completed)
  }).length

  return {
    ...item,
    checkin_date: item.checkin_date ?? item.date ?? item.created_at,
    notes: item.notes ?? '',
    items,
    completed_count: item.completed_count ?? item.done_count ?? completedCount,
    total_count: item.total_count ?? item.total_items ?? items.length,
  }
}

function extractData(payload) {
  const data = unwrap(payload)

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data?.data?.data)) return data.data.data

  return []
}

function extractObject(payload) {
  const data = unwrap(payload)

  if (data?.data && typeof data.data === 'object' && !Array.isArray(data.data)) {
    return data.data
  }

  return data ?? {}
}

function todayDate() {
  return new Date().toISOString().slice(0, 10)
}

export const useCheckinStore = defineStore('checkin', {
  state: () => ({
    habits: [],
    todayCheckin: {
      habits: [],
      notes: '',
      checkin_date: todayDate(),
    },
    history: [],
    recap: null,
    loading: false,
    error: null,
  }),

  getters: {
    completedCount: (state) => {
      return state.habits.filter((habit) => habit.is_done || habit.completed).length
    },

    totalHabits: (state) => {
      return state.habits.length
    },

    progressPercent: (state) => {
      if (!state.habits.length) return 0

      return Math.round(
        (state.habits.filter((habit) => habit.is_done || habit.completed).length / state.habits.length) * 100,
      )
    },

    // Alias untuk komponen lama
    completedHabits() {
      return this.completedCount
    },

    progressPercentage() {
      return this.progressPercent
    },
  },

  actions: {
    // Alias untuk komponen lama
    async fetchTodayCheckin() {
      return this.fetchToday()
    },

    async fetchToday() {
      this.loading = true
      this.error = null

      try {
        const habitsResponse = await getHabits()
        const habits = extractData(habitsResponse)

        let todayCheckin = {
          habits: [],
          notes: '',
          checkin_date: todayDate(),
        }

        try {
          const todayResponse = await getTodayCheckin()
          todayCheckin = {
            ...todayCheckin,
            ...extractObject(todayResponse),
          }
        } catch (error) {
          if (error.response?.status !== 404) {
            throw error
          }
        }

        const todayItems =
          todayCheckin?.items ??
          todayCheckin?.checkin_items ??
          todayCheckin?.habits ??
          []

        const mappedHabits = habits.map((habit) => {
          const item = todayItems.find((checkinItem) => {
            return (
              Number(checkinItem.habit_id) === Number(habit.id) ||
              Number(checkinItem.habit?.id) === Number(habit.id) ||
              Number(checkinItem.id) === Number(habit.id)
            )
          })

          const isDone = Boolean(item?.is_done ?? item?.completed ?? false)
          const notes = item?.notes ?? item?.note ?? ''

          return {
            ...habit,
            is_done: isDone,
            completed: isDone,
            notes,
            note: notes,
            activity_context: item?.activity_context ?? 'rumah',
          }
        })

        this.habits = mappedHabits
        this.todayCheckin = {
          ...todayCheckin,
          habits: mappedHabits,
          notes: todayCheckin?.notes ?? '',
          checkin_date: todayCheckin?.checkin_date ?? todayDate(),
        }
      } catch (error) {
        this.error = error.response?.data?.message ?? 'Gagal mengambil data check-in hari ini.'
        this.habits = []
        this.todayCheckin = {
          habits: [],
          notes: '',
          checkin_date: todayDate(),
        }
      } finally {
        this.loading = false
      }
    },

    toggleHabit(habitId) {
      this.habits = this.habits.map((habit) => {
        if (Number(habit.id) !== Number(habitId)) return habit

        const isDone = !(habit.is_done || habit.completed)

        return {
          ...habit,
          is_done: isDone,
          completed: isDone,
        }
      })

      this.todayCheckin.habits = this.habits
    },

    updateHabitNote(habitId, notes) {
      this.habits = this.habits.map((habit) => {
        if (Number(habit.id) !== Number(habitId)) return habit

        return {
          ...habit,
          notes,
          note: notes,
        }
      })

      this.todayCheckin.habits = this.habits
    },

    // Alias untuk HabitCard lama
    updateHabit(habitId, updates) {
      this.habits = this.habits.map((habit) => {
        if (Number(habit.id) !== Number(habitId)) return habit

        const nextIsDone = updates.completed ?? updates.is_done ?? habit.is_done ?? false
        const nextNotes = updates.note ?? updates.notes ?? habit.notes ?? ''

        return {
          ...habit,
          is_done: Boolean(nextIsDone),
          completed: Boolean(nextIsDone),
          notes: nextNotes,
          note: nextNotes,
        }
      })

      this.todayCheckin.habits = this.habits
    },

    // Alias untuk komponen lama
    async saveCheckin() {
      return this.submitToday(this.todayCheckin.notes)
    },

    async submitToday(notes = '') {
      this.loading = true
      this.error = null

      try {
        const payload = {
          notes,
          items: this.habits.map((habit) => ({
            habit_id: habit.id,
            is_done: Boolean(habit.is_done || habit.completed),
            activity_context: habit.activity_context ?? 'rumah',
            notes: habit.notes ?? habit.note ?? null,
          })),
        }

        const response = await saveCheckin(payload)
        await this.fetchToday()

        return unwrap(response)
      } catch (error) {
        this.error = error.response?.data?.message ?? 'Gagal menyimpan check-in.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchHistory(params = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await getCheckinHistory({
          per_page: 30,
          ...params,
        })

        const list = extractHistoryList(response)

        this.history = list.map(normalizeHistoryItem)

        console.log('History response:', response.data)
        console.log('Parsed history:', this.history)
      } catch (error) {
        this.error = error.response?.data?.message ?? 'Gagal mengambil riwayat check-in.'
        this.history = []

        console.error(error)
      } finally {
        this.loading = false
      } 
    },

    async fetchRecap(params = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await getStudentRecap({
          ...currentMonthParams(),
          ...params,
        })

        this.recap = normalizeRecapResponse(response)

        console.log('Recap response:', response.data)
        console.log('Parsed recap:', this.recap)
      } catch (error) {
        this.error = error.response?.data?.message ?? 'Gagal mengambil rekap.'
        this.recap = null

        console.error(error)
      } finally {
        this.loading = false
      }
    },
  },
})
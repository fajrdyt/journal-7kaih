import { defineStore } from 'pinia'

import {
  getChildCheckins,
  getChildRecap,
  getParentCheckinDetail,
  getParentChildren,
  validateHomeCheckin,
  validateParentCheckinItem,
} from '../api/parent'

function unwrap(response) {
  return response?.data ?? response
}

function extractObject(response) {
  const payload = unwrap(response)
  const data = payload?.data ?? payload

  if (data && typeof data === 'object' && !Array.isArray(data)) {
    return data
  }

  return {}
}

function extractList(response) {
  const payload = unwrap(response)
  const data = payload?.data ?? payload

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data
  if (Array.isArray(data?.children)) return data.children
  if (Array.isArray(data?.students)) return data.students
  if (Array.isArray(data?.checkins)) return data.checkins
  if (Array.isArray(data?.items)) return data.items
  if (Array.isArray(data?.records)) return data.records

  return []
}

function normalizeDateOnly(value) {
  if (!value) return null

  const rawValue = String(value)

  if (/^\d{4}-\d{2}-\d{2}$/.test(rawValue)) {
    return rawValue
  }

  const date = new Date(rawValue)

  if (Number.isNaN(date.getTime())) {
    return rawValue.slice(0, 10)
  }

  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Jakarta',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date)

  const year = parts.find((part) => part.type === 'year')?.value
  const month = parts.find((part) => part.type === 'month')?.value
  const day = parts.find((part) => part.type === 'day')?.value

  if (!year || !month || !day) {
    return rawValue.slice(0, 10)
  }

  return `${year}-${month}-${day}`
}

function normalizeChild(child) {
  const student = child?.student ?? child

  return {
    ...child,
    ...student,
    id: student?.id ?? child?.student_id ?? child?.id,
    full_name:
      student?.full_name ??
      student?.name ??
      child?.student_name ??
      'Siswa',
    username:
      student?.username ??
      child?.username ??
      null,
    class:
      student?.class ??
      student?.class_room ??
      student?.classRoom ??
      child?.class ??
      null,
  }
}

function normalizeCheckinItem(item) {
  const habit = item?.habit ?? {}

  return {
    ...item,
    id: item?.id,
    habit_id: item?.habit_id ?? habit?.id,
    habit: {
      ...habit,
      id: habit?.id ?? item?.habit_id,
      name:
        habit?.name ??
        item?.habit_name ??
        'Kebiasaan',
      code:
        habit?.code ??
        item?.habit_code ??
        null,
    },
    is_done: Boolean(
      item?.is_done ??
      item?.completed ??
      false,
    ),
    notes:
      item?.notes ??
      item?.note ??
      '',
    validations: Array.isArray(item?.validations)
      ? item.validations
      : [],
  }
}

function normalizeCheckin(checkin) {
  const rawItems =
    checkin?.items ??
    checkin?.checkin_items ??
    checkin?.daily_checkin_items ??
    []

  const items = Array.isArray(rawItems)
    ? rawItems.map(normalizeCheckinItem)
    : []

  const completedCount = items.filter((item) => item.is_done).length

  return {
    ...checkin,
    checkin_date: normalizeDateOnly(
      checkin?.checkin_date ??
      checkin?.date ??
      checkin?.created_at,
    ),
    notes: checkin?.notes ?? '',
    items,
    completed_count:
      checkin?.completed_count ??
      checkin?.done_count ??
      checkin?.summary?.total_done ??
      completedCount,
    total_count:
      checkin?.total_count ??
      checkin?.total_items ??
      checkin?.summary?.total_habits ??
      items.length,
  }
}

function normalizeRecap(response) {
  const root = extractObject(response)

  return {
    ...root,
    total_days: Number(
      root?.total_days ??
      root?.total_checkins ??
      root?.summary?.total_days ??
      root?.summary?.total_checkins ??
      0,
    ),
    completed_days: Number(
      root?.completed_days ??
      root?.complete_days ??
      root?.summary?.completed_days ??
      root?.summary?.complete_days ??
      0,
    ),
    completion_percentage: Number(
      root?.completion_percentage ??
      root?.percentage ??
      root?.summary?.completion_percentage ??
      0,
    ),
    habits:
      root?.habit_summary ??
      root?.habit_summaries ??
      root?.habits ??
      root?.summary?.habits ??
      [],
    raw: root,
  }
}

export const useParentStore = defineStore('parent', {
  state: () => ({
    children: [],
    selectedChildId: null,
    checkins: [],
    currentCheckin: null,
    recap: null,

    loadingChildren: false,
    loadingCheckins: false,
    loadingDetail: false,
    loadingRecap: false,
    validating: false,

    error: null,
  }),

  getters: {
    selectedChild(state) {
      return (
        state.children.find((child) => {
          return Number(child.id) === Number(state.selectedChildId)
        }) ?? null
      )
    },

    currentItems(state) {
      return state.currentCheckin?.items ?? []
    },

    completedItemCount(state) {
      return state.currentCheckin?.items?.filter((item) => {
        return item.is_done
      }).length ?? 0
    },

    totalItemCount(state) {
      return state.currentCheckin?.items?.length ?? 0
    },
  },

  actions: {
    clearError() {
      this.error = null
    },

    setSelectedChild(childId) {
      this.selectedChildId = childId
        ? Number(childId)
        : null
    },

    async fetchChildren() {
      this.loadingChildren = true
      this.error = null

      try {
        const response = await getParentChildren()
        const children = extractList(response).map(normalizeChild)

        this.children = children

        if (
          !this.selectedChildId &&
          children.length
        ) {
          this.selectedChildId = Number(children[0].id)
        }

        return children
      } catch (error) {
        this.children = []
        this.selectedChildId = null

        this.error =
          error.response?.data?.message ??
          'Gagal mengambil daftar anak.'

        throw error
      } finally {
        this.loadingChildren = false
      }
    },

    async fetchChildCheckins(studentId, params = {}) {
      const resolvedStudentId =
        studentId ?? this.selectedChildId

      if (!resolvedStudentId) {
        this.checkins = []
        return []
      }

      this.loadingCheckins = true
      this.error = null

      try {
        this.selectedChildId = Number(resolvedStudentId)

        const response = await getChildCheckins(
          resolvedStudentId,
          params,
        )

        const checkins = extractList(response).map(normalizeCheckin)

        this.checkins = checkins

        return checkins
      } catch (error) {
        this.checkins = []

        this.error =
          error.response?.data?.message ??
          'Gagal mengambil riwayat check-in anak.'

        throw error
      } finally {
        this.loadingCheckins = false
      }
    },

    async fetchCheckinDetail(checkinId) {
      if (!checkinId) return null

      this.loadingDetail = true
      this.error = null

      try {
        const response = await getParentCheckinDetail(checkinId)
        const checkin = normalizeCheckin(extractObject(response))

        this.currentCheckin = checkin

        return checkin
      } catch (error) {
        this.currentCheckin = null

        this.error =
          error.response?.data?.message ??
          'Gagal mengambil detail check-in.'

        throw error
      } finally {
        this.loadingDetail = false
      }
    },

    async validateHome(checkinId, payload = {}) {
      if (!checkinId || this.validating) return null

      this.validating = true
      this.error = null

      try {
        const response = await validateHomeCheckin(
          checkinId,
          payload,
        )

        await this.fetchCheckinDetail(checkinId)

        return extractObject(response)
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          'Gagal memvalidasi check-in.'

        throw error
      } finally {
        this.validating = false
      }
    },

    async validateItem(itemId, payload = {}) {
      if (!itemId || this.validating) return null

      this.validating = true
      this.error = null

      try {
        const response = await validateParentCheckinItem(
          itemId,
          payload,
        )

        if (this.currentCheckin?.id) {
          await this.fetchCheckinDetail(
            this.currentCheckin.id,
          )
        }

        return extractObject(response)
      } catch (error) {
        this.error =
          error.response?.data?.message ??
          'Gagal memvalidasi kebiasaan.'

        throw error
      } finally {
        this.validating = false
      }
    },

    async fetchChildRecap(studentId, params = {}) {
      const resolvedStudentId =
        studentId ?? this.selectedChildId

      if (!resolvedStudentId) {
        this.recap = null
        return null
      }

      this.loadingRecap = true
      this.error = null

      try {
        this.selectedChildId = Number(resolvedStudentId)

        const response = await getChildRecap(
          resolvedStudentId,
          params,
        )

        this.recap = normalizeRecap(response)

        return this.recap
      } catch (error) {
        this.recap = null

        this.error =
          error.response?.data?.message ??
          'Gagal mengambil rekap anak.'

        throw error
      } finally {
        this.loadingRecap = false
      }
    },

    resetParentData() {
      this.children = []
      this.selectedChildId = null
      this.checkins = []
      this.currentCheckin = null
      this.recap = null
      this.error = null
    },
  },
})

<script setup>
import { computed, onMounted, ref } from 'vue'

import { validationApi } from '@/api/validation'

const TOTAL_HABITS = 7

const children = ref([])
const selectedStudentId = ref('')
const selectedMonth = ref(getCurrentMonth())
const historyItems = ref([])
const selectedCheckin = ref(null)

const loadingChildren = ref(false)
const loadingHistory = ref(false)
const loadingDetail = ref(false)
const errorMessage = ref('')
const detailError = ref('')
const detailOpen = ref(false)

const selectedChild = computed(() => {
  return children.value.find(
    (child) => String(child.student_id) === String(selectedStudentId.value),
  )
})

const periodLabel = computed(() => formatMonth(selectedMonth.value))

const summary = computed(() => {
  const submitted = historyItems.value.filter(
    (item) => item.status === 'submitted',
  ).length

  const missed = historyItems.value.filter(
    (item) => item.status === 'missed',
  ).length

  const totalDone = historyItems.value.reduce(
    (total, item) => total + getDoneCount(item),
    0,
  )

  const totalPossible = submitted * TOTAL_HABITS

  return {
    submitted,
    missed,
    totalDays: historyItems.value.length,
    completion:
      totalPossible > 0
        ? Math.round((totalDone / totalPossible) * 100)
        : 0,
  }
})

onMounted(loadChildren)

async function loadChildren() {
  try {
    loadingChildren.value = true
    errorMessage.value = ''

    const response = await validationApi.getChildren()
    const payload = response.data?.data

    children.value = normalizeChildren(payload)

    if (children.value.length === 0) {
      selectedStudentId.value = ''
      historyItems.value = []
      return
    }

    selectedStudentId.value = String(children.value[0].student_id)
    await loadHistory()
  } catch (error) {
    errorMessage.value = getApiError(
      error,
      'Gagal mengambil daftar anak.',
    )
  } finally {
    loadingChildren.value = false
  }
}

async function loadHistory() {
  const studentId = Number(selectedStudentId.value)

  if (!Number.isInteger(studentId) || studentId <= 0) {
    historyItems.value = []
    errorMessage.value = 'Data anak tidak memiliki ID siswa yang valid.'
    return
  }

  try {
    loadingHistory.value = true
    errorMessage.value = ''

    const period = getMonthPeriod(selectedMonth.value)
    const response = await validationApi.getChildCheckins(
      studentId,
      {
        start_date: period.startDate,
        end_date: period.endDate,
        per_page: 100,
      },
    )

    const payload = response.data?.data
    const checkins = Array.isArray(payload)
      ? payload
      : payload?.items || []

    historyItems.value = buildTimeline(
      checkins,
      period.startDate,
      period.endDate,
    )
  } catch (error) {
    historyItems.value = []
    errorMessage.value = getApiError(
      error,
      'Gagal mengambil riwayat check-in anak.',
    )
  } finally {
    loadingHistory.value = false
  }
}

function normalizeChildren(payload) {
  if (!Array.isArray(payload)) {
    return []
  }

  return payload
    .map((item) => {
      const student = item?.student ?? null
      const studentId = Number(
        student?.id ?? item?.student_id,
      )

      if (!Number.isInteger(studentId) || studentId <= 0) {
        return null
      }

      return {
        relation_id: item?.relation_id ?? null,
        relation_type: item?.relation_type ?? null,
        student_id: studentId,
        student_name:
          student?.full_name ??
          item?.student_name ??
          'Siswa',
        class: student?.class ?? item?.class ?? null,
      }
    })
    .filter(Boolean)
}

async function handleFilterChange() {
  closeDetail()
  await loadHistory()
}

async function openDetail(item) {
  if (!item.id) {
    return
  }

  try {
    detailOpen.value = true
    loadingDetail.value = true
    detailError.value = ''
    selectedCheckin.value = null

    const response =
      await validationApi.getParentCheckinDetail(item.id)

    selectedCheckin.value = response.data?.data || null
  } catch (error) {
    detailError.value = getApiError(
      error,
      'Gagal mengambil detail check-in.',
    )
  } finally {
    loadingDetail.value = false
  }
}

function closeDetail() {
  detailOpen.value = false
  selectedCheckin.value = null
  detailError.value = ''
}

function buildTimeline(checkins, startDate, endDate) {
  const checkinMap = new Map(
    checkins.map((item) => [normalizeDate(item.checkin_date), item]),
  )

  const timeline = []
  const start = parseDate(startDate)
  const cursor = parseDate(endDate)

  while (cursor >= start) {
    const date = formatDateKey(cursor)
    const checkin = checkinMap.get(date)

    if (checkin) {
      timeline.push({
        ...checkin,
        checkin_date: date,
        status: 'submitted',
      })
    } else {
      timeline.push({
        id: null,
        checkin_date: date,
        status: date === getToday() ? 'pending' : 'missed',
        notes: null,
        total_habits_done: 0,
        total_items_validated: 0,
        parent_pending_validation_count: 0,
        teacher_validated_items: 0,
      })
    }

    cursor.setDate(cursor.getDate() - 1)
  }

  return timeline
}

function getDoneCount(item) {
  return Number(
    item.total_habits_done ??
      item.summary?.total_done ??
      item.items?.filter((habit) => habit.is_done).length ??
      0,
  )
}

function getValidatedCount(item) {
  return Number(
    item.total_items_validated ??
      item.summary?.parent_validations ??
      0,
  )
}

function getStatusLabel(status) {
  const labels = {
    submitted: 'Sudah Check-in',
    missed: 'Terlewat',
    pending: 'Belum Check-in',
  }

  return labels[status] || 'Tidak diketahui'
}

function getStatusClass(status) {
  const classes = {
    submitted:
      'border-emerald-200 bg-emerald-50 text-emerald-700',
    missed: 'border-red-200 bg-red-50 text-red-700',
    pending: 'border-amber-200 bg-amber-50 text-amber-700',
  }

  return (
    classes[status] ||
    'border-slate-200 bg-slate-50 text-slate-600'
  )
}

function isParentValidated(item) {
  return Array.isArray(item.validations)
    ? item.validations.some(
        (validation) => validation.validator_role === 'orang_tua',
      )
    : false
}

function getClassLabel(child) {
  const classData = child?.class

  if (!classData) {
    return 'Belum terikat kelas'
  }

  return [classData.grade_level, classData.name]
    .filter(Boolean)
    .join(' - ')
}

function getMonthPeriod(monthValue) {
  const [year, month] = String(monthValue).split('-').map(Number)
  const start = new Date(year, month - 1, 1)
  const end = new Date(year, month, 0)
  const today = new Date()

  today.setHours(0, 0, 0, 0)

  if (end > today) {
    end.setTime(today.getTime())
  }

  return {
    startDate: formatDateKey(start),
    endDate: formatDateKey(end),
  }
}

function getCurrentMonth() {
  return getToday().slice(0, 7)
}

function getToday() {
  return formatDateKey(new Date())
}

function normalizeDate(value) {
  return String(value || '').slice(0, 10)
}

function parseDate(value) {
  const [year, month, day] = normalizeDate(value).split('-').map(Number)
  return new Date(year, month - 1, day)
}

function formatDateKey(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatLongDate(value) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(parseDate(value))
}

function formatMonth(value) {
  if (!value) {
    return '-'
  }

  const [year, month] = value.split('-').map(Number)

  return new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
  }).format(new Date(year, month - 1, 1))
}

function getApiError(error, fallback) {
  const errors = error.response?.data?.errors

  if (errors && typeof errors === 'object') {
    return Object.values(errors).flat().join(' ')
  }

  return error.response?.data?.message || fallback
}
</script>

<template>
  <section class="space-y-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-600">
            Riwayat Anak
          </p>
          <h1 class="mt-2 text-2xl font-bold text-slate-900">
            Riwayat Check-in Anak
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Pantau jurnal harian dan hari yang terlewat pada periode terpilih.
          </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[480px]">
          <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-600">Pilih Anak</span>
            <select
              v-model="selectedStudentId"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              :disabled="loadingChildren || !children.length"
              @change="handleFilterChange"
            >
              <option
                v-for="child in children"
                :key="child.student_id"
                :value="String(child.student_id)"
              >
                {{ child.student_name }}
              </option>
            </select>
          </label>

          <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-600">Bulan</span>
            <input
              v-model="selectedMonth"
              type="month"
              :max="getCurrentMonth()"
              class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              @change="handleFilterChange"
            />
          </label>
        </div>
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="selectedChild"
      class="rounded-3xl bg-gradient-to-r from-blue-600 to-sky-500 p-6 text-white shadow-sm"
    >
      <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-100">
            Anak yang Dipantau
          </p>
          <h2 class="mt-2 text-2xl font-bold">
            {{ selectedChild.student_name }}
          </h2>
          <p class="mt-1 text-sm text-blue-100">
            {{ getClassLabel(selectedChild) }} · {{ periodLabel }}
          </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-2xl bg-white/15 px-4 py-3 text-center">
            <strong class="block text-xl">{{ summary.totalDays }}</strong>
            <span class="text-xs text-blue-100">Hari</span>
          </div>
          <div class="rounded-2xl bg-white/15 px-4 py-3 text-center">
            <strong class="block text-xl">{{ summary.submitted }}</strong>
            <span class="text-xs text-blue-100">Check-in</span>
          </div>
          <div class="rounded-2xl bg-white/15 px-4 py-3 text-center">
            <strong class="block text-xl">{{ summary.missed }}</strong>
            <span class="text-xs text-blue-100">Terlewat</span>
          </div>
          <div class="rounded-2xl bg-white/15 px-4 py-3 text-center">
            <strong class="block text-xl">{{ summary.completion }}%</strong>
            <span class="text-xs text-blue-100">Selesai</span>
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="loadingChildren || loadingHistory"
      class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm"
    >
      <div class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"></div>
      <p class="mt-4 text-sm font-medium text-slate-500">Memuat riwayat check-in...</p>
    </div>

    <div
      v-else-if="!children.length"
      class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
    >
      <h2 class="text-lg font-bold text-slate-800">Belum ada anak yang terhubung</h2>
      <p class="mt-2 text-sm text-slate-500">
        Hubungi admin untuk mengatur relasi siswa dan orang tua.
      </p>
    </div>

    <div
      v-else
      class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
    >
      <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
        <h2 class="font-bold text-slate-900">Timeline {{ periodLabel }}</h2>
        <p class="mt-1 text-sm text-slate-500">Riwayat diurutkan dari tanggal terbaru.</p>
      </div>

      <div class="divide-y divide-slate-100">
        <article
          v-for="item in historyItems"
          :key="item.checkin_date"
          class="grid gap-4 px-5 py-5 transition hover:bg-slate-50 sm:px-6 lg:grid-cols-[minmax(190px,1fr)_minmax(150px,0.6fr)_minmax(220px,1fr)_auto] lg:items-center"
        >
          <div>
            <p class="font-bold text-slate-900">{{ formatLongDate(item.checkin_date) }}</p>
            <p class="mt-1 text-xs text-slate-400">
              {{ item.notes || 'Tidak ada catatan umum' }}
            </p>
          </div>

          <div>
            <span
              class="inline-flex rounded-full border px-3 py-1.5 text-xs font-semibold"
              :class="getStatusClass(item.status)"
            >
              {{ getStatusLabel(item.status) }}
            </span>
          </div>

          <div>
            <p class="text-sm font-semibold text-slate-700">
              {{ getDoneCount(item) }} dari {{ TOTAL_HABITS }} kebiasaan selesai
            </p>
            <p class="mt-1 text-xs text-slate-400">
              {{ getValidatedCount(item) }} item tervalidasi
            </p>
          </div>

          <button
            type="button"
            class="w-full rounded-2xl border px-4 py-2.5 text-sm font-semibold transition lg:w-auto"
            :class="
              item.id
                ? 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'
                : 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'
            "
            :disabled="!item.id"
            @click="openDetail(item)"
          >
            {{ item.id ? 'Lihat Detail' : 'Tidak Tersedia' }}
          </button>
        </article>
      </div>
    </div>

    <div
      v-if="detailOpen"
      class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/45 backdrop-blur-sm sm:items-center sm:p-5"
      @click.self="closeDetail"
    >
      <section class="max-h-[92vh] w-full overflow-y-auto rounded-t-3xl bg-white shadow-2xl sm:max-w-3xl sm:rounded-3xl">
        <header class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-100 bg-white px-5 py-5 sm:px-6">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600">Detail Jurnal</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">
              {{ selectedCheckin ? formatLongDate(selectedCheckin.checkin_date) : 'Memuat jurnal' }}
            </h2>
          </div>
          <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-500"
            aria-label="Tutup detail"
            @click="closeDetail"
          >
            ×
          </button>
        </header>

        <div v-if="loadingDetail" class="px-6 py-16 text-center">
          <div class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"></div>
          <p class="mt-4 text-sm text-slate-500">Memuat detail jurnal...</p>
        </div>

        <div
          v-else-if="detailError"
          class="m-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
        >
          {{ detailError }}
        </div>

        <div v-else-if="selectedCheckin" class="space-y-4 p-5 sm:p-6">
          <div
            v-if="selectedCheckin.notes"
            class="rounded-2xl border border-blue-100 bg-blue-50 p-4"
          >
            <p class="text-xs font-semibold text-blue-500">Catatan Umum</p>
            <p class="mt-2 text-sm leading-6 text-blue-900">{{ selectedCheckin.notes }}</p>
          </div>

          <article
            v-for="item in selectedCheckin.items"
            :key="item.id"
            class="rounded-2xl border border-slate-200 p-4"
          >
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
              <div>
                <h3 class="font-bold text-slate-900">{{ item.habit?.name || 'Kebiasaan' }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ item.notes || 'Tidak ada catatan' }}</p>
              </div>
              <div class="flex flex-wrap gap-2">
                <span
                  class="rounded-full px-3 py-1 text-xs font-semibold"
                  :class="item.is_done ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                >
                  {{ item.is_done ? 'Selesai' : 'Belum Selesai' }}
                </span>
                <span
                  v-if="isParentValidated(item)"
                  class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700"
                >
                  Divalidasi Orang Tua
                </span>
              </div>
            </div>
          </article>
        </div>
      </section>
    </div>
  </section>
</template>

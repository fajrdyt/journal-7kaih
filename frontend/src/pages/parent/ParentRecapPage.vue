<script setup>
import { computed, onMounted, ref } from 'vue'

import { validationApi } from '@/api/validation'

const children = ref([])
const selectedStudentId = ref('')
const selectedMonth = ref(getCurrentMonth())
const recap = ref(null)

const loadingChildren = ref(false)
const loadingRecap = ref(false)
const errorMessage = ref('')

const selectedChild = computed(() => {
  return children.value.find(
    (child) => String(child.student_id) === String(selectedStudentId.value),
  )
})

const summary = computed(() => {
  const source = recap.value?.summary || {}

  return {
    totalCheckins: Number(source.total_checkins || 0),
    totalItems: Number(source.total_items || 0),
    totalDone: Number(source.total_done_items || 0),
    completion: Math.round(Number(source.completion_percentage || 0)),
    parentValidated: Number(source.parent_validated_items || 0),
    teacherValidated: Number(source.teacher_validated_items || 0),
    pendingValidation: Number(source.pending_validation_items || 0),
  }
})

const habitRows = computed(() => {
  const rows = recap.value?.habit_summary

  if (!Array.isArray(rows)) {
    return []
  }

  return rows.map((item) => {
    const done = Number(item.done_count || 0)
    const notDone = Number(item.not_done_count || 0)
    const total = done + notDone

    return {
      ...item,
      done,
      notDone,
      percentage: total > 0 ? Math.round((done / total) * 100) : 0,
    }
  })
})

const dailyRows = computed(() => {
  const rows = recap.value?.daily_checkins

  return Array.isArray(rows)
    ? [...rows].sort((a, b) =>
        String(b.checkin_date).localeCompare(String(a.checkin_date)),
      )
    : []
})

const bestHabit = computed(() => {
  if (habitRows.value.length === 0) {
    return null
  }

  return [...habitRows.value].sort(
    (first, second) => second.percentage - first.percentage,
  )[0]
})

const missedDays = computed(() => {
  const period = recap.value?.period

  if (!period?.start_date || !period?.end_date) {
    return 0
  }

  const totalDays = countDays(period.start_date, period.end_date)

  return Math.max(totalDays - summary.value.totalCheckins, 0)
})

onMounted(async () => {
  await loadChildren()
})

async function loadChildren() {
  try {
    loadingChildren.value = true
    error.value = ''

    const response = await validationApi.getChildren()
    const payload = response.data?.data

    const items = Array.isArray(payload)
      ? payload
      : Array.isArray(payload?.items)
        ? payload.items
        : []

    children.value = items.filter(
      (item) => item?.student?.id,
    )

    if (!children.value.length) {
      selectedStudentId.value = ''
      recap.value = null
      return
    }

    selectedStudentId.value = String(
      children.value[0].student.id,
    )

    await loadRecap()
  } catch (err) {
    children.value = []
    selectedStudentId.value = ''
    recap.value = null

    error.value =
      err.response?.data?.message ??
      'Gagal mengambil daftar anak.'
  } finally {
    loadingChildren.value = false
  }
}

async function loadRecap() {
  const studentId = Number(selectedStudentId.value)

  if (!Number.isInteger(studentId) || studentId <= 0) {
    recap.value = null
    return
  }

  try {
    loadingRecap.value = true
    error.value = ''

    const period = resolveMonthPeriod(selectedMonth.value)

    const response = await validationApi.getChildRecap(
      studentId,
      {
        start_date: period.startDate,
        end_date: period.endDate,
      },
    )

    recap.value = response.data?.data ?? null
  } catch (err) {
    recap.value = null

    error.value =
      err.response?.data?.message ??
      'Gagal mengambil rekap perkembangan anak.'
  } finally {
    loadingRecap.value = false
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
  await loadRecap()
}

function getHabitName(item) {
  return item.habit?.name || 'Kebiasaan'
}

function getDailyStatus(item) {
  const total = Number(item.total_items || 0)
  const done = Number(item.done_items || 0)

  if (total > 0 && done >= total) {
    return {
      label: 'Lengkap',
      className:
        'border-emerald-200 bg-emerald-50 text-emerald-700',
    }
  }

  return {
    label: 'Belum Lengkap',
    className:
      'border-amber-200 bg-amber-50 text-amber-700',
  }
}

function getClassLabel() {
  const classData = recap.value?.student?.class || selectedChild.value?.class

  if (!classData) {
    return 'Belum terikat kelas'
  }

  return [classData.grade_level, classData.name]
    .filter(Boolean)
    .join(' - ')
}

function getStudentName() {
  return recap.value?.student?.full_name || selectedChild.value?.student_name || '-'
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

function countDays(startDate, endDate) {
  const start = parseDate(startDate)
  const end = parseDate(endDate)
  const difference = end.getTime() - start.getTime()

  return Math.floor(difference / 86400000) + 1
}

function getCurrentMonth() {
  return formatDateKey(new Date()).slice(0, 7)
}

function parseDate(value) {
  const [year, month, day] = String(value).slice(0, 10).split('-').map(Number)
  return new Date(year, month - 1, day)
}

function formatDateKey(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('id-ID', {
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
            Rekap Anak
          </p>
          <h1 class="mt-2 text-2xl font-bold text-slate-900">
            Rekap Perkembangan Anak
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Lihat penyelesaian kebiasaan dan perkembangan anak pada periode terpilih.
          </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[480px]">
          <label class="space-y-2">
            <span class="text-xs font-semibold text-slate-600">Pilih Anak</span>
            <select
                v-model="selectedStudentId"
                :disabled="loadingChildren || !children.length"
                @change="loadRecap"
                >
                <option
                    v-for="relation in children"
                    :key="relation.relation_id"
                    :value="String(relation.student.id)"
                >
                    {{ relation.student.full_name }}
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
      v-if="loadingChildren || loadingRecap"
      class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm"
    >
      <div class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"></div>
      <p class="mt-4 text-sm font-medium text-slate-500">Memuat rekap perkembangan...</p>
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

    <template v-else-if="recap">
      <div class="rounded-3xl bg-gradient-to-r from-blue-600 to-sky-500 p-6 text-white shadow-sm">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-100">
              Perkembangan {{ formatMonth(selectedMonth) }}
            </p>
            <h2 class="mt-2 text-2xl font-bold">{{ getStudentName() }}</h2>
            <p class="mt-1 text-sm text-blue-100">{{ getClassLabel() }}</p>
          </div>

          <div class="rounded-2xl bg-white/15 px-5 py-4">
            <p class="text-xs font-semibold text-blue-100">Kebiasaan Terbaik</p>
            <p class="mt-1 text-lg font-bold">
              {{ bestHabit ? getHabitName(bestHabit) : 'Belum ada data' }}
            </p>
            <p class="mt-1 text-sm text-blue-100">
              {{ bestHabit ? `${bestHabit.percentage}% penyelesaian` : 'Belum ada jurnal' }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold text-slate-400">Total Check-in</p>
          <p class="mt-2 text-3xl font-bold text-slate-900">{{ summary.totalCheckins }}</p>
          <p class="mt-1 text-sm text-slate-500">jurnal pada periode ini</p>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold text-slate-400">Hari Terlewat</p>
          <p class="mt-2 text-3xl font-bold text-red-600">{{ missedDays }}</p>
          <p class="mt-1 text-sm text-slate-500">hari tanpa jurnal</p>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold text-slate-400">Penyelesaian</p>
          <p class="mt-2 text-3xl font-bold text-blue-600">{{ summary.completion }}%</p>
          <p class="mt-1 text-sm text-slate-500">
            {{ summary.totalDone }} dari {{ summary.totalItems }} item
          </p>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-xs font-semibold text-slate-400">Validasi Orang Tua</p>
          <p class="mt-2 text-3xl font-bold text-emerald-600">{{ summary.parentValidated }}</p>
          <p class="mt-1 text-sm text-slate-500">kebiasaan tervalidasi</p>
        </article>
      </div>

      <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
          <h2 class="text-lg font-bold text-slate-900">Perkembangan per Kebiasaan</h2>
          <p class="mt-1 text-sm text-slate-500">
            Persentase dihitung berdasarkan jurnal pada periode terpilih.
          </p>
        </div>

        <div
          v-if="!habitRows.length"
          class="px-6 py-14 text-center text-sm text-slate-500"
        >
          Belum ada data kebiasaan pada periode ini.
        </div>

        <div v-else class="divide-y divide-slate-100">
          <article
            v-for="habit in habitRows"
            :key="habit.habit_id"
            class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(180px,1fr)_minmax(220px,1.4fr)_minmax(160px,0.8fr)] lg:items-center"
          >
            <div>
              <p class="font-bold text-slate-900">{{ getHabitName(habit) }}</p>
              <p class="mt-1 text-xs text-slate-400">
                {{ habit.done }} selesai · {{ habit.notDone }} belum selesai
              </p>
            </div>

            <div>
              <div class="flex items-center justify-between text-xs font-semibold text-slate-500">
                <span>Progres</span>
                <span>{{ habit.percentage }}%</span>
              </div>
              <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-slate-100">
                <div
                  class="h-full rounded-full bg-blue-600"
                  :style="{ width: `${habit.percentage}%` }"
                ></div>
              </div>
            </div>

            <div class="text-sm text-slate-600">
              <p>Orang tua: <strong class="text-slate-900">{{ habit.parent_validated_count || 0 }}</strong></p>
              <p class="mt-1">Guru: <strong class="text-slate-900">{{ habit.teacher_validated_count || 0 }}</strong></p>
            </div>
          </article>
        </div>
      </section>

      <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
          <h2 class="text-lg font-bold text-slate-900">Ringkasan Harian</h2>
          <p class="mt-1 text-sm text-slate-500">Daftar jurnal selama periode terpilih.</p>
        </div>

        <div
          v-if="!dailyRows.length"
          class="px-6 py-14 text-center text-sm text-slate-500"
        >
          Belum ada check-in pada periode ini.
        </div>

        <div v-else class="divide-y divide-slate-100">
          <article
            v-for="item in dailyRows"
            :key="item.id"
            class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(180px,1fr)_minmax(160px,0.7fr)_minmax(220px,1fr)] lg:items-center"
          >
            <div>
              <p class="font-bold text-slate-900">{{ formatDate(item.checkin_date) }}</p>
              <p class="mt-1 text-xs text-slate-400">{{ item.notes || 'Tidak ada catatan umum' }}</p>
            </div>

            <div>
              <span
                class="inline-flex rounded-full border px-3 py-1.5 text-xs font-semibold"
                :class="getDailyStatus(item).className"
              >
                {{ getDailyStatus(item).label }}
              </span>
            </div>

            <div class="text-sm text-slate-600">
              <p>
                Selesai:
                <strong class="text-slate-900">
                  {{ item.done_items || 0 }} / {{ item.total_items || 0 }}
                </strong>
              </p>
              <p class="mt-1 text-xs text-slate-400">
                Orang tua: {{ item.parent_validated_items || 0 }} · Guru:
                {{ item.teacher_validated_items || 0 }}
              </p>
            </div>
          </article>
        </div>
      </section>
    </template>
  </section>
</template>

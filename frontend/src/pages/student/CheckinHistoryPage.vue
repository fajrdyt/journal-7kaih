<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

import { useCheckinStore } from '../../stores/checkinStore'

const checkinStore = useCheckinStore()

const searchInput = ref('')
const monthInput = ref('all')
const activeSearch = ref('')
const activeMonth = ref('all')

const currentPage = ref(1)
const perPage = 5
const expandedId = ref(null)

const todayKey = ref(getLocalDateKey())

let dateRefreshTimer = null

function getLocalDateKey(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function getCheckinDateKey(item) {
  return String(item?.checkin_date ?? '').slice(0, 10)
}

function parseDate(dateValue) {
  if (!dateValue) return null

  const dateString = String(dateValue).slice(0, 10)
  const [year, month, day] = dateString.split('-').map(Number)

  if (!year || !month || !day) return null

  return new Date(year, month - 1, day)
}

function formatDate(dateValue) {
  const date = parseDate(dateValue)

  if (!date) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

function monthKey(dateValue) {
  const date = parseDate(dateValue)

  if (!date) return ''

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')

  return `${year}-${month}`
}

function formatMonthLabel(key) {
  if (!key) return '-'

  const [year, month] = key.split('-').map(Number)
  const date = new Date(year, month - 1, 1)

  return new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
  }).format(date)
}

function completedCount(item) {
  if (item.completed_count !== undefined) {
    return Number(item.completed_count)
  }

  if (item.done_items !== undefined) {
    return Number(item.done_items)
  }

  const items = item.items ?? []

  return items.filter((checkinItem) => {
    return Boolean(checkinItem.is_done ?? checkinItem.completed)
  }).length
}

function totalCount(item) {
  if (item.total_count !== undefined) {
    return Number(item.total_count)
  }

  if (item.total_items !== undefined) {
    return Number(item.total_items)
  }

  return item.items?.length ?? 0
}

function progressPercentage(item) {
  const total = totalCount(item)

  if (!total) return 0

  return Math.round((completedCount(item) / total) * 100)
}

function itemHabitName(item) {
  return (
    item.habit?.name ??
    item.habit_name ??
    item.name ??
    `Kebiasaan ${item.habit_id ?? ''}`
  )
}

function isItemDone(item) {
  return Boolean(item.is_done ?? item.completed)
}

function statusTheme(item) {
  const completed = completedCount(item)
  const total = totalCount(item)
  const itemDate = getCheckinDateKey(item)

  const isToday = itemDate === todayKey.value
  const isPast = Boolean(itemDate) && itemDate < todayKey.value

  if (total > 0 && completed >= total) {
    return {
      label: 'Lengkap',
      text: 'text-emerald-700',
      dotActive: 'bg-emerald-500',
      badge: 'bg-emerald-100 text-emerald-700',
      percentage: 'text-emerald-600',
      card: 'border-emerald-100 bg-white',
      icon: 'bg-emerald-50 text-emerald-600',
    }
  }

  if (isToday) {
    return {
      label: 'Dalam progres',
      text: 'text-amber-700',
      dotActive: 'bg-amber-400',
      badge: 'bg-amber-100 text-amber-700',
      percentage: 'text-amber-600',
      card: 'border-amber-100 bg-white',
      icon: 'bg-amber-50 text-amber-600',
    }
  }

  if (isPast && completed > 0) {
    return {
      label: 'Tidak lengkap',
      text: 'text-slate-600',
      dotActive: 'bg-slate-500',
      badge: 'bg-slate-100 text-slate-600',
      percentage: 'text-slate-500',
      card: 'border-slate-200 bg-white',
      icon: 'bg-slate-100 text-slate-500',
    }
  }

  if (isPast && completed === 0) {
    return {
      label: 'Tidak diisi',
      text: 'text-slate-400',
      dotActive: 'bg-slate-300',
      badge:
        'border border-dashed border-slate-300 bg-transparent text-slate-500',
      percentage: 'text-slate-400',
      card: 'border-dashed border-slate-300 bg-slate-50/60',
      icon: 'bg-slate-100 text-slate-400',
    }
  }

  return {
    label: 'Belum dimulai',
    text: 'text-slate-500',
    dotActive: 'bg-slate-400',
    badge: 'bg-slate-100 text-slate-600',
    percentage: 'text-slate-400',
    card: 'border-slate-200 bg-white',
    icon: 'bg-slate-100 text-slate-500',
  }
}

function applyFilters() {
  activeSearch.value = searchInput.value.trim().toLowerCase()
  activeMonth.value = monthInput.value
  currentPage.value = 1
}

function resetFilters() {
  searchInput.value = ''
  monthInput.value = 'all'
  activeSearch.value = ''
  activeMonth.value = 'all'
  currentPage.value = 1
}

function toggleDetail(id) {
  expandedId.value = expandedId.value === id ? null : id
}

const sortedHistory = computed(() => {
  return [...checkinStore.history].sort((first, second) => {
    const firstDate = parseDate(first.checkin_date)?.getTime() ?? 0
    const secondDate = parseDate(second.checkin_date)?.getTime() ?? 0

    return secondDate - firstDate
  })
})

const availableMonths = computed(() => {
  const months = sortedHistory.value
    .map((item) => monthKey(item.checkin_date))
    .filter(Boolean)

  return [...new Set(months)]
})

const filteredHistory = computed(() => {
  return sortedHistory.value.filter((item) => {
    const dateLabel = formatDate(item.checkin_date).toLowerCase()
    const notes = String(item.notes ?? '').toLowerCase()

    const matchesSearch =
      !activeSearch.value ||
      dateLabel.includes(activeSearch.value) ||
      notes.includes(activeSearch.value)

    const matchesMonth =
      activeMonth.value === 'all' ||
      monthKey(item.checkin_date) === activeMonth.value

    return matchesSearch && matchesMonth
  })
})

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredHistory.value.length / perPage))
})

const paginatedHistory = computed(() => {
  const start = (currentPage.value - 1) * perPage

  return filteredHistory.value.slice(start, start + perPage)
})

const groupedHistory = computed(() => {
  return paginatedHistory.value.reduce((groups, item) => {
    const key = monthKey(item.checkin_date)

    if (!groups[key]) {
      groups[key] = []
    }

    groups[key].push(item)

    return groups
  }, {})
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(totalPages.value, start + 4)

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  return pages
})

watch(totalPages, (value) => {
  if (currentPage.value > value) {
    currentPage.value = value
  }
})

onMounted(async () => {
  await checkinStore.fetchHistory()

  dateRefreshTimer = window.setInterval(() => {
    todayKey.value = getLocalDateKey()
  }, 60_000)
})

onUnmounted(() => {
  if (dateRefreshTimer) {
    window.clearInterval(dateRefreshTimer)
  }
})
</script>

<template>
  <section class="space-y-5 pb-4 sm:space-y-6 sm:pb-6 lg:pb-10">
    <!-- Page heading -->
    <div>
      <h1 class="text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl">
        Riwayat Check-in
      </h1>

      <p class="mt-2 text-sm leading-6 text-slate-500">
        Lihat daftar check-in yang sudah pernah kamu isi untuk memantau
        perkembangan kebiasaan harianmu.
      </p>
    </div>

    <!-- Filter -->
    <div
      class="rounded-2xl bg-white p-4 shadow-[0_14px_35px_rgba(15,23,42,0.06)] sm:p-5"
    >
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          >
            <circle
              cx="11"
              cy="11"
              r="6"
              stroke="currentColor"
              stroke-width="2"
            />
            <path
              d="m16 16 4 4"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
            />
          </svg>

          <input
            v-model="searchInput"
            type="search"
            class="h-12 w-full rounded-xl border border-transparent bg-indigo-50/70 pl-11 pr-4 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100"
            placeholder="Cari catatan..."
            @keyup.enter="applyFilters"
          >
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
          <div class="relative">
            <svg
              viewBox="0 0 24 24"
              fill="none"
              class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
              aria-hidden="true"
            >
              <rect
                x="4"
                y="6"
                width="16"
                height="14"
                rx="2"
                stroke="currentColor"
                stroke-width="1.8"
              />
              <path
                d="M8 3v6M16 3v6M4 11h16"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
              />
            </svg>

            <select
              v-model="monthInput"
              class="h-12 w-full min-w-0 appearance-none rounded-xl border border-transparent bg-indigo-50/70 pl-11 pr-10 text-sm font-semibold text-slate-700 outline-none transition focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100 sm:min-w-44"
            >
              <option value="all">
                Semua Bulan
              </option>

              <option
                v-for="month in availableMonths"
                :key="month"
                :value="month"
              >
                {{ formatMonthLabel(month) }}
              </option>
            </select>

            <svg
              viewBox="0 0 20 20"
              fill="none"
              class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              aria-hidden="true"
            >
              <path
                d="m5 7.5 5 5 5-5"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <button
            type="button"
            class="h-12 w-full rounded-xl bg-sky-600 px-6 text-sm font-bold text-white shadow-[0_10px_24px_rgba(2,132,199,0.2)] transition hover:bg-sky-700 active:scale-[0.98] sm:w-auto"
            @click="applyFilters"
          >
            Filter
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="checkinStore.loading && !checkinStore.history.length"
      class="rounded-2xl bg-white px-6 py-14 text-center shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
    >
      <p class="text-sm font-medium text-slate-500">
        Memuat riwayat check-in...
      </p>
    </div>

    <!-- Error -->
    <div
      v-else-if="checkinStore.error && !checkinStore.history.length"
      class="rounded-2xl border border-red-200 bg-red-50 px-6 py-5"
    >
      <p class="text-sm font-semibold text-red-700">
        {{ checkinStore.error }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-700"
        @click="checkinStore.fetchHistory()"
      >
        Coba Lagi
      </button>
    </div>

    <!-- Empty -->
    <div
      v-else-if="!filteredHistory.length"
      class="rounded-2xl bg-white px-6 py-14 text-center shadow-[0_12px_30px_rgba(15,23,42,0.05)]"
    >
      <div
        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-2xl text-sky-600"
      >
        ▣
      </div>

      <h2 class="mt-4 text-lg font-bold text-slate-900">
        Riwayat tidak ditemukan
      </h2>

      <p class="mt-2 text-sm text-slate-500">
        Belum ada check-in yang sesuai dengan pencarian atau filter kamu.
      </p>

      <button
        type="button"
        class="mt-5 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-700"
        @click="resetFilters"
      >
        Reset Filter
      </button>
    </div>

    <!-- History -->
    <div
      v-else
      class="space-y-6 sm:space-y-8"
    >
      <section
        v-for="(items, month) in groupedHistory"
        :key="month"
        class="space-y-4"
      >
        <div class="flex items-center gap-4">
          <div class="h-px flex-1 bg-slate-200" />

          <p
            class="text-[11px] font-bold uppercase tracking-[0.25em] text-slate-400"
          >
            {{ formatMonthLabel(month) }}
          </p>

          <div class="h-px flex-1 bg-slate-200" />
        </div>

        <article
          v-for="item in items"
          :key="item.id"
          class="group rounded-2xl border p-4 shadow-[0_12px_32px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_20px_45px_rgba(14,165,233,0.10)] sm:p-6"
          :class="statusTheme(item).card"
        >
          <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl transition-colors duration-300"
              :class="statusTheme(item).icon"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-5 w-5"
                aria-hidden="true"
              >
                <rect
                  x="5"
                  y="4"
                  width="14"
                  height="16"
                  rx="2"
                  stroke="currentColor"
                  stroke-width="1.8"
                />
                <path
                  d="M9 9h6M9 13h4"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                />
              </svg>
            </div>

            <div class="min-w-0 flex-1">
              <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
              >
                <div>
                  <h2 class="text-base font-extrabold text-slate-900">
                    {{ formatDate(item.checkin_date) }}
                  </h2>

                  <div class="mt-3 flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1">
                      <span
                        v-for="index in totalCount(item)"
                        :key="index"
                        class="h-2 w-2 rounded-full transition-colors duration-300"
                        :class="
                          index <= completedCount(item)
                            ? statusTheme(item).dotActive
                            : 'bg-slate-200'
                        "
                      />
                    </div>

                    <span
                      class="text-xs font-bold transition-colors duration-300"
                      :class="statusTheme(item).text"
                    >
                      {{ completedCount(item) }}/{{ totalCount(item) }} selesai
                    </span>

                    <span
                      class="text-xs font-semibold transition-colors duration-300"
                      :class="statusTheme(item).percentage"
                    >
                      {{ progressPercentage(item) }}%
                    </span>

                    <span
                      class="rounded-full px-2.5 py-1 text-[10px] font-bold"
                      :class="statusTheme(item).badge"
                    >
                      {{ statusTheme(item).label }}
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  class="inline-flex w-fit shrink-0 items-center gap-2 text-sm font-bold text-sky-700 transition hover:text-sky-900"
                  @click="toggleDetail(item.id)"
                >
                  {{ expandedId === item.id ? 'Tutup Detail' : 'Lihat Detail' }}

                  <svg
                    viewBox="0 0 20 20"
                    fill="none"
                    class="h-4 w-4 transition-transform"
                    :class="expandedId === item.id ? 'rotate-90' : ''"
                    aria-hidden="true"
                  >
                    <path
                      d="M4 10h12M12 6l4 4-4 4"
                      stroke="currentColor"
                      stroke-width="1.8"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                </button>
              </div>

              <p
                v-if="item.notes"
                class="mt-4 max-w-3xl break-words text-sm italic leading-6 text-slate-500"
              >
                “{{ item.notes }}”
              </p>

              <p
                v-else
                class="mt-4 text-sm italic text-slate-400"
              >
                Tidak ada catatan umum.
              </p>

              <div
                v-if="expandedId === item.id"
                class="mt-5 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-2"
              >
                <div
                  v-for="checkinItem in item.items"
                  :key="checkinItem.id ?? checkinItem.habit_id"
                  class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-800">
                      {{ itemHabitName(checkinItem) }}
                    </p>

                    <p
                      v-if="checkinItem.notes"
                      class="mt-1 break-words text-xs text-slate-500"
                    >
                      {{ checkinItem.notes }}
                    </p>
                  </div>

                  <span
                    class="shrink-0 rounded-full px-3 py-1 text-[11px] font-bold"
                    :class="
                      isItemDone(checkinItem)
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-slate-200 text-slate-500'
                    "
                  >
                    {{ isItemDone(checkinItem) ? 'Selesai' : 'Belum' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </section>

      <!-- Pagination -->
      <div
        class="flex flex-col items-stretch justify-between gap-4 border-t border-slate-200 pt-6 sm:flex-row sm:items-center"
      >
        <button
          type="button"
          class="w-full rounded-xl bg-indigo-50 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
          :disabled="currentPage === 1"
          @click="currentPage -= 1"
        >
          ← Sebelumnya
        </button>

        <div class="flex flex-wrap items-center justify-center gap-2">
          <button
            v-for="page in visiblePages"
            :key="page"
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-sm font-bold transition"
            :class="
              currentPage === page
                ? 'bg-sky-700 text-white shadow-sm'
                : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
            "
            @click="currentPage = page"
          >
            {{ page }}
          </button>

          <span
            v-if="totalPages > visiblePages.length"
            class="px-1 text-sm text-slate-400"
          >
            …
          </span>

          <span
            v-if="totalPages > visiblePages.length"
            class="px-2 text-sm font-semibold text-slate-500"
          >
            {{ totalPages }}
          </span>
        </div>

        <button
          type="button"
          class="w-full rounded-xl bg-indigo-50 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
          :disabled="currentPage === totalPages"
          @click="currentPage += 1"
        >
          Selanjutnya →
        </button>
      </div>
    </div>
  </section>
</template>

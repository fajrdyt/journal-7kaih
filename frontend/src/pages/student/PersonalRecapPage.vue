<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useCheckinStore } from '../../stores/checkinStore'

const router = useRouter()
const checkinStore = useCheckinStore()

const recap = computed(() => checkinStore.recap)

const totalCheckins = computed(() => {
  return Number(recap.value?.total_days ?? 0)
})

const completedDays = computed(() => {
  return Number(recap.value?.completed_days ?? 0)
})

const totalItems = computed(() => {
  return Number(recap.value?.total_items ?? 0)
})

const completedItems = computed(() => {
  return Number(recap.value?.completed_items ?? 0)
})

const completionPercentage = computed(() => {
  const value = Number(recap.value?.completion_percentage ?? 0)

  return Math.min(100, Math.max(0, value))
})

const habits = computed(() => {
  return Array.isArray(recap.value?.habits)
    ? recap.value.habits
    : []
})

const periodLabel = computed(() => {
  const period = recap.value?.raw?.period

  if (period?.start_date && period?.end_date) {
    return `${formatShortDate(period.start_date)} – ${formatShortDate(
      period.end_date,
    )}`
  }

  return new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric',
  }).format(new Date())
})

const RING_R = 54
const RING_CIRC = 2 * Math.PI * RING_R

const ringDashoffset = computed(() => {
  return (
    RING_CIRC -
    (completionPercentage.value / 100) * RING_CIRC
  )
})

const overallStatus = computed(() => {
  const percentage = completionPercentage.value

  if (percentage >= 100) {
    return {
      label: 'Sempurna',
      description:
        'Semua kebiasaan pada periode ini berhasil diselesaikan. Luar biasa!',
      badgeClass: 'bg-emerald-100 text-emerald-700',
      ringColor: '#10b981',
    }
  }

  if (percentage >= 75) {
    return {
      label: 'Baik',
      description:
        'Konsistensi kebiasaanmu sudah sangat baik. Pertahankan ritme ini.',
      badgeClass: 'bg-white/25 text-white',
      ringColor: '#ffffff',
    }
  }

  if (percentage >= 50) {
    return {
      label: 'Cukup Baik',
      description:
        'Kamu sudah berada di jalur yang tepat. Tetap lanjutkan dan tingkatkan konsistensimu.',
      badgeClass: 'bg-amber-100 text-amber-700',
      ringColor: '#fbbf24',
    }
  }

  if (percentage > 0) {
    return {
      label: 'Perlu Ditingkatkan',
      description:
        'Mulai dari langkah kecil dan tingkatkan konsistensi secara bertahap.',
      badgeClass: 'bg-orange-100 text-orange-700',
      ringColor: '#fb923c',
    }
  }

  return {
    label: 'Belum Dimulai',
    description:
      'Mulai isi check-in hari ini untuk membangun kebiasaan positifmu.',
    badgeClass: 'bg-white/20 text-white',
    ringColor: 'rgba(255,255,255,0.35)',
  }
})

function formatShortDate(dateValue) {
  if (!dateValue) return '-'

  const dateString = String(dateValue).slice(0, 10)
  const [year, month, day] = dateString.split('-').map(Number)

  if (!year || !month || !day) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(year, month - 1, day))
}

function habitPercentage(habit) {
  const percentage = Number(habit.percentage ?? 0)

  return Math.min(100, Math.max(0, percentage))
}

function habitCompletedCount(habit) {
  return Number(habit.completed_count ?? 0)
}

function habitTotalCount(habit) {
  return Number(
    habit.total_count ??
      totalCheckins.value ??
      0,
  )
}

function habitTheme(habit) {
  const percentage = habitPercentage(habit)

  if (percentage >= 100) {
    return {
      status: 'Sangat konsisten',
      barClass: 'bg-emerald-500',
      iconClass: 'bg-emerald-50 text-emerald-600',
      textClass: 'text-emerald-700',
      badgeClass: 'bg-emerald-100 text-emerald-700',
    }
  }

  if (percentage >= 75) {
    return {
      status: 'Konsisten',
      barClass: 'bg-sky-500',
      iconClass: 'bg-sky-50 text-sky-600',
      textClass: 'text-sky-700',
      badgeClass: 'bg-sky-100 text-sky-700',
    }
  }

  if (percentage >= 50) {
    return {
      status: 'Cukup konsisten',
      barClass: 'bg-amber-400',
      iconClass: 'bg-amber-50 text-amber-600',
      textClass: 'text-amber-700',
      badgeClass: 'bg-amber-100 text-amber-700',
    }
  }

  if (percentage > 0) {
    return {
      status: 'Perlu ditingkatkan',
      barClass: 'bg-orange-400',
      iconClass: 'bg-orange-50 text-orange-600',
      textClass: 'text-orange-700',
      badgeClass: 'bg-orange-100 text-orange-700',
    }
  }

  return {
    status: 'Belum dilakukan',
    barClass: 'bg-slate-300',
    iconClass: 'bg-slate-100 text-slate-400',
    textClass: 'text-slate-400',
    badgeClass: 'bg-slate-100 text-slate-500',
  }
}

function habitIcon(habit) {
  const code = String(habit.code ?? '').toUpperCase()
  const name = String(habit.name ?? '').toLowerCase()

  const icons = {
    BANGUN_PAGI: '☼',
    BERIBADAH: '✦',
    BEROLAHRAGA: '⚡',
    MAKAN_SEHAT_BERGIZI: '♨',
    GEMAR_BELAJAR: '▣',
    BERMASYARAKAT: '⌘',
    TIDUR_CEPAT: '☾',
  }

  if (icons[code]) return icons[code]

  if (name.includes('bangun')) return '☼'
  if (name.includes('ibadah')) return '✦'
  if (name.includes('olahraga')) return '⚡'
  if (name.includes('makan')) return '♨'
  if (name.includes('belajar')) return '▣'
  if (name.includes('masyarakat')) return '⌘'
  if (name.includes('tidur')) return '☾'

  return '✓'
}

function goToCheckin() {
  router.push('/student/checkin')
}

function goToHistory() {
  router.push('/student/history')
}

onMounted(async () => {
  await checkinStore.fetchRecap()
})
</script>

<template>
  <section class="space-y-5 pb-4 sm:space-y-6 sm:pb-6 lg:pb-10">
    <!-- Page header -->
    <div
      class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
    >
      <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl">
          Rekap Pribadi
        </h1>

        <p class="mt-2 text-sm leading-6 text-slate-500">
          Pantau perkembangan dan konsistensi tujuh kebiasaan harianmu.
        </p>
      </div>

      <div
        class="inline-flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm sm:w-fit"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          class="h-5 w-5 text-sky-600"
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

        {{ periodLabel }}
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="checkinStore.loading && !recap"
      class="rounded-2xl bg-white px-6 py-16 text-center shadow-sm"
    >
      <div
        class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-sky-100 border-t-sky-500"
      />

      <p class="mt-4 text-sm font-medium text-slate-500">
        Memuat data rekap...
      </p>
    </div>

    <!-- Error -->
    <div
      v-else-if="checkinStore.error && !recap"
      class="rounded-2xl border border-red-200 bg-red-50 px-6 py-5"
    >
      <p class="text-sm font-semibold text-red-700">
        {{ checkinStore.error }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-700"
        @click="checkinStore.fetchRecap()"
      >
        Coba Lagi
      </button>
    </div>

    <template v-else>
     <section
          class="relative overflow-hidden rounded-3xl hero-gradient px-5 py-6 text-white shadow-[0_18px_45px_rgba(2,132,199,0.22)] sm:px-8 sm:py-8"
        >
          <div
            class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-white/10"
          />

          <div
            class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_170px] lg:items-center"
          >
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  class="rounded-full bg-white/20 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-white sm:text-[11px]"
                >
                  Ringkasan Bulan Ini
                </span>

                <span
                  class="rounded-full px-3 py-1.5 text-[10px] font-bold sm:text-[11px]"
                  :class="overallStatus.badgeClass"
                >
                  {{ overallStatus.label }}
                </span>
              </div>

              <h2
                class="mt-5 max-w-2xl text-2xl font-extrabold leading-tight sm:text-3xl"
              >
                Konsistensi kebiasaanmu
              </h2>

              <div class="mt-3 flex items-end gap-2">
                <p
                  class="text-5xl font-extrabold leading-none text-white sm:text-6xl"
                >
                  {{ Math.round(completionPercentage) }}%
                </p>

                <span class="pb-1 text-sm font-semibold text-white/75">
                  bulan ini
                </span>
              </div>

              <p
                class="mt-4 max-w-2xl text-sm leading-6 text-white/85"
              >
                {{ overallStatus.description }}
              </p>

              <div class="mt-7 max-w-2xl">
                <div
                  class="mb-2.5 flex items-center justify-between gap-4 text-xs font-semibold"
                >
                  <span class="text-white/90">
                    {{ completedItems }} dari
                    {{ totalItems }} aktivitas selesai
                  </span>

                  <span class="shrink-0 text-white">
                    {{ Math.round(completionPercentage) }}%
                  </span>
                </div>

                <div
                  class="h-3 overflow-hidden rounded-full bg-white/25"
                >
                  <div
                    class="h-full rounded-full bg-white transition-all duration-700"
                    :style="{
                      width: `${completionPercentage}%`,
                    }"
                  />
                </div>
              </div>
            </div>

            <div class="hidden justify-end lg:flex">
              <div class="relative flex h-36 w-36 items-center justify-center">
                <svg
                  viewBox="0 0 144 144"
                  class="h-36 w-36 -rotate-90"
                  aria-hidden="true"
                >
                  <!-- Backing solid supaya angka & ring tetap kontras
                       di mana pun ring ini jatuh pada hero-gradient -->
                  <circle
                    cx="72"
                    cy="72"
                    r="46"
                    fill="rgba(255,255,255,0.94)"
                  />

                  <circle
                    cx="72"
                    cy="72"
                    :r="RING_R"
                    fill="none"
                    stroke="rgba(255,255,255,0.35)"
                    stroke-width="12"
                    stroke-linecap="round"
                  />

                  <circle
                    cx="72"
                    cy="72"
                    :r="RING_R"
                    fill="none"
                    :stroke="overallStatus.ringColor"
                    stroke-width="12"
                    stroke-linecap="round"
                    :stroke-dasharray="RING_CIRC"
                    :stroke-dashoffset="ringDashoffset"
                    style="
                      transition:
                        stroke-dashoffset 0.7s ease,
                        stroke 0.3s ease;
                    "
                  />
                </svg>

                <div
                  class="absolute flex flex-col items-center justify-center text-center"
                >
                  <p class="text-3xl font-extrabold leading-none text-primary-deep">
                    {{ Math.round(completionPercentage) }}%
                  </p>

                  <p class="mt-1 text-[11px] font-semibold text-primary-deep/70">
                    bulan ini
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      <!-- Summary cards -->
      <div class="grid gap-4 sm:gap-5 md:grid-cols-3">
        <!-- Total check-in -->
        <article
          class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_18px_38px_rgba(14,165,233,0.12)] sm:p-5"
        >
          <div class="flex items-center justify-between gap-4">
            <div>
              <p
                class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
              >
                Total Check-in
              </p>

              <div class="mt-2 flex items-end gap-2">
                <p class="text-3xl font-extrabold text-slate-950">
                  {{ totalCheckins }}
                </p>

                <span
                  class="pb-1 text-sm font-semibold text-slate-400"
                >
                  hari
                </span>
              </div>
            </div>

            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600 transition-colors duration-300 group-hover:bg-sky-500 group-hover:text-white"
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
          </div>
        </article>

        <!-- Completed days -->
        <article
          class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-[0_18px_38px_rgba(16,185,129,0.12)] sm:p-5"
        >
          <div class="flex items-center justify-between gap-4">
            <div>
              <p
                class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
              >
                Hari Lengkap
              </p>

              <div class="mt-2 flex items-end gap-2">
                <p class="text-3xl font-extrabold text-slate-950">
                  {{ completedDays }}
                </p>

                <span
                  class="pb-1 text-sm font-semibold text-slate-400"
                >
                  dari {{ totalCheckins }} hari
                </span>
              </div>
            </div>

            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 transition-colors duration-300 group-hover:bg-emerald-500 group-hover:text-white"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-5 w-5"
                aria-hidden="true"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="8"
                  stroke="currentColor"
                  stroke-width="1.8"
                />

                <path
                  d="m8.5 12 2.3 2.4 4.8-5"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>
        </article>

        <!-- Monthly progress -->
        <article
          class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-[0_10px_28px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-[0_18px_38px_rgba(245,158,11,0.12)] sm:p-5"
        >
          <div class="flex items-center justify-between gap-4">
            <div>
              <p
                class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
              >
                Progres Bulan Ini
              </p>

              <div class="mt-2 flex items-end gap-1">
                <p class="text-3xl font-extrabold text-slate-950">
                  {{ Math.round(completionPercentage) }}
                </p>

                <span
                  class="pb-1 text-base font-extrabold text-slate-500"
                >
                  %
                </span>
              </div>
            </div>

            <div
              class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 transition-colors duration-300 group-hover:bg-amber-400 group-hover:text-white"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-5 w-5"
                aria-hidden="true"
              >
                <path
                  d="M6 18V11M12 18V6M18 18V9"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                />
              </svg>
            </div>
          </div>
        </article>
      </div>

      <!-- Habit recap -->
      <section
        class="rounded-3xl border border-slate-200 bg-white p-4 shadow-[0_12px_36px_rgba(15,23,42,0.05)] sm:p-6"
      >
        <div
          class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center"
        >
          <div>
            <h2 class="text-xl font-extrabold text-slate-950">
              Rekap per Kebiasaan
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              Perbandingan jumlah hari berhasil untuk setiap kebiasaan.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 sm:w-auto"
              @click="goToHistory"
            >
              Lihat Riwayat
            </button>

            <button
              type="button"
              class="w-full rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700 sm:w-auto"
              @click="goToCheckin"
            >
              Isi Check-in
            </button>
          </div>
        </div>

        <div
          v-if="!habits.length"
          class="py-12 text-center"
        >
          <p class="text-sm font-semibold text-slate-500">
            Data rekap kebiasaan belum tersedia.
          </p>
        </div>

        <div
          v-else
          class="mt-5 space-y-3"
        >
          <article
            v-for="habit in habits"
            :key="habit.id ?? habit.name"
            class="group rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-sky-200 hover:bg-white hover:shadow-[0_12px_28px_rgba(14,165,233,0.08)] sm:px-5"
          >
            <div
              class="grid gap-4 md:grid-cols-12 md:items-center"
            >
              <!-- Habit identity -->
              <div
                class="flex min-w-0 items-center gap-3 md:col-span-4"
              >
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-bold"
                  :class="habitTheme(habit).iconClass"
                >
                  {{ habitIcon(habit) }}
                </div>

                <div class="min-w-0">
                  <h3
                    class="truncate text-sm font-bold text-slate-900"
                  >
                    {{ habit.name }}
                  </h3>

                  <p
                    class="mt-1 text-xs font-semibold"
                    :class="habitTheme(habit).textClass"
                  >
                    {{ habitTheme(habit).status }}
                  </p>
                </div>
              </div>

              <!-- Habit progress -->
              <div class="md:col-span-6">
                <div
                  class="mb-2 flex items-center justify-between text-xs font-semibold"
                >
                  <span class="text-slate-400">
                    Progres
                  </span>

                  <span :class="habitTheme(habit).textClass">
                    {{ Math.round(habitPercentage(habit)) }}%
                  </span>
                </div>

                <div
                  class="h-3 overflow-hidden rounded-full bg-slate-200"
                >
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="habitTheme(habit).barClass"
                    :style="{
                      width: `${habitPercentage(habit)}%`,
                    }"
                  />
                </div>
              </div>

              <!-- Habit result -->
              <div
                class="flex justify-start md:col-span-2 md:justify-end"
              >
                <span
                  class="whitespace-nowrap rounded-full px-3 py-1.5 text-[11px] font-bold"
                  :class="habitTheme(habit).badgeClass"
                >
                  {{ habitCompletedCount(habit) }}/{{
                    habitTotalCount(habit)
                  }}
                  hari
                </span>
              </div>
            </div>
          </article>
        </div>
      </section>
    </template>
  </section>
</template>

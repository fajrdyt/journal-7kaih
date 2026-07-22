<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'
import { useCheckinStore } from '../../stores/checkinStore'

const router = useRouter()
const authStore = useAuthStore()
const checkinStore = useCheckinStore()


const displayName = computed(() => {
  return (
    authStore.user?.display_name ??
    authStore.user?.full_name ??
    authStore.user?.name ??
    'Siswa'
  )
})

const totalHabits = computed(() => {
  return Number(checkinStore.totalHabits ?? 0)
})

const completedHabits = computed(() => {
  return Number(checkinStore.completedCount ?? 0)
})

const progressPercent = computed(() => {
  return Number(checkinStore.progressPercent ?? 0)
})

const safeProgressPercent = computed(() => {
  return Math.min(100, Math.max(0, progressPercent.value))
})

const remainingHabits = computed(() => {
  return Math.max(
    totalHabits.value - completedHabits.value,
    0,
  )
})

const isJournalComplete = computed(() => {
  return (
    totalHabits.value > 0 &&
    completedHabits.value >= totalHabits.value
  )
})

const journalStatus = computed(() => {
  if (!totalHabits.value) {
    return 'Belum tersedia'
  }

  return isJournalComplete.value
    ? 'Lengkap'
    : 'Belum lengkap'
})

const completionDescription = computed(() => {
  if (!totalHabits.value) {
    return 'Data kebiasaan belum tersedia.'
  }

  if (isJournalComplete.value) {
    return 'Semua kebiasaan hari ini telah diselesaikan.'
  }

  if (!completedHabits.value) {
    return 'Mulai dari satu kebiasaan kecil hari ini.'
  }

  return `${remainingHabits.value} kebiasaan lagi untuk melengkapi jurnal hari ini.`
})

const checkinActionLabel = computed(() => {
  if (!totalHabits.value || !completedHabits.value) {
    return 'Mulai Check-in Hari Ini'
  }

  if (isJournalComplete.value) {
    return 'Lihat Check-in Hari Ini'
  }

  return 'Lanjutkan Check-in'
})

const recap = computed(() => {
  return checkinStore.recap ?? null
})

const recapTotalDays = computed(() => {
  return Number(recap.value?.total_days ?? 0)
})

const recapCompletedDays = computed(() => {
  return Number(recap.value?.completed_days ?? 0)
})

const recapCompletionPercentage = computed(() => {
  const value = Number(
    recap.value?.completion_percentage ?? 0,
  )

  return Math.min(100, Math.max(0, value))
})

const recapDailyCheckins = computed(() => {
  const checkins =
    recap.value?.daily_checkins ??
    recap.value?.raw?.daily_checkins ??
    []

  return Array.isArray(checkins) ? checkins : []
})

function dateKey(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function previousDate(date) {
  const result = new Date(date)
  result.setDate(result.getDate() - 1)

  return result
}

function isCompleteCheckin(checkin) {
  if (checkin?.is_complete !== undefined) {
    return Boolean(checkin.is_complete)
  }

  const total = Number(
    checkin?.total_items ??
      checkin?.total_count ??
      0,
  )

  const completed = Number(
    checkin?.done_items ??
      checkin?.completed_count ??
      0,
  )

  return total > 0 && completed >= total
}

const calculatedStreak = computed(() => {
  const completeDates = new Set(
    recapDailyCheckins.value
      .filter(isCompleteCheckin)
      .map((checkin) => {
        return String(
          checkin?.checkin_date ?? '',
        ).slice(0, 10)
      })
      .filter(Boolean),
  )

  if (!completeDates.size) {
    return 0
  }

  let cursor = new Date()

  if (!completeDates.has(dateKey(cursor))) {
    cursor = previousDate(cursor)
  }

  let streak = 0

  while (completeDates.has(dateKey(cursor))) {
    streak += 1
    cursor = previousDate(cursor)
  }

  return streak
})

const currentStreak = computed(() => {
  const backendValue =
    recap.value?.current_streak ??
    recap.value?.streak ??
    recap.value?.raw?.summary?.current_streak ??
    recap.value?.raw?.current_streak

  if (
    backendValue !== undefined &&
    backendValue !== null
  ) {
    return Math.max(
      0,
      Number(backendValue) || 0,
    )
  }

  return calculatedStreak.value
})

const achievementContent = computed(() => {
  if (currentStreak.value > 0) {
    return {
      eyebrow: 'Streak Aktif',
      title: `${currentStreak.value} Hari Konsisten`,
      description:
        `Kamu berhasil menjaga kebiasaan lengkap selama ${currentStreak.value} hari berturut-turut.`,
      metric: `${currentStreak.value} hari`,
    }
  }

  if (recapCompletedDays.value > 0) {
    return {
      eyebrow: 'Pencapaian Bulan Ini',
      title: `${recapCompletedDays.value} Hari Lengkap`,
      description:
        `Kamu telah menyelesaikan ${recapCompletedDays.value} dari ${recapTotalDays.value} hari check-in secara lengkap.`,
      metric: `${recapCompletedDays.value}/${recapTotalDays.value} hari`,
    }
  }

  if (recapCompletionPercentage.value > 0) {
    return {
      eyebrow: 'Progres Bulan Ini',
      title: `${Math.round(
        recapCompletionPercentage.value,
      )}% Tercapai`,
      description:
        'Progres kebiasaanmu mulai terbentuk. Terus lanjutkan check-in setiap hari.',
      metric: `${Math.round(
        recapCompletionPercentage.value,
      )}%`,
    }
  }

  return {
    eyebrow: 'Streak Harian',
    title: 'Mulai Streak Pertamamu',
    description:
      'Lengkapi seluruh kebiasaan hari ini untuk memulai rangkaian hari konsistenmu.',
    metric: '0 hari',
  }
})

function goToCheckin() {
  router.push('/student/checkin')
}

function goToRecap() {
  router.push('/student/recap')
}

onMounted(async () => {
  await Promise.allSettled([
    checkinStore.fetchToday(),
    checkinStore.fetchRecap(),
  ])
})
</script>

<template>
  <section
    class="space-y-4 pb-8 sm:space-y-5 lg:space-y-6 lg:pb-10"
  >
    <!-- MOBILE DASHBOARD -->
    <div class="space-y-4 md:hidden">
      <!-- Sapaan ringkas -->
      <header class="px-1">
        <p class="text-sm text-slate-500">
          Selamat datang,
          <span class="font-bold text-slate-950">
            {{ displayName }}
          </span>
        </p>

        <p class="mt-1 text-xs text-slate-400">
          Lanjutkan kebiasaan positifmu hari ini.
        </p>
      </header>

      <!-- Prioritas utama: progres + CTA -->
      <section
        class="relative overflow-hidden rounded-[26px] hero-gradient p-5 text-white shadow-[0_16px_36px_rgba(14,165,233,0.24)]"
      >
        <div
          class="pointer-events-none absolute -right-14 -top-16 h-40 w-40 rounded-full bg-white/10"
        />

        <div
          class="pointer-events-none absolute -bottom-20 -left-16 h-44 w-44 rounded-full bg-white/10"
        />

        <div class="relative">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <p
                class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-white/75"
              >
                Progres Hari Ini
              </p>

              <h1 class="mt-2 text-2xl font-extrabold">
                {{ completedHabits }}/{{ totalHabits }}
                selesai
              </h1>

              <p
                class="mt-2 max-w-[230px] text-xs leading-5 text-white/80"
              >
                {{ completionDescription }}
              </p>
            </div>

            <div
              class="relative flex h-16 w-16 shrink-0 items-center justify-center"
            >
              <svg
                viewBox="0 0 64 64"
                class="absolute inset-0 h-full w-full -rotate-90"
                aria-hidden="true"
              >
                <circle
                  cx="32"
                  cy="32"
                  r="27"
                  fill="none"
                  stroke="rgba(255,255,255,0.25)"
                  stroke-width="5"
                />

                <circle
                  cx="32"
                  cy="32"
                  r="27"
                  fill="none"
                  stroke="#ffffff"
                  stroke-width="5"
                  stroke-linecap="round"
                  :stroke-dasharray="169.65"
                  :stroke-dashoffset="
                    169.65 -
                    (safeProgressPercent / 100) *
                      169.65
                  "
                  class="transition-all duration-700"
                />
              </svg>

              <span class="text-sm font-extrabold">
                {{ Math.round(safeProgressPercent) }}%
              </span>
            </div>
          </div>

          <div
            class="mt-5 h-2.5 overflow-hidden rounded-full bg-white/20"
          >
            <div
              class="h-full rounded-full bg-white transition-all duration-700"
              :style="{
                width: `${safeProgressPercent}%`,
              }"
            />
          </div>

          <button
            type="button"
            class="mt-5 flex w-full items-center justify-center gap-2 rounded-2xl bg-white px-5 py-3.5 text-sm font-extrabold text-sky-700 shadow-[0_10px_24px_rgba(3,105,161,0.16)] transition active:scale-[0.98]"
            @click="goToCheckin"
          >
            <span>{{ checkinActionLabel }}</span>

            <svg
              viewBox="0 0 24 24"
              fill="none"
              class="h-4 w-4"
              aria-hidden="true"
            >
              <path
                d="M5 12h14M14 7l5 5-5 5"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </button>
        </div>
      </section>

      <!-- Pencapaian bulanan -->
      <button
        type="button"
        class="group w-full rounded-[24px] border border-sky-100 bg-gradient-to-br from-sky-50 to-cyan-50 p-5 text-left shadow-[0_10px_28px_rgba(15,23,42,0.05)] transition active:scale-[0.99]"
        @click="goToRecap"
      >
        <div class="flex items-start gap-4">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-xl shadow-sm"
          >
            🏆
          </div>

          <div class="min-w-0 flex-1">
            <div
              class="flex items-start justify-between gap-3"
            >
              <div class="min-w-0">
                <p
                  class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-sky-600"
                >
                  {{ achievementContent.eyebrow }}
                </p>

                <h2
                  class="mt-1 text-lg font-extrabold leading-tight text-slate-950"
                >
                  {{ achievementContent.title }}
                </h2>
              </div>

              <span
                class="shrink-0 rounded-full bg-sky-100 px-2.5 py-1 text-[10px] font-extrabold text-sky-700"
              >
                {{ achievementContent.metric }}
              </span>
            </div>

            <p
              class="mt-2 text-xs leading-5 text-slate-500"
            >
              {{ achievementContent.description }}
            </p>

            <span
              class="mt-3 inline-flex items-center gap-1.5 text-xs font-extrabold text-sky-700"
            >
              Lihat perkembangan

              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-3.5 w-3.5 transition group-hover:translate-x-0.5"
                aria-hidden="true"
              >
                <path
                  d="M5 12h14M14 7l5 5-5 5"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </span>
          </div>
        </div>
      </button>

      <!-- Status jurnal ringkas -->
      <button
        type="button"
        class="w-full rounded-[22px] border border-slate-200 bg-white p-4 text-left shadow-[0_10px_26px_rgba(15,23,42,0.05)] transition active:scale-[0.99]"
        @click="goToCheckin"
      >
        <div class="flex items-center gap-4">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
            :class="
              isJournalComplete
                ? 'bg-emerald-50 text-emerald-600'
                : 'bg-amber-50 text-amber-600'
            "
          >
            <svg
              v-if="isJournalComplete"
              viewBox="0 0 24 24"
              fill="none"
              class="h-5 w-5"
              aria-hidden="true"
            >
              <circle
                cx="12"
                cy="12"
                r="9"
                stroke="currentColor"
                stroke-width="1.8"
              />

              <path
                d="m7.5 12 3 3 6-7"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
              class="h-5 w-5"
              aria-hidden="true"
            >
              <circle
                cx="12"
                cy="12"
                r="9"
                stroke="currentColor"
                stroke-width="1.8"
              />

              <path
                d="M12 7v5l3 2"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <div class="min-w-0 flex-1">
            <p
              class="text-[10px] font-bold uppercase tracking-[0.15em] text-slate-400"
            >
              Status Jurnal
            </p>

            <p
              class="mt-1 text-base font-extrabold"
              :class="
                isJournalComplete
                  ? 'text-emerald-700'
                  : 'text-slate-900'
              "
            >
              {{ journalStatus }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
              {{
                isJournalComplete
                  ? 'Jurnal hari ini sudah lengkap.'
                  : `${remainingHabits} kebiasaan masih tersisa.`
              }}
            </p>
          </div>

          <svg
            viewBox="0 0 24 24"
            fill="none"
            class="h-5 w-5 shrink-0 text-slate-300"
            aria-hidden="true"
          >
            <path
              d="m9 5 7 7-7 7"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
      </button>
    </div>

    <!-- DESKTOP / TABLET DASHBOARD -->
    <div class="hidden space-y-6 md:block">
      <!-- Welcome banner -->
      <section
        class="rounded-[28px] bg-white px-8 py-7 shadow-[0_18px_45px_rgba(15,23,42,0.06)]"
      >
        <div class="flex items-center justify-between gap-6">
          <div>
            <h1
              class="text-2xl font-bold text-slate-950"
            >
              Selamat Datang, {{ displayName }}!
            </h1>

            <p class="mt-2 text-sm text-slate-500">
              Mari bangun kebiasaan positif hari ini.
            </p>
          </div>

          <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-sky-50 text-2xl text-sky-600"
          >
            🌀
          </div>
        </div>
      </section>

      <!-- Desktop hero and achievement -->
      <div
        class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(330px,0.7fr)] xl:items-stretch"
      >
        <section
          class="overflow-hidden rounded-[28px] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.06)] xl:min-h-[430px]"
        >
          <div
            class="grid h-full grid-cols-[minmax(0,1.3fr)_minmax(300px,0.7fr)]"
          >
            <div
              class="flex min-w-0 flex-col justify-center p-8 xl:p-9"
            >
              <span
                class="mb-5 inline-flex w-fit rounded-full bg-sky-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-sky-700"
              >
                Dashboard Utama
              </span>

              <h2
                class="max-w-lg text-3xl font-extrabold leading-tight text-slate-950 xl:text-[32px]"
              >
                Jurnal 7 Kebiasaan Anak Indonesia Hebat
              </h2>

              <p
                class="mt-4 max-w-lg text-sm leading-6 text-slate-500"
              >
                Catat setiap langkah kecilmu dalam membangun
                disiplin dan karakter positif setiap hari.
              </p>

              <button
                type="button"
                class="mt-7 w-fit rounded-xl bg-sky-500 px-7 py-3.5 text-sm font-bold text-white shadow-[0_12px_30px_rgba(14,165,233,0.25)] transition hover:-translate-y-0.5 hover:bg-sky-600"
                @click="goToCheckin"
              >
                Isi Check-in Hari Ini
              </button>
            </div>

            <div
              class="relative min-h-[360px] overflow-hidden bg-gradient-to-br from-cyan-50 to-blue-100 xl:min-h-[430px]"
            >
              <img
                src="/images/dashboard-student-illustration.png"
                alt="Ilustrasi siswa sedang belajar"
                class="absolute inset-0 h-full w-full object-cover object-center"
              />

              <div
                class="pointer-events-none absolute bottom-6 left-8 right-8 h-5 rounded-full bg-white/70"
              />
            </div>
          </div>
        </section>

        <button
          type="button"
          class="group relative flex min-h-[360px] flex-col overflow-hidden rounded-[28px] bg-gradient-to-br from-sky-500 to-cyan-500 p-7 text-left text-white shadow-[0_18px_45px_rgba(14,165,233,0.22)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_22px_50px_rgba(14,165,233,0.3)] xl:min-h-[430px]"
          @click="goToRecap"
        >
          <div
            class="pointer-events-none absolute -right-16 -top-16 h-44 w-44 rounded-full bg-white/10"
          />

          <div
            class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-white/10"
          />

          <div class="relative flex h-full flex-col">
            <div
              class="flex items-start justify-between gap-4"
            >
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-2xl backdrop-blur-sm"
              >
                🏆
              </div>

              <span
                class="max-w-[150px] truncate rounded-full bg-white/15 px-3 py-1.5 text-[10px] font-extrabold uppercase tracking-wide text-white/90 backdrop-blur-sm"
              >
                {{ achievementContent.metric }}
              </span>
            </div>

            <div class="mt-6">
              <p
                class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-white/70"
              >
                {{ achievementContent.eyebrow }}
              </p>

              <h2
                class="mt-2 text-[26px] font-extrabold leading-tight"
              >
                {{ achievementContent.title }}
              </h2>

              <p
                class="mt-4 max-w-sm text-sm font-medium leading-6 text-white/90"
              >
                {{ achievementContent.description }}
              </p>
            </div>

            <div
              class="mt-auto inline-flex w-fit items-center gap-2 rounded-xl bg-white/15 px-4 py-3 text-xs font-extrabold uppercase tracking-wide text-white transition group-hover:bg-white/25"
            >
              <span>Lihat Rekap</span>

              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-4 w-4 transition group-hover:translate-x-1"
                aria-hidden="true"
              >
                <path
                  d="M5 12h14M14 7l5 5-5 5"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>
        </button>
      </div>

      <!-- Desktop metrics -->
      <div class="grid grid-cols-3 gap-5">
        <article
          class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_rgba(15,23,42,0.05)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_18px_40px_rgba(14,165,233,0.1)]"
        >
          <div
            class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-sky-50"
          />

          <div
            class="relative flex items-start justify-between gap-5"
          >
            <div class="min-w-0">
              <p
                class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
              >
                Selesai Hari Ini
              </p>

              <div class="mt-5 flex items-end gap-2">
                <p
                  class="text-3xl font-extrabold leading-none text-slate-950"
                >
                  {{ completedHabits }}
                </p>

                <p
                  class="pb-0.5 text-base font-bold text-slate-400"
                >
                  / {{ totalHabits }}
                </p>
              </div>

              <p
                class="mt-3 text-xs font-medium text-slate-500"
              >
                {{ completionDescription }}
              </p>
            </div>

            <div
              class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full"
            >
              <svg
                viewBox="0 0 64 64"
                class="absolute inset-0 h-full w-full -rotate-90"
                aria-hidden="true"
              >
                <circle
                  cx="32"
                  cy="32"
                  r="27"
                  fill="none"
                  stroke="#e0f2fe"
                  stroke-width="5"
                />

                <circle
                  cx="32"
                  cy="32"
                  r="27"
                  fill="none"
                  stroke="#0ea5e9"
                  stroke-width="5"
                  stroke-linecap="round"
                  :stroke-dasharray="169.65"
                  :stroke-dashoffset="
                    169.65 -
                    (safeProgressPercent / 100) *
                      169.65
                  "
                  class="transition-all duration-700"
                />
              </svg>

              <span
                class="text-xs font-extrabold text-sky-600"
              >
                {{ Math.round(safeProgressPercent) }}%
              </span>
            </div>
          </div>
        </article>

        <article
          class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-[0_12px_32px_rgba(15,23,42,0.05)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_18px_40px_rgba(14,165,233,0.1)]"
        >
          <div
            class="flex items-center justify-between gap-4"
          >
            <p
              class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
            >
              Progres Hari Ini
            </p>

            <span
              class="rounded-full bg-sky-50 px-3 py-1 text-xs font-extrabold text-sky-600"
            >
              {{ Math.round(safeProgressPercent) }}%
            </span>
          </div>

          <div class="mt-6">
            <div
              class="h-3 overflow-hidden rounded-full bg-slate-100"
            >
              <div
                class="h-full rounded-full bg-gradient-to-r from-sky-500 to-cyan-400 transition-all duration-700"
                :style="{
                  width: `${safeProgressPercent}%`,
                }"
              />
            </div>

            <div
              class="mt-4 flex items-center justify-between gap-4"
            >
              <p
                class="text-xs font-medium text-slate-500"
              >
                {{ completedHabits }} dari
                {{ totalHabits }} kebiasaan
              </p>

              <p
                class="text-xs font-bold"
                :class="
                  isJournalComplete
                    ? 'text-emerald-600'
                    : 'text-sky-600'
                "
              >
                {{
                  isJournalComplete
                    ? 'Selesai'
                    : 'Terus lanjutkan'
                }}
              </p>
            </div>
          </div>
        </article>

        <button
          type="button"
          class="group rounded-2xl border border-slate-200 bg-white p-6 text-left shadow-[0_12px_32px_rgba(15,23,42,0.05)] transition duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-[0_18px_40px_rgba(14,165,233,0.1)]"
          @click="goToCheckin"
        >
          <div
            class="flex items-start justify-between gap-5"
          >
            <div>
              <p
                class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
              >
                Status Jurnal
              </p>

              <p
                class="mt-5 text-xl font-extrabold"
                :class="
                  isJournalComplete
                    ? 'text-emerald-700'
                    : 'text-slate-950'
                "
              >
                {{ journalStatus }}
              </p>

              <p
                class="mt-2 text-xs font-medium leading-5 text-slate-500"
              >
                {{
                  isJournalComplete
                    ? 'Jurnal hari ini sudah diselesaikan.'
                    : 'Lengkapi kebiasaan yang masih tersisa.'
                }}
              </p>
            </div>

            <div
              class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition group-hover:bg-sky-50 group-hover:text-sky-600"
            >
              <svg
                viewBox="0 0 24 24"
                fill="none"
                class="h-4 w-4 transition group-hover:translate-x-0.5"
                aria-hidden="true"
              >
                <path
                  d="M5 12h14M14 7l5 5-5 5"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </div>
        </button>
      </div>
    </div>
  </section>
</template>
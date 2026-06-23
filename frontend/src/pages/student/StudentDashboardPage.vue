<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'
import { useCheckinStore } from '../../stores/checkinStore'

const router = useRouter()
const authStore = useAuthStore()
const checkinStore = useCheckinStore()

const displayName = computed(() => {
  return authStore.user?.display_name ?? authStore.user?.full_name ?? authStore.user?.name ?? 'Siswa'
})

const totalHabits = computed(() => checkinStore.totalHabits)
const completedHabits = computed(() => checkinStore.completedCount)
const progressPercent = computed(() => checkinStore.progressPercent)

const journalStatus = computed(() => {
  if (!totalHabits.value) return 'Belum tersedia'
  return completedHabits.value >= totalHabits.value ? 'Lengkap' : 'Belum lengkap'
})

function goToCheckin() {
  router.push('/student/checkin')
}

function goToRecap() {
  router.push('/student/recap')
}

onMounted(async () => {
  await checkinStore.fetchToday()
})
</script>

<template>
  <section class="space-y-6 px-8 pb-10">
    <div class="rounded-[28px] bg-white px-8 py-7 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
      <div class="flex items-center justify-between gap-6">
        <div>
          <h2 class="text-2xl font-bold text-slate-950">
            Selamat Datang, {{ displayName }}!
          </h2>

          <p class="mt-2 text-sm text-slate-500">
            Mari bangun kebiasaan positif hari ini.
          </p>
        </div>

        <div class="hidden h-16 w-16 items-center justify-center rounded-full bg-sky-50 text-2xl text-sky-600 sm:flex">
          🌀
        </div>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.8fr_0.8fr]">
      <div class="overflow-hidden rounded-[28px] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
        <div class="grid min-h-[260px] md:grid-cols-[1.2fr_0.9fr]">
          <div class="flex flex-col justify-center p-8">
            <span class="mb-5 inline-flex w-fit rounded-full bg-sky-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-sky-700">
              Dashboard Utama
            </span>

            <h2 class="max-w-sm text-3xl font-extrabold leading-tight text-slate-950">
              Jurnal 7 Kebiasaan Anak Indonesia Hebat
            </h2>

            <p class="mt-4 max-w-md text-sm leading-6 text-slate-500">
              Catat setiap langkah kecilmu dalam membangun disiplin diri dan karakter unggul sebagai generasi masa depan.
            </p>

            <button
              type="button"
              class="mt-8 w-fit rounded-xl bg-sky-500 px-7 py-4 text-sm font-bold text-white shadow-[0_12px_30px_rgba(14,165,233,0.25)] transition hover:bg-sky-600"
              @click="goToCheckin"
            >
              Isi Check-in Hari Ini
            </button>
          </div>

          <div class="relative hidden overflow-hidden bg-sky-50 md:block">
            <div class="absolute inset-0 bg-gradient-to-br from-sky-100 via-cyan-50 to-blue-100" />

            <div class="absolute right-8 top-8 grid grid-cols-4 gap-3 opacity-60">
              <span
                v-for="item in 16"
                :key="item"
                class="h-2 w-2 rounded-full bg-sky-300"
              />
            </div>

            <div class="absolute bottom-0 left-1/2 h-52 w-52 -translate-x-1/2 rounded-t-full bg-sky-200" />

            <div class="absolute bottom-10 left-1/2 flex h-36 w-36 -translate-x-1/2 items-center justify-center rounded-[38px] bg-white text-7xl shadow-xl">
              📚
            </div>

            <div class="absolute bottom-7 left-8 right-8 h-6 rounded-full bg-white/70" />
          </div>
        </div>
      </div>

      <button
        type="button"
        class="group flex min-h-[260px] flex-col justify-between rounded-[28px] bg-sky-500 p-8 text-left text-white shadow-[0_18px_45px_rgba(14,165,233,0.22)] transition hover:-translate-y-1 hover:bg-sky-600"
        @click="goToRecap"
      >
        <div>
          <div class="mb-8 flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-2xl">
            🏆
          </div>

          <h3 class="text-xl font-extrabold">
            Pencapaian Baru!
          </h3>

          <p class="mt-4 text-sm font-medium leading-6 text-white/90">
            Kamu telah menjaga streak selama 15 hari tanpa terputus. Luar biasa!
          </p>
        </div>

        <div class="mt-8 text-xs font-extrabold uppercase tracking-wide text-white">
          Lihat Prestasi
        </div>
      </button>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
      <div class="rounded-2xl border-l-4 border-sky-500 bg-white p-6 shadow-[0_14px_35px_rgba(15,23,42,0.06)]">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
              Selesai Hari Ini
            </p>

            <p class="mt-3 text-3xl font-extrabold text-sky-600">
              {{ completedHabits }}/{{ totalHabits }}
            </p>
          </div>

          <div class="flex h-14 w-14 items-center justify-center rounded-full border-4 border-sky-500 text-sky-600">
            ✓
          </div>
        </div>
      </div>

      <div class="rounded-2xl border-l-4 border-sky-500 bg-white p-6 shadow-[0_14px_35px_rgba(15,23,42,0.06)]">
        <div class="flex items-center justify-between">
          <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
            Progress Hari Ini
          </p>

          <p class="text-sm font-bold text-sky-600">
            {{ progressPercent }}%
          </p>
        </div>

        <div class="mt-5 h-2.5 overflow-hidden rounded-full bg-sky-100">
          <div
            class="h-full rounded-full bg-sky-500 transition-all"
            :style="{ width: `${progressPercent}%` }"
          />
        </div>
      </div>

      <div class="rounded-2xl border-l-4 border-slate-800 bg-white p-6 shadow-[0_14px_35px_rgba(15,23,42,0.06)]">
        <div class="flex items-center justify-between gap-4">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
              Status Jurnal
            </p>

            <p class="mt-3 text-lg font-bold text-slate-900">
              {{ journalStatus }}
            </p>
          </div>

          <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-50 text-xl text-sky-600">
            ⚙️
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
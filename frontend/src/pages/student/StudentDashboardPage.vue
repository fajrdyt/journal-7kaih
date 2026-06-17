<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { useCheckinStore } from '@/stores/checkinStore'

const checkinStore = useCheckinStore()

const statusText = computed(() => {
  if (!checkinStore.totalHabits) return 'Belum ada data kebiasaan.'

  if (checkinStore.completedHabits === checkinStore.totalHabits) {
    return 'Semua kebiasaan hari ini sudah selesai.'
  }

  return `${checkinStore.completedHabits} dari ${checkinStore.totalHabits} kebiasaan selesai.`
})

onMounted(() => {
  checkinStore.fetchToday()
})
</script>

<template>
  <div class="space-y-6">
    <section class="rounded-xl border bg-white p-6 shadow-sm">
      <p class="text-sm text-slate-500">
        Dashboard siswa
      </p>

      <h1 class="mt-1 text-2xl font-bold text-slate-900">
        Jurnal 7 Kebiasaan Anak Indonesia Hebat
      </h1>

      <p class="mt-2 text-sm text-slate-500">
        {{ statusText }}
      </p>

      <RouterLink
        to="/student/checkin"
        class="mt-5 inline-flex rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700"
      >
        Isi Check-in Hari Ini
      </RouterLink>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">
          Selesai Hari Ini
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
          {{ checkinStore.completedHabits }}/{{ checkinStore.totalHabits }}
        </h2>
      </div>

      <div class="rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">
          Progress
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
          {{ checkinStore.progressPercentage }}%
        </h2>
      </div>

      <div class="rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">
          Status
        </p>

        <h2 class="mt-2 text-lg font-semibold text-slate-900">
          {{ checkinStore.completedHabits === checkinStore.totalHabits && checkinStore.totalHabits ? 'Lengkap' : 'Berjalan' }}
        </h2>
      </div>
    </section>
  </div>
</template>

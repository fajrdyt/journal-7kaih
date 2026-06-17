<script setup>
import { computed, onMounted } from 'vue'
import { useCheckinStore } from '@/stores/checkinStore'

const checkinStore = useCheckinStore()

const percentage = computed(() => Math.round(Number(checkinStore.recap?.completion_percentage ?? 0)))

onMounted(() => {
  checkinStore.fetchRecap()
})
</script>

<template>
  <div class="space-y-6">
    <section>
      <h1 class="text-2xl font-bold text-slate-900">
        Rekap Pribadi
      </h1>

      <p class="text-sm text-slate-500">
        Ringkasan perkembangan kebiasaan kamu.
      </p>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">
          Total Check-in
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
          {{ checkinStore.recap?.total_days ?? 0 }}
        </h2>
      </div>

      <div class="rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">
          Hari Lengkap
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
          {{ checkinStore.recap?.completed_days ?? 0 }}
        </h2>
      </div>

      <div class="rounded-xl border bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">
          Persentase
        </p>

        <h2 class="mt-2 text-3xl font-bold text-slate-900">
          {{ percentage }}%
        </h2>
      </div>
    </section>

    <section class="rounded-xl border bg-white p-6 shadow-sm">
      <h2 class="mb-4 font-semibold text-slate-900">
        Rekap per Kebiasaan
      </h2>

      <p
        v-if="checkinStore.loading && !checkinStore.recap"
        class="text-sm text-slate-500"
      >
        Mengambil rekap pribadi...
      </p>

      <p
        v-else-if="checkinStore.error"
        class="rounded-lg bg-red-50 p-4 text-sm text-red-600"
      >
        {{ checkinStore.error }}
      </p>

      <p
        v-else-if="!checkinStore.recap?.habits?.length"
        class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500"
      >
        Data rekap kebiasaan belum tersedia.
      </p>

      <div
        v-else
        class="space-y-3"
      >
        <div
          v-for="habit in checkinStore.recap.habits"
          :key="habit.id ?? habit.habit_id ?? habit.name"
          class="rounded-lg border border-slate-200 p-4"
        >
          <div class="mb-2 flex items-center justify-between">
            <h3 class="font-medium text-slate-900">
              {{ habit.name ?? habit.habit_name ?? 'Kebiasaan' }}
            </h3>

            <span class="text-sm text-slate-500">
              {{ habit.completed_count ?? habit.total_done ?? 0 }} selesai
            </span>
          </div>

          <div class="h-2 rounded-full bg-slate-100">
            <div
              class="h-2 rounded-full bg-sky-600"
              :style="{ width: `${habit.percentage ?? habit.completion_percentage ?? 0}%` }"
            />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

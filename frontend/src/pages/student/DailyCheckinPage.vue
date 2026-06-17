<script setup>
import { computed, onMounted, ref } from 'vue'
import HabitCard from '@/components/cards/HabitCard.vue'
import ProgressBadge from '@/components/common/ProgressBadge.vue'
import { useCheckinStore } from '@/stores/checkinStore'

const checkinStore = useCheckinStore()
const successMessage = ref('')
const saveError = ref('')

const todayLabel = computed(() => {
  const date = checkinStore.todayCheckin.checkin_date

  if (!date) return 'Hari ini'

  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(new Date(date))
})

onMounted(() => {
  checkinStore.fetchTodayCheckin()
})

function updateHabit(habitId, updates) {
  checkinStore.updateHabit(habitId, updates)
}

async function handleSubmit() {
  successMessage.value = ''
  saveError.value = ''

  try {
    await checkinStore.saveCheckin()

    successMessage.value = 'Check-in berhasil disimpan.'
  } catch (error) {
    saveError.value = error.response?.data?.message || 'Check-in gagal disimpan.'
  }
}
</script>

<template>
  <div class="space-y-6">
    <section class="rounded-xl border bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
          <p class="text-sm text-slate-500">
            Check-in harian
          </p>

          <h1 class="text-2xl font-bold text-slate-900">
            {{ todayLabel }}
          </h1>
        </div>

        <ProgressBadge
          :completed="checkinStore.completedHabits"
          :total="checkinStore.totalHabits"
          :percentage="checkinStore.progressPercentage"
        />
      </div>
    </section>

    <section class="rounded-xl border bg-white p-6 shadow-sm">
      <div class="mb-6 flex items-center justify-between">
        <div>
          <h2 class="font-semibold text-slate-900">
            Kebiasaan Hari Ini
          </h2>

          <p class="text-sm text-slate-500">
            Centang kebiasaan yang sudah dilakukan, lalu simpan.
          </p>
        </div>
      </div>

      <p
        v-if="checkinStore.loading && !checkinStore.todayCheckin.habits.length"
        class="rounded-lg bg-slate-50 p-4 text-sm text-slate-500"
      >
        Mengambil data check-in...
      </p>

      <p
        v-else-if="checkinStore.error"
        class="rounded-lg bg-red-50 p-4 text-sm text-red-600"
      >
        {{ checkinStore.error }}
      </p>

      <p
        v-else-if="!checkinStore.todayCheckin.habits.length"
        class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-700"
      >
        Data kebiasaan belum tersedia dari API.
      </p>

      <div
        v-else
        class="space-y-4"
      >
        <HabitCard
          v-for="habit in checkinStore.todayCheckin.habits"
          :key="habit.id"
          :habit="habit"
          @update="updateHabit"
        />

        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700">
            Catatan umum
          </label>

          <textarea
            v-model="checkinStore.todayCheckin.notes"
            class="w-full rounded-lg border border-slate-200 p-3 text-sm outline-none focus:border-sky-500"
            rows="3"
            placeholder="Tambahkan catatan untuk check-in hari ini..."
          />
        </div>

        <p
          v-if="successMessage"
          class="rounded-lg bg-green-50 p-3 text-sm text-green-700"
        >
          {{ successMessage }}
        </p>

        <p
          v-if="saveError"
          class="rounded-lg bg-red-50 p-3 text-sm text-red-600"
        >
          {{ saveError }}
        </p>

        <div class="flex justify-end">
          <button
            type="button"
            :disabled="checkinStore.loading"
            class="rounded-lg bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
            @click="handleSubmit"
          >
            {{ checkinStore.loading ? 'Menyimpan...' : 'Simpan Check-in' }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

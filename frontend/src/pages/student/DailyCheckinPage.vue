<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'

import HabitCard from '../../components/cards/HabitCard.vue'
import { useCheckinStore } from '../../stores/checkinStore'

const checkinStore = useCheckinStore()

const saving = ref(false)
const successMessage = ref('')
const actionError = ref('')
const currentDateKey = ref(getLocalDateKey())

let dateRefreshTimer = null

const habits = computed(() => checkinStore.habits)
const completedCount = computed(() => checkinStore.completedCount)
const totalHabits = computed(() => checkinStore.totalHabits)
const progressPercent = computed(() => checkinStore.progressPercent)

const generalNotes = computed({
  get() {
    return checkinStore.todayCheckin?.notes ?? ''
  },

  set(value) {
    if (!checkinStore.todayCheckin) return

    checkinStore.todayCheckin.notes = value
  },
})

const displayDate = computed(() => {
  const dateString = checkinStore.todayCheckin?.checkin_date

  if (!dateString) {
    return formatDate(new Date())
  }

  const normalizedDate = String(dateString).slice(0, 10)
  const [year, month, day] = normalizedDate.split('-').map(Number)

  if (!year || !month || !day) {
    return formatDate(new Date())
  }

  return formatDate(new Date(year, month - 1, day))
})

const isEditableToday = computed(() => {
  const checkinDate = String(
    checkinStore.todayCheckin?.checkin_date ?? '',
  ).slice(0, 10)

  if (!checkinDate) {
    return true
  }

  return checkinDate === currentDateKey.value
})

function getLocalDateKey(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatDate(date) {
  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date)
}

function clearFeedback() {
  successMessage.value = ''
  actionError.value = ''
}

function toggleHabit(habitId) {
  if (!isEditableToday.value) return

  clearFeedback()
  checkinStore.toggleHabit(habitId)
}

function updateHabitNote(habitId, notes) {
  if (!isEditableToday.value) return

  clearFeedback()
  checkinStore.updateHabitNote(habitId, notes)
}

function handleGeneralNotesInput() {
  if (!isEditableToday.value) return

  clearFeedback()
}

async function handleCancel() {
  if (!isEditableToday.value) return

  clearFeedback()
  await checkinStore.fetchToday()
}

async function handleSave() {
  if (!isEditableToday.value || saving.value) return

  saving.value = true
  clearFeedback()

  try {
    await checkinStore.saveCheckin()

    successMessage.value = 'Check-in hari ini berhasil disimpan.'
  } catch (error) {
    actionError.value =
      error.response?.data?.message ??
      checkinStore.error ??
      'Gagal menyimpan check-in hari ini.'
  } finally {
    saving.value = false
  }
}

async function refreshDateIfChanged() {
  const latestDateKey = getLocalDateKey()

  if (latestDateKey === currentDateKey.value) return

  currentDateKey.value = latestDateKey
  clearFeedback()

  await checkinStore.fetchToday()
}

onMounted(async () => {
  currentDateKey.value = getLocalDateKey()

  await checkinStore.fetchToday()

  dateRefreshTimer = window.setInterval(() => {
    refreshDateIfChanged()
  }, 60_000)
})

onUnmounted(() => {
  if (dateRefreshTimer) {
    window.clearInterval(dateRefreshTimer)
  }
})
</script>

<template>
  <section class="px-8 pb-10">
    <!-- Informasi halaman dan progress -->
    <div
      class="mb-7 flex flex-col justify-between gap-5 sm:flex-row sm:items-center"
    >
      <div>
        <p class="text-xs font-semibold text-sky-600">
          Check-in Harian
        </p>

        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">
          {{ displayDate }}
        </h1>
      </div>

      <div
        class="w-full rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.05)] sm:w-72"
      >
        <div class="flex items-center justify-between gap-5">
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-3">
              <p class="text-xs font-bold text-slate-700">
                Progress Hari Ini
              </p>

              <p class="text-xs font-bold text-sky-600">
                {{ completedCount }}/{{ totalHabits }}
              </p>
            </div>

            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
              <div
                class="h-full rounded-full bg-sky-500 transition-all duration-300"
                :style="{ width: `${progressPercent}%` }"
              />
            </div>

            <p class="mt-2 text-xs font-medium text-slate-500">
              {{ progressPercent }}% selesai
            </p>
          </div>

          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-xl text-sky-600"
          >
            ⚡
          </div>
        </div>
      </div>
    </div>

    <!-- Informasi check-in lama -->
    <div
      v-if="!isEditableToday && checkinStore.todayCheckin"
      class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4"
    >
      <p class="text-sm font-semibold text-amber-800">
        Check-in ini berasal dari hari sebelumnya dan hanya dapat dilihat.
      </p>

      <p class="mt-1 text-xs leading-5 text-amber-700">
        Kebiasaan dan catatan tidak dapat diubah setelah hari berganti.
      </p>
    </div>

    <!-- Loading -->
    <div
      v-if="checkinStore.loading && !habits.length"
      class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm"
    >
      <p class="text-sm font-medium text-slate-500">
        Memuat data kebiasaan...
      </p>
    </div>

    <!-- Error fetch -->
    <div
      v-else-if="checkinStore.error && !habits.length"
      class="rounded-2xl border border-red-200 bg-red-50 px-6 py-5"
    >
      <p class="text-sm font-semibold text-red-700">
        {{ checkinStore.error }}
      </p>

      <button
        type="button"
        class="mt-4 rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-700"
        @click="checkinStore.fetchToday()"
      >
        Coba Lagi
      </button>
    </div>

    <template v-else>
      <!-- Grid kebiasaan -->
      <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
        <HabitCard
          v-for="habit in habits"
          :key="habit.id"
          :habit="habit"
          :editable="isEditableToday"
          @toggle="toggleHabit(habit.id)"
          @update-note="updateHabitNote(habit.id, $event)"
        />

        <!-- Catatan umum -->
        <article
          class="flex min-h-[200px] flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)] xl:col-span-2"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-lg font-bold text-sky-600"
            >
              ≡
            </div>

            <h3 class="text-sm font-bold text-slate-900">
              Catatan Umum
            </h3>
          </div>

          <textarea
            v-model="generalNotes"
            :readonly="!isEditableToday"
            rows="5"
            class="mt-4 min-h-[125px] w-full flex-1 resize-none rounded-xl border border-transparent bg-indigo-50/70 px-4 py-3 text-xs leading-5 text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-sky-300 focus:bg-white focus:ring-4 focus:ring-sky-100 read-only:cursor-default read-only:bg-slate-100 read-only:text-slate-500 read-only:focus:border-transparent read-only:focus:ring-0"
            placeholder="Apa yang kamu rasakan hari ini? Adakah hal menarik atau tantangan yang dihadapi?"
            @input="handleGeneralNotesInput"
          />
        </article>
      </div>

      <!-- Feedback -->
      <div
        v-if="successMessage"
        class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="actionError"
        class="mt-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"
      >
        {{ actionError }}
      </div>

      <!-- Tombol aksi -->
      <div
        class="mt-7 flex flex-col-reverse items-stretch justify-end gap-3 sm:flex-row sm:items-center"
      >
        <button
          type="button"
          class="rounded-xl bg-indigo-50 px-7 py-3 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            saving ||
            checkinStore.loading ||
            !isEditableToday
          "
          @click="handleCancel"
        >
          Batal
        </button>

        <button
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-500 px-7 py-3 text-sm font-bold text-white shadow-[0_12px_30px_rgba(14,165,233,0.25)] transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="
            saving ||
            checkinStore.loading ||
            !totalHabits ||
            !isEditableToday
          "
          @click="handleSave"
        >
          <svg
            viewBox="0 0 20 20"
            fill="none"
            class="h-4 w-4"
            aria-hidden="true"
          >
            <path
              d="M4 3.5h10.5L17 6v10.5H4v-13Z"
              stroke="currentColor"
              stroke-width="1.7"
              stroke-linejoin="round"
            />
            <path
              d="M7 3.5v4h6v-4M7 13h6"
              stroke="currentColor"
              stroke-width="1.7"
              stroke-linecap="round"
            />
          </svg>

          {{ saving ? 'Menyimpan...' : 'Simpan Check-in' }}
        </button>
      </div>
    </template>
  </section>
</template>

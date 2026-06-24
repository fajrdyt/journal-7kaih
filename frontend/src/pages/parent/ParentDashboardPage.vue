<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'
import { useParentStore } from '../../stores/parentStore'

const router = useRouter()
const authStore = useAuthStore()
const parentStore = useParentStore()

const pageError = ref('')

const user = computed(() => authStore.user)
const children = computed(() => parentStore.children)
const selectedChild = computed(() => parentStore.selectedChild)

const selectedChildId = computed({
  get() {
    return parentStore.selectedChildId ?? ''
  },

  set(value) {
    parentStore.setSelectedChild(value)
  },
})

const parentName = computed(() => {
  return (
    user.value?.display_name ??
    user.value?.full_name ??
    user.value?.name ??
    'Orang Tua'
  )
})

const selectedChildName = computed(() => {
  return selectedChild.value?.full_name ?? 'Anak'
})

const selectedChildClass = computed(() => {
  return (
    selectedChild.value?.class?.name ??
    selectedChild.value?.class_room?.name ??
    selectedChild.value?.classRoom?.name ??
    selectedChild.value?.class_name ??
    '-'
  )
})

const latestCheckin = computed(() => {
  if (!parentStore.checkins.length) return null

  return [...parentStore.checkins].sort((first, second) => {
    const firstDate = new Date(first.checkin_date ?? 0).getTime()
    const secondDate = new Date(second.checkin_date ?? 0).getTime()

    return secondDate - firstDate
  })[0]
})

const latestCompletedCount = computed(() => {
  return Number(latestCheckin.value?.completed_count ?? 0)
})

const latestTotalCount = computed(() => {
  return Number(latestCheckin.value?.total_count ?? 0)
})

const latestProgress = computed(() => {
  if (!latestTotalCount.value) return 0

  return Math.round(
    (latestCompletedCount.value / latestTotalCount.value) * 100,
  )
})

const latestStatus = computed(() => {
  if (!latestCheckin.value) {
    return {
      label: 'Belum ada check-in',
      textClass: 'text-slate-500',
      badgeClass: 'bg-slate-100 text-slate-600',
    }
  }

  if (
    latestTotalCount.value > 0 &&
    latestCompletedCount.value >= latestTotalCount.value
  ) {
    return {
      label: 'Lengkap',
      textClass: 'text-emerald-700',
      badgeClass: 'bg-emerald-100 text-emerald-700',
    }
  }

  if (latestCompletedCount.value > 0) {
    return {
      label: 'Belum lengkap',
      textClass: 'text-amber-700',
      badgeClass: 'bg-amber-100 text-amber-700',
    }
  }

  return {
    label: 'Belum dilakukan',
    textClass: 'text-slate-500',
    badgeClass: 'bg-slate-100 text-slate-600',
  }
})

const recapPercentage = computed(() => {
  const percentage = Number(
    parentStore.recap?.completion_percentage ?? 0,
  )

  return Math.min(100, Math.max(0, percentage))
})

const recapTotalDays = computed(() => {
  return Number(parentStore.recap?.total_days ?? 0)
})

const recapCompletedDays = computed(() => {
  return Number(parentStore.recap?.completed_days ?? 0)
})

const latestItems = computed(() => {
  return Array.isArray(latestCheckin.value?.items)
    ? latestCheckin.value.items
    : []
})

const isLoading = computed(() => {
  return (
    parentStore.loadingChildren ||
    parentStore.loadingCheckins ||
    parentStore.loadingRecap
  )
})

function formatDate(value) {
  if (!value) return '-'

  const dateString = String(value).slice(0, 10)
  const [year, month, day] = dateString.split('-').map(Number)

  if (!year || !month || !day) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(new Date(year, month - 1, day))
}

function habitIcon(item) {
  const code = String(item?.habit?.code ?? '').toUpperCase()

  const icons = {
    BANGUN_PAGI: '☼',
    BERIBADAH: '✦',
    BEROLAHRAGA: '⚡',
    MAKAN_SEHAT_BERGIZI: '♨',
    GEMAR_BELAJAR: '▣',
    BERMASYARAKAT: '⌘',
    TIDUR_CEPAT: '☾',
  }

  return icons[code] ?? '✓'
}

async function loadSelectedChildData() {
  if (!selectedChildId.value) return

  pageError.value = ''

  try {
    await Promise.all([
      parentStore.fetchChildCheckins(selectedChildId.value, {
        per_page: 7,
      }),
      parentStore.fetchChildRecap(selectedChildId.value),
    ])
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal mengambil data perkembangan anak.'
  }
}

async function handleChildChange() {
  await loadSelectedChildData()
}

function goToValidation() {
  router.push({
    path: '/parent/validation',
    query: {
      student: selectedChildId.value,
    },
  })
}

function goToHistory() {
  router.push({
    path: '/parent/history',
    query: {
      student: selectedChildId.value,
    },
  })
}

function goToRecap() {
  router.push({
    path: '/parent/recap',
    query: {
      student: selectedChildId.value,
    },
  })
}

onMounted(async () => {
  pageError.value = ''

  try {
    if (!authStore.user && authStore.token) {
      await authStore.fetchUser()
    }

    await parentStore.fetchChildren()
    await loadSelectedChildData()
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal memuat dashboard orang tua.'
  }
})
</script>

<template>
  <section class="space-y-5 pb-10 sm:space-y-6">
    <!-- Header -->
    <div
      class="rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_12px_32px_rgba(15,23,42,0.05)] sm:p-7"
    >
      <div
        class="flex flex-col justify-between gap-5 md:flex-row md:items-center"
      >
        <div>
          <p
            class="text-xs font-bold uppercase tracking-[0.18em] text-sky-600"
          >
            Dashboard Orang Tua
          </p>

          <h1
            class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl"
          >
            Selamat Datang, {{ parentName }}!
          </h1>

          <p class="mt-2 text-sm leading-6 text-slate-500">
            Pantau perkembangan kebiasaan anak dan berikan validasi
            secara berkala.
          </p>
        </div>

        <div
          v-if="children.length"
          class="w-full md:w-72"
        >
          <label
            for="selected-child"
            class="text-xs font-bold uppercase tracking-wide text-slate-400"
          >
            Anak yang dipantau
          </label>

          <select
            id="selected-child"
            v-model="selectedChildId"
            class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
            @change="handleChildChange"
          >
            <option
              v-for="child in children"
              :key="child.id"
              :value="child.id"
            >
              {{ child.full_name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="parentStore.loadingChildren && !children.length"
      class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm"
    >
      <div
        class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-sky-100 border-t-sky-500"
      />

      <p class="mt-4 text-sm font-medium text-slate-500">
        Memuat data anak...
      </p>
    </div>

    <!-- No children -->
    <div
      v-else-if="!children.length"
      class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm"
    >
      <div
        class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-2xl"
      >
        👨‍👩‍👧
      </div>

      <h2 class="mt-5 text-lg font-bold text-slate-900">
        Belum ada anak terhubung
      </h2>

      <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
        Akun orang tua ini belum memiliki relasi dengan akun siswa.
        Hubungi administrator sekolah untuk menghubungkan data anak.
      </p>
    </div>

    <template v-else>
      <!-- Error -->
      <div
        v-if="pageError"
        class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"
      >
        {{ pageError }}
      </div>

      <!-- Child card -->
      <section
        class="overflow-hidden rounded-3xl text-white shadow-[0_18px_45px_rgba(2,132,199,0.18)]"
        style="
          background:
            linear-gradient(
              135deg,
              #075985 0%,
              #0284c7 52%,
              #06b6d4 100%
            );
        "
      >
        <div
          class="flex flex-col justify-between gap-6 p-5 sm:p-7 md:flex-row md:items-center"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/20 text-2xl font-extrabold backdrop-blur"
            >
              {{ selectedChildName.charAt(0).toUpperCase() }}
            </div>

            <div>
              <p class="text-xs font-semibold text-white/75">
                Profil Anak
              </p>

              <h2 class="mt-1 text-2xl font-extrabold">
                {{ selectedChildName }}
              </h2>

              <p class="mt-1 text-sm text-white/80">
                Kelas {{ selectedChildClass }}
                <span v-if="selectedChild?.username">
                  · @{{ selectedChild.username }}
                </span>
              </p>
            </div>
          </div>

          <button
            type="button"
            class="w-full rounded-2xl bg-white px-5 py-3 text-sm font-bold text-sky-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-sky-50 sm:w-auto"
            @click="goToValidation"
          >
            Periksa Check-in
          </button>
        </div>
      </section>

      <!-- Summary cards -->
      <div class="grid gap-5 md:grid-cols-3">
        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]"
        >
          <p
            class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
          >
            Check-in Terbaru
          </p>

          <p class="mt-3 text-lg font-extrabold text-slate-950">
            {{ formatDate(latestCheckin?.checkin_date) }}
          </p>

          <span
            class="mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-bold"
            :class="latestStatus.badgeClass"
          >
            {{ latestStatus.label }}
          </span>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]"
        >
          <div class="flex items-center justify-between">
            <p
              class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
            >
              Progres Terbaru
            </p>

            <p class="text-sm font-extrabold text-sky-600">
              {{ latestCompletedCount }}/{{ latestTotalCount }}
            </p>
          </div>

          <p class="mt-3 text-3xl font-extrabold text-slate-950">
            {{ latestProgress }}%
          </p>

          <div
            class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-200"
          >
            <div
              class="h-full rounded-full bg-sky-500 transition-all duration-500"
              :style="{ width: `${latestProgress}%` }"
            />
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)]"
        >
          <div class="flex items-center justify-between">
            <p
              class="text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400"
            >
              Progres Bulan Ini
            </p>

            <p class="text-sm font-extrabold text-emerald-600">
              {{ Math.round(recapPercentage) }}%
            </p>
          </div>

          <p class="mt-3 text-sm font-semibold text-slate-700">
            {{ recapCompletedDays }} dari {{ recapTotalDays }} hari lengkap
          </p>

          <div
            class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-200"
          >
            <div
              class="h-full rounded-full bg-emerald-500 transition-all duration-500"
              :style="{ width: `${recapPercentage}%` }"
            />
          </div>
        </article>
      </div>

      <!-- Latest check-in -->
      <section
        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_12px_34px_rgba(15,23,42,0.05)] sm:p-6"
      >
        <div
          class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-center"
        >
          <div>
            <h2 class="text-xl font-extrabold text-slate-950">
              Check-in Terbaru
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              Ringkasan kebiasaan terbaru dari {{ selectedChildName }}.
            </p>
          </div>

          <button
            type="button"
            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 sm:w-auto"
            @click="goToHistory"
          >
            Lihat Riwayat
          </button>
        </div>

        <div
          v-if="isLoading && !latestCheckin"
          class="py-12 text-center text-sm font-medium text-slate-500"
        >
          Memuat check-in terbaru...
        </div>

        <div
          v-else-if="!latestCheckin"
          class="py-12 text-center"
        >
          <p class="text-sm font-semibold text-slate-500">
            Belum ada data check-in dari anak.
          </p>
        </div>

        <div
          v-else
          class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
          <article
            v-for="item in latestItems"
            :key="item.id ?? item.habit_id"
            class="rounded-2xl border p-4 transition"
            :class="
              item.is_done
                ? 'border-emerald-200 bg-emerald-50/60'
                : 'border-slate-200 bg-slate-50'
            "
          >
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-lg font-bold"
                :class="
                  item.is_done
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-slate-200 text-slate-500'
                "
              >
                {{ habitIcon(item) }}
              </div>

              <div class="min-w-0">
                <h3 class="truncate text-sm font-bold text-slate-900">
                  {{ item.habit?.name }}
                </h3>

                <p
                  class="mt-1 text-xs font-semibold"
                  :class="
                    item.is_done
                      ? 'text-emerald-700'
                      : 'text-slate-500'
                  "
                >
                  {{ item.is_done ? 'Selesai' : 'Belum dilakukan' }}
                </p>
              </div>
            </div>

            <p
              v-if="item.notes"
              class="mt-4 line-clamp-2 text-xs leading-5 text-slate-500"
            >
              {{ item.notes }}
            </p>
          </article>
        </div>
      </section>

      <!-- Quick actions -->
      <section
        class="grid gap-5 md:grid-cols-3"
      >
        <button
          type="button"
          class="rounded-2xl bg-sky-600 p-5 text-left text-white shadow-[0_12px_30px_rgba(2,132,199,0.22)] transition hover:-translate-y-1 hover:bg-sky-700"
          @click="goToValidation"
        >
          <p class="text-sm font-extrabold">
            Validasi Check-in
          </p>

          <p class="mt-2 text-xs leading-5 text-white/80">
            Periksa dan validasi kebiasaan yang dilakukan anak.
          </p>
        </button>

        <button
          type="button"
          class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-1 hover:border-sky-300"
          @click="goToHistory"
        >
          <p class="text-sm font-extrabold text-slate-900">
            Riwayat Check-in
          </p>

          <p class="mt-2 text-xs leading-5 text-slate-500">
            Lihat catatan kebiasaan anak berdasarkan tanggal.
          </p>
        </button>

        <button
          type="button"
          class="rounded-2xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-1 hover:border-emerald-300"
          @click="goToRecap"
        >
          <p class="text-sm font-extrabold text-slate-900">
            Rekap Perkembangan
          </p>

          <p class="mt-2 text-xs leading-5 text-slate-500">
            Pantau konsistensi kebiasaan anak selama satu bulan.
          </p>
        </button>
      </section>
    </template>
  </section>
</template>
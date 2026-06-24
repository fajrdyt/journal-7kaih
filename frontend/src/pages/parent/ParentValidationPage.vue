<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useParentStore } from '../../stores/parentStore'

const route = useRoute()
const router = useRouter()
const parentStore = useParentStore()

const selectedCheckinId = ref(null)
const pageError = ref('')
const successMessage = ref('')

const children = computed(() => parentStore.children)
const selectedChild = computed(() => parentStore.selectedChild)
const currentCheckin = computed(() => parentStore.currentCheckin)

const selectedChildId = computed({
  get() {
    return parentStore.selectedChildId ?? ''
  },

  set(value) {
    parentStore.setSelectedChild(value)
  },
})

const sortedCheckins = computed(() => {
  return [...parentStore.checkins].sort((first, second) => {
    return String(second.checkin_date ?? '').localeCompare(
      String(first.checkin_date ?? ''),
    )
  })
})

const currentItems = computed(() => {
  return Array.isArray(currentCheckin.value?.items)
    ? currentCheckin.value.items
    : []
})

const completedCount = computed(() => {
  return currentItems.value.filter((item) => item.is_done).length
})

const totalCount = computed(() => {
  return currentItems.value.length
})

const progressPercentage = computed(() => {
  if (!totalCount.value) return 0

  return Math.round(
    (completedCount.value / totalCount.value) * 100,
  )
})

const validatedCount = computed(() => {
  return currentItems.value.filter((item) => {
    return isParentValidated(item)
  }).length
})

const itemsWaitingValidation = computed(() => {
  return currentItems.value.filter((item) => {
    return item.is_done && !isParentValidated(item)
  })
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

const isInitialLoading = computed(() => {
  return (
    parentStore.loadingChildren &&
    !children.value.length
  )
})

function clearFeedback() {
  pageError.value = ''
  successMessage.value = ''
  parentStore.clearError()
}

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

function formatShortDate(value) {
  if (!value) return '-'

  const dateString = String(value).slice(0, 10)
  const [year, month, day] = dateString.split('-').map(Number)

  if (!year || !month || !day) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(year, month - 1, day))
}

function formatValidationTime(value) {
  if (!value) return null

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return null

  return new Intl.DateTimeFormat('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

function habitIcon(item) {
  const code = String(
    item?.habit?.code ?? '',
  ).toUpperCase()

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

function checkinProgress(checkin) {
  const completed = Number(
    checkin?.completed_count ?? 0,
  )

  const total = Number(
    checkin?.total_count ?? 0,
  )

  if (!total) return 0

  return Math.round((completed / total) * 100)
}

function extractRole(value) {
  if (!value) return ''

  if (typeof value === 'object') {
    return String(
      value.name ??
      value.code ??
      value.slug ??
      '',
    ).toLowerCase()
  }

  return String(value).toLowerCase()
}

function isParentValidation(validation) {
  if (!validation) return false

  if (
    validation.is_parent === true ||
    validation.parent_validated === true
  ) {
    return true
  }

  const roleValues = [
    validation.validator_role,
    validation.role,
    validation.validator?.role,
    validation.validator_type,
    validation.validation_type,
    validation.source,
    validation.context,
  ]
    .map(extractRole)
    .join(' ')

  return (
    roleValues.includes('orang_tua') ||
    roleValues.includes('parent') ||
    roleValues.includes('rumah')
  )
}

function getParentValidation(item) {
  if (item?.parent_validation) {
    return item.parent_validation
  }

  if (
    item?.is_parent_validated ||
    item?.parent_validated
  ) {
    return {
      validated_at:
        item.parent_validated_at ??
        item.validated_at ??
        null,
    }
  }

  const validations = Array.isArray(item?.validations)
    ? item.validations
    : []

  return validations.find(isParentValidation) ?? null
}

function isParentValidated(item) {
  return Boolean(getParentValidation(item))
}

function validationTimestamp(item) {
  const validation = getParentValidation(item)

  return (
    validation?.validated_at ??
    validation?.created_at ??
    item?.parent_validated_at ??
    null
  )
}

async function loadCheckins(preferredCheckinId = null) {
  if (!selectedChildId.value) return

  clearFeedback()

  try {
    await parentStore.fetchChildCheckins(
      selectedChildId.value,
      {
        per_page: 30,
      },
    )

    const requestedCheckin = preferredCheckinId
      ? sortedCheckins.value.find((checkin) => {
          return (
            Number(checkin.id) ===
            Number(preferredCheckinId)
          )
        })
      : null

    const targetCheckin =
      requestedCheckin ??
      sortedCheckins.value[0] ??
      null

    if (!targetCheckin) {
      selectedCheckinId.value = null
      parentStore.currentCheckin = null
      return
    }

    selectedCheckinId.value = targetCheckin.id

    await parentStore.fetchCheckinDetail(
      targetCheckin.id,
    )
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal mengambil data check-in anak.'
  }
}

async function handleChildChange() {
  parentStore.currentCheckin = null
  selectedCheckinId.value = null

  await loadCheckins()
}

async function handleSelectCheckin(checkinId) {
  if (
    parentStore.loadingDetail ||
    Number(selectedCheckinId.value) ===
      Number(checkinId)
  ) {
    return
  }

  clearFeedback()
  selectedCheckinId.value = checkinId

  try {
    await parentStore.fetchCheckinDetail(checkinId)
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal mengambil detail check-in.'
  }
}

async function handleValidateItem(item) {
  if (
    !item?.id ||
    !item.is_done ||
    isParentValidated(item) ||
    parentStore.validating
  ) {
    return
  }

  clearFeedback()

  try {
    await parentStore.validateItem(item.id)

    successMessage.value =
      `${item.habit?.name ?? 'Kebiasaan'} berhasil divalidasi.`
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal memvalidasi kebiasaan.'
  }
}

async function handleValidateAll() {
  if (
    !currentCheckin.value?.id ||
    !itemsWaitingValidation.value.length ||
    parentStore.validating
  ) {
    return
  }

  clearFeedback()

  try {
    await parentStore.validateHome(
      currentCheckin.value.id,
    )

    successMessage.value =
      'Semua kebiasaan yang memenuhi syarat berhasil divalidasi.'
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal memvalidasi check-in.'
  }
}

function goToDashboard() {
  router.push('/parent/dashboard')
}

onMounted(async () => {
  clearFeedback()

  try {
    if (!parentStore.children.length) {
      await parentStore.fetchChildren()
    }

    const queryStudentId = Number(
      route.query.student,
    )

    if (
      queryStudentId &&
      parentStore.children.some((child) => {
        return Number(child.id) === queryStudentId
      })
    ) {
      parentStore.setSelectedChild(queryStudentId)
    }

    await loadCheckins(route.query.checkin)
  } catch (error) {
    pageError.value =
      error.response?.data?.message ??
      parentStore.error ??
      'Gagal memuat halaman validasi.'
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
        class="flex flex-col justify-between gap-5 lg:flex-row lg:items-center"
      >
        <div>
          <p
            class="text-xs font-bold uppercase tracking-[0.18em] text-sky-600"
          >
            Validasi Orang Tua
          </p>

          <h1
            class="mt-2 text-2xl font-extrabold tracking-tight text-slate-950 sm:text-3xl"
          >
            Validasi Check-in Anak
          </h1>

          <p class="mt-2 text-sm leading-6 text-slate-500">
            Periksa kebiasaan yang telah dilakukan anak dan
            berikan validasi sebagai orang tua.
          </p>
        </div>

        <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
          <button
            type="button"
            class="w-full rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 sm:w-auto"
            @click="goToDashboard"
          >
            Kembali ke Dashboard
          </button>

          <div
            v-if="children.length"
            class="w-full sm:min-w-56"
          >
            <select
              v-model="selectedChildId"
              class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
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
    </div>

    <!-- Initial loading -->
    <div
      v-if="isInitialLoading"
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
      <h2 class="text-lg font-bold text-slate-900">
        Belum ada anak terhubung
      </h2>

      <p class="mt-2 text-sm text-slate-500">
        Hubungi administrator untuk menghubungkan akun anak.
      </p>
    </div>

    <template v-else>
      <!-- Child summary -->
      <div
        class="rounded-3xl bg-gradient-to-r from-sky-800 via-sky-600 to-cyan-500 p-5 text-white shadow-[0_16px_40px_rgba(2,132,199,0.18)] sm:p-6"
      >
        <div
          class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-xl font-extrabold"
            >
              {{ selectedChildName.charAt(0).toUpperCase() }}
            </div>

            <div>
              <p class="text-xs font-semibold text-white/75">
                Anak yang dipantau
              </p>

              <h2 class="mt-1 text-xl font-extrabold">
                {{ selectedChildName }}
              </h2>

              <p class="mt-1 text-sm text-white/80">
                Kelas {{ selectedChildClass }}
              </p>
            </div>
          </div>

          <div
            v-if="currentCheckin"
            class="w-full rounded-2xl bg-white/15 px-5 py-3 text-sm backdrop-blur sm:w-auto"
          >
            <p class="font-semibold text-white/75">
              Tanggal check-in
            </p>

            <p class="mt-1 font-bold text-white">
              {{ formatDate(currentCheckin.checkin_date) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Feedback -->
      <div
        v-if="successMessage"
        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700"
      >
        {{ successMessage }}
      </div>

      <div
        v-if="pageError"
        class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-semibold text-red-700"
      >
        {{ pageError }}
      </div>

      <div
        class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]"
      >
        <!-- Check-in list -->
        <aside
          class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
        >
          <div>
            <h2 class="text-lg font-extrabold text-slate-950">
              Daftar Check-in
            </h2>

            <p class="mt-1 text-sm text-slate-500">
              Pilih tanggal yang ingin diperiksa.
            </p>
          </div>

          <div
            v-if="
              parentStore.loadingCheckins &&
              !sortedCheckins.length
            "
            class="py-12 text-center text-sm font-medium text-slate-500"
          >
            Memuat riwayat check-in...
          </div>

          <div
            v-else-if="!sortedCheckins.length"
            class="py-12 text-center"
          >
            <p class="text-sm font-semibold text-slate-500">
              Belum ada check-in dari anak.
            </p>
          </div>

          <div
            v-else
            class="mt-5 space-y-3"
          >
            <button
              v-for="checkin in sortedCheckins"
              :key="checkin.id"
              type="button"
              class="w-full rounded-2xl border p-4 text-left transition"
              :class="
                Number(selectedCheckinId) === Number(checkin.id)
                  ? 'border-sky-400 bg-sky-50 shadow-sm'
                  : 'border-slate-200 bg-white hover:border-sky-300 hover:bg-sky-50/50'
              "
              @click="handleSelectCheckin(checkin.id)"
            >
              <div
                class="flex items-start justify-between gap-3"
              >
                <div>
                  <p class="text-sm font-bold text-slate-900">
                    {{ formatShortDate(checkin.checkin_date) }}
                  </p>

                  <p class="mt-1 text-xs font-medium text-slate-500">
                    {{ checkin.completed_count }}/{{
                      checkin.total_count
                    }}
                    kebiasaan selesai
                  </p>
                </div>

                <span
                  class="rounded-full px-2.5 py-1 text-[10px] font-bold"
                  :class="
                    checkinProgress(checkin) >= 100
                      ? 'bg-emerald-100 text-emerald-700'
                      : 'bg-amber-100 text-amber-700'
                  "
                >
                  {{ checkinProgress(checkin) }}%
                </span>
              </div>
            </button>
          </div>
        </aside>

        <!-- Check-in detail -->
        <main
          class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
        >
          <div
            v-if="parentStore.loadingDetail"
            class="py-16 text-center"
          >
            <div
              class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-sky-100 border-t-sky-500"
            />

            <p class="mt-4 text-sm font-medium text-slate-500">
              Memuat detail check-in...
            </p>
          </div>

          <div
            v-else-if="!currentCheckin"
            class="py-16 text-center"
          >
            <p class="text-sm font-semibold text-slate-500">
              Pilih check-in untuk melihat detail.
            </p>
          </div>

          <template v-else>
            <!-- Detail header -->
            <div
              class="flex flex-col justify-between gap-5 border-b border-slate-100 pb-6 sm:flex-row sm:items-center"
            >
              <div>
                <h2 class="text-xl font-extrabold text-slate-950">
                  {{ formatDate(currentCheckin.checkin_date) }}
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                  {{ completedCount }}/{{ totalCount }}
                  kebiasaan selesai ·
                  {{ validatedCount }} telah divalidasi orang tua
                </p>
              </div>

              <button
                type="button"
                class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                :disabled="
                  parentStore.validating ||
                  !itemsWaitingValidation.length
                "
                @click="handleValidateAll"
              >
                {{
                  parentStore.validating
                    ? 'Memvalidasi...'
                    : 'Validasi Semua'
                }}
              </button>
            </div>

            <!-- Progress -->
            <div
              class="mt-6 rounded-2xl border border-slate-100 bg-slate-50 p-5"
            >
              <div
                class="flex items-center justify-between text-sm font-semibold"
              >
                <span class="text-slate-600">
                  Progres check-in
                </span>

                <span class="text-sky-700">
                  {{ progressPercentage }}%
                </span>
              </div>

              <div
                class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-200"
              >
                <div
                  class="h-full rounded-full bg-sky-500 transition-all duration-500"
                  :style="{
                    width: `${progressPercentage}%`,
                  }"
                />
              </div>
            </div>

            <!-- General notes -->
            <div
              v-if="currentCheckin.notes"
              class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-5"
            >
              <p
                class="text-xs font-bold uppercase tracking-wide text-blue-500"
              >
                Catatan Umum
              </p>

              <p
                class="mt-2 text-sm leading-6 text-blue-900"
              >
                {{ currentCheckin.notes }}
              </p>
            </div>

            <!-- Habit items -->
            <div class="mt-6 space-y-4">
              <article
                v-for="item in currentItems"
                :key="item.id ?? item.habit_id"
                class="rounded-2xl border p-4 transition sm:p-5"
                :class="
                  isParentValidated(item)
                    ? 'border-emerald-200 bg-emerald-50/50'
                    : item.is_done
                      ? 'border-sky-200 bg-white'
                      : 'border-slate-200 bg-slate-50'
                "
              >
                <div
                  class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center"
                >
                  <div class="flex min-w-0 items-start gap-4">
                    <div
                      class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-lg font-bold"
                      :class="
                        isParentValidated(item)
                          ? 'bg-emerald-100 text-emerald-700'
                          : item.is_done
                            ? 'bg-sky-100 text-sky-700'
                            : 'bg-slate-200 text-slate-500'
                      "
                    >
                      {{ habitIcon(item) }}
                    </div>

                    <div class="min-w-0">
                      <h3
                        class="text-sm font-bold text-slate-900"
                      >
                        {{ item.habit?.name }}
                      </h3>

                      <p
                        class="mt-1 text-xs font-semibold"
                        :class="
                          item.is_done
                            ? 'text-sky-700'
                            : 'text-slate-500'
                        "
                      >
                        {{
                          item.is_done
                            ? 'Dilakukan'
                            : 'Belum dilakukan'
                        }}
                      </p>

                      <p
                        v-if="item.notes"
                        class="mt-3 text-sm leading-6 text-slate-600"
                      >
                        {{ item.notes }}
                      </p>
                    </div>
                  </div>

                  <div class="shrink-0">
                    <div
                      v-if="isParentValidated(item)"
                      class="rounded-xl bg-emerald-100 px-4 py-2.5 text-center"
                    >
                      <p
                        class="text-xs font-bold text-emerald-700"
                      >
                        Sudah divalidasi
                      </p>

                      <p
                        v-if="formatValidationTime(validationTimestamp(item))"
                        class="mt-1 text-[10px] text-emerald-600"
                      >
                        {{
                          formatValidationTime(
                            validationTimestamp(item),
                          )
                        }}
                      </p>
                    </div>

                    <button
                      v-else-if="item.is_done"
                      type="button"
                      class="rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-50"
                      :disabled="parentStore.validating"
                      @click="handleValidateItem(item)"
                    >
                      Validasi
                    </button>

                    <span
                      v-else
                      class="inline-flex rounded-xl bg-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-500"
                    >
                      Tidak perlu divalidasi
                    </span>
                  </div>
                </div>
              </article>
            </div>
          </template>
        </main>
      </div>
    </template>
  </section>
</template>
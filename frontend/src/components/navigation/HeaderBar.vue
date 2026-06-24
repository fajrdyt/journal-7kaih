<script setup>
import {
  computed,
  ref,
  watch,
} from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

const emit = defineEmits(['toggle-sidebar'])

const router = useRouter()
const authStore = useAuthStore()

const avatarLoadError = ref(false)

const displayName = computed(() => {
  return (
    authStore.user?.display_name ??
    authStore.user?.full_name ??
    authStore.user?.name ??
    authStore.user?.username ??
    'Pengguna'
  )
})

const initialName = computed(() => {
  return (
    displayName.value
      .trim()
      .charAt(0)
      .toUpperCase() || 'P'
  )
})

const avatarUrl = computed(() => {
  return String(
    authStore.user?.avatar_url ?? '',
  ).trim()
})

const normalizedRole = computed(() => {
  const role = String(authStore.userRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const aliases = {
    student: 'siswa',
    siswa: 'siswa',
    parent: 'orang_tua',
    orang_tua: 'orang_tua',
    orangtua: 'orang_tua',
    teacher: 'guru',
    guru: 'guru',
    admin: 'admin',
  }

  return aliases[role] ?? role
})

const settingsPath = computed(() => {
  if (normalizedRole.value === 'siswa') {
    return '/student/settings'
  }

  return '/settings'
})

function openSettings() {
  router.push(settingsPath.value)
}

function handleAvatarLoad() {
  avatarLoadError.value = false
}

function handleAvatarError() {
  avatarLoadError.value = true
}

watch(
  avatarUrl,
  () => {
    avatarLoadError.value = false
  },
  {
    immediate: true,
  },
)
</script>

<template>
  <header
    class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-slate-200/80 bg-slate-50/95 px-4 py-3 backdrop-blur sm:px-6 lg:px-8"
  >
    <div class="flex min-w-0 items-center gap-3">
      <button
        type="button"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 lg:hidden"
        aria-label="Buka navigasi"
        @click="emit('toggle-sidebar')"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          class="h-5 w-5"
        >
          <path
            d="M4 7h16M4 12h16M4 17h16"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          />
        </svg>
      </button>

      <div class="min-w-0">
        <h1
          class="truncate text-base font-bold text-slate-900 sm:text-xl"
        >
          Jurnal Kebiasaan
        </h1>

        <p
          class="hidden truncate text-xs font-medium text-slate-400 sm:block"
        >
          Jurnal 7 Kebiasaan Anak Indonesia Hebat
        </p>
      </div>
    </div>

    <button
      type="button"
      class="flex min-w-0 items-center gap-3 rounded-2xl p-1.5 transition hover:bg-white hover:shadow-sm"
      aria-label="Buka pengaturan akun"
      @click="openSettings"
    >
      <div class="hidden min-w-0 text-right sm:block">
        <p
          class="max-w-44 truncate text-sm font-semibold text-slate-800"
        >
          {{ displayName }}
        </p>

        <p class="text-xs font-medium text-slate-400">
          Lihat pengaturan
        </p>
      </div>

      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-blue-100 bg-blue-100 text-sm font-bold text-blue-700 shadow-sm"
      >
        <img
          v-if="avatarUrl && !avatarLoadError"
          :key="avatarUrl"
          :src="avatarUrl"
          :alt="`Foto profil ${displayName}`"
          class="h-full w-full object-cover"
          @load="handleAvatarLoad"
          @error="handleAvatarError"
        />

        <span v-else>
          {{ initialName }}
        </span>
      </div>
    </button>
  </header>
</template>
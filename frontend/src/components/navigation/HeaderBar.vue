<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

defineProps({
  showMenuButton: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['toggle-sidebar'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const accountMenu = ref(null)
const accountMenuOpen = ref(false)
const logoutModalOpen = ref(false)
const loggingOut = ref(false)
const logoutError = ref('')
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

const initials = computed(() => {
  const words = displayName.value
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!words.length) {
    return 'P'
  }

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words.at(-1)[0]}`.toUpperCase()
})

const avatarUrl = computed(() => {
  return String(
    authStore.user?.avatar_url ?? '',
  ).trim()
})

const normalizedRole = computed(() => {
  const sourceRole =
    typeof authStore.userRole === 'object'
      ? authStore.userRole?.name ??
        authStore.userRole?.code ??
        ''
      : authStore.userRole

  const role = String(sourceRole ?? '')
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

const roleLabel = computed(() => {
  const labels = {
    siswa: 'Siswa',
    orang_tua: 'Orang Tua',
    guru: 'Guru',
    admin: 'Admin',
  }

  return labels[normalizedRole.value] ?? 'Pengguna'
})

const settingsPath = computed(() => {
  const paths = {
    siswa: '/student/settings',
    orang_tua: '/parent/settings',
    guru: '/settings',
    admin: '/settings',
  }

  return paths[normalizedRole.value] ?? '/settings'
})

function toggleAccountMenu() {
  accountMenuOpen.value = !accountMenuOpen.value
}

function closeAccountMenu() {
  accountMenuOpen.value = false
}

async function openSettings() {
  closeAccountMenu()

  if (route.path !== settingsPath.value) {
    await router.push(settingsPath.value)
  }
}

function openLogoutModal() {
  logoutError.value = ''
  closeAccountMenu()
  logoutModalOpen.value = true
}

function closeLogoutModal() {
  if (loggingOut.value) {
    return
  }

  logoutModalOpen.value = false
  logoutError.value = ''
}

async function confirmLogout() {
  if (loggingOut.value) {
    return
  }

  loggingOut.value = true
  logoutError.value = ''

  try {
    await authStore.logout()

    logoutModalOpen.value = false

    await router.replace('/login')
  } catch (error) {
    logoutError.value =
      error.response?.data?.message ??
      'Gagal keluar dari akun. Silakan coba kembali.'
  } finally {
    loggingOut.value = false
  }
}

function handleAvatarLoad() {
  avatarLoadError.value = false
}

function handleAvatarError() {
  avatarLoadError.value = true
}

function handleDocumentClick(event) {
  if (
    accountMenu.value &&
    !accountMenu.value.contains(event.target)
  ) {
    closeAccountMenu()
  }
}

function handleDocumentKeydown(event) {
  if (event.key !== 'Escape') {
    return
  }

  closeAccountMenu()

  if (!loggingOut.value) {
    closeLogoutModal()
  }
}

watch(
  () => route.fullPath,
  () => {
    closeAccountMenu()
  },
)

watch(
  avatarUrl,
  () => {
    avatarLoadError.value = false
  },
  {
    immediate: true,
  },
)

watch(logoutModalOpen, (isOpen) => {
  document.body.style.overflow = isOpen
    ? 'hidden'
    : ''
})

onMounted(() => {
  document.addEventListener(
    'click',
    handleDocumentClick,
  )

  document.addEventListener(
    'keydown',
    handleDocumentKeydown,
  )
})

onBeforeUnmount(() => {
  document.removeEventListener(
    'click',
    handleDocumentClick,
  )

  document.removeEventListener(
    'keydown',
    handleDocumentKeydown,
  )

  document.body.style.overflow = ''
})
</script>

<template>
  <header
    class="sticky top-0 z-30 flex min-h-16 items-center justify-between border-b border-slate-200/80 bg-slate-50/95 px-4 py-3 backdrop-blur sm:px-6 lg:px-8"
  >
    <div class="flex min-w-0 items-center gap-3">
      <button
        v-if="showMenuButton"
        type="button"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-primary-tint hover:bg-primary-tint hover:text-primary-deep lg:hidden"
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
          class="truncate text-base font-bold text-slate-800 sm:text-xl"
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

    <div
      ref="accountMenu"
      class="relative"
    >
      <button
        type="button"
        class="flex min-w-0 items-center gap-3 rounded-2xl p-1.5 transition hover:bg-white hover:shadow-sm"
        aria-label="Buka menu akun"
        aria-haspopup="menu"
        :aria-expanded="accountMenuOpen"
        @click.stop="toggleAccountMenu"
      >
        <div class="hidden min-w-0 text-right sm:block">
          <p
            class="max-w-44 truncate text-sm font-semibold text-slate-800"
          >
            {{ displayName }}
          </p>

          <p class="text-xs font-medium text-slate-400">
            {{ roleLabel }}
          </p>
        </div>

        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-primary-tint bg-primary-tint text-xs font-bold text-primary-deep shadow-sm"
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
            {{ initials }}
          </span>
        </div>

        <svg
          viewBox="0 0 20 20"
          fill="none"
          class="hidden h-4 w-4 shrink-0 text-slate-400 transition-transform sm:block"
          :class="{
            'rotate-180': accountMenuOpen,
          }"
          aria-hidden="true"
        >
          <path
            d="m5 7.5 5 5 5-5"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>

      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="-translate-y-1 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="-translate-y-1 opacity-0"
      >
        <div
          v-if="accountMenuOpen"
          class="absolute right-0 top-[calc(100%+10px)] z-50 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_18px_45px_rgba(15,23,42,0.16)]"
          role="menu"
        >
          <div class="border-b border-slate-100 px-4 py-4">
            <div class="flex items-center gap-3">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-tint text-xs font-bold text-primary-deep"
              >
                <img
                  v-if="avatarUrl && !avatarLoadError"
                  :src="avatarUrl"
                  :alt="`Foto profil ${displayName}`"
                  class="h-full w-full object-cover"
                />

                <span v-else>
                  {{ initials }}
                </span>
              </div>

              <div class="min-w-0">
                <p
                  class="truncate text-sm font-bold text-slate-900"
                >
                  {{ displayName }}
                </p>

                <p
                  class="mt-0.5 text-xs font-medium text-slate-400"
                >
                  {{ roleLabel }}
                </p>
              </div>
            </div>
          </div>

          <div class="p-2">
            <button
              type="button"
              role="menuitem"
              class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-slate-600 transition hover:bg-primary-tint hover:text-primary-deep"
              @click="openSettings"
            >
              <span
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-tint text-primary-deep"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  class="h-4 w-4"
                >
                  <circle
                    cx="12"
                    cy="12"
                    r="3"
                    stroke="currentColor"
                    stroke-width="1.8"
                  />

                  <path
                    d="M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                    stroke="currentColor"
                    stroke-width="1.8"
                  />
                </svg>
              </span>

              <span>Pengaturan Akun</span>
            </button>

            <button
              type="button"
              role="menuitem"
              class="mt-1 flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loggingOut"
              @click="openLogoutModal"
            >
              <span
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  class="h-4 w-4"
                >
                  <path
                    d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                  />

                  <path
                    d="M14 8l4 4-4 4M18 12H9"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
              </span>

              <span>Logout</span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </header>

  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="logoutModalOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/50 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-modal-title"
        @click.self="closeLogoutModal"
      >
        <div
          class="w-full max-w-sm rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_24px_70px_rgba(15,23,42,0.28)] sm:p-6"
        >
          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600"
          >
            <svg
              viewBox="0 0 24 24"
              fill="none"
              class="h-6 w-6"
            >
              <path
                d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
              />

              <path
                d="M14 8l4 4-4 4M18 12H9"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <h2
            id="logout-modal-title"
            class="mt-5 text-xl font-extrabold text-slate-900"
          >
            Keluar dari akun?
          </h2>

          <p class="mt-2 text-sm leading-6 text-slate-500">
            Kamu perlu login kembali untuk mengakses aplikasi
            dan melihat jurnal kebiasaan.
          </p>

          <div
            v-if="logoutError"
            class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
          >
            {{ logoutError }}
          </div>

          <div
            class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
          >
            <button
              type="button"
              class="w-full rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
              :disabled="loggingOut"
              @click="closeLogoutModal"
            >
              Batal
            </button>

            <button
              type="button"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
              :disabled="loggingOut"
              @click="confirmLogout"
            >
              <span
                v-if="loggingOut"
                class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
              ></span>

              {{
                loggingOut
                  ? 'Sedang keluar...'
                  : 'Ya, Logout'
              }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
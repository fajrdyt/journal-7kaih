<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'
import { useSettingsStore } from '../../stores/settingsStore'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const user = computed(() => authStore.user)

const displayName = computed(() => {
  return user.value?.display_name ?? user.value?.full_name ?? user.value?.name ?? 'Siswa'
})

const initialName = computed(() => {
  return displayName.value?.charAt(0)?.toUpperCase() ?? 'S'
})

const roleLabel = computed(() => {
  const role = user.value?.role

  if (role === 'siswa' || role === 'student') return 'Siswa'
  if (role === 'guru' || role === 'teacher') return 'Guru'
  if (role === 'orang_tua' || role === 'parent') return 'Orang Tua'
  if (role === 'admin') return 'Admin'

  return role ?? '-'
})

const className = computed(() => {
  return (
    user.value?.class?.name ??
    user.value?.class_room?.name ??
    user.value?.classRoom?.name ??
    user.value?.class_name ??
    '-'
  )
})

const studentIdentity = computed(() => {
  return user.value?.username ?? user.value?.email ?? '-'
})

async function handleLogout() {
  await authStore.logout()

  router.push('/login')
}

onMounted(async () => {
  if (!settingsStore.initialized) {
    settingsStore.init()
  }

  if (!authStore.user && authStore.token) {
    await authStore.fetchUser()
  }
})
</script>

<template>
  <section class="space-y-6">
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600">
        Pengaturan
      </p>

      <h1 class="mt-2 text-2xl font-bold text-slate-900">
        Pengaturan Akun
      </h1>

      <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
        Lihat informasi akun, atur tema tampilan, dan kelola sesi login kamu.
      </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_1fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center gap-4">
          <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-xl font-bold text-blue-700">
            {{ initialName }}
          </div>

          <div>
            <h2 class="text-lg font-bold text-slate-900">
              {{ displayName }}
            </h2>

            <p class="text-sm text-slate-500">
              {{ roleLabel }}
            </p>
          </div>
        </div>

        <div class="mt-6 space-y-4">
          <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
              Nama Lengkap
            </p>

            <p class="mt-1 font-medium text-slate-800">
              {{ user?.full_name ?? user?.name ?? '-' }}
            </p>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
              Username
            </p>

            <p class="mt-1 font-medium text-slate-800">
              {{ user?.username ?? '-' }}
            </p>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
              Email
            </p>

            <p class="mt-1 font-medium text-slate-800">
              {{ user?.email ?? '-' }}
            </p>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
              Nomor Telepon
            </p>

            <p class="mt-1 font-medium text-slate-800">
              {{ user?.phone ?? '-' }}
            </p>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Kelas
              </p>

              <p class="mt-1 font-medium text-slate-800">
                {{ className }}
              </p>
            </div>

            <div class="rounded-2xl bg-slate-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Role
              </p>

              <p class="mt-1 font-medium text-slate-800">
                {{ roleLabel }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-bold text-slate-900">
            Tampilan
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Pilih tema yang nyaman digunakan saat mengisi jurnal harian.
          </p>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <button
              type="button"
              class="rounded-2xl border px-4 py-3 text-sm font-semibold transition"
              :class="settingsStore.theme === 'light'
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300'"
              @click="settingsStore.setTheme('light')"
            >
              Terang
            </button>

            <button
              type="button"
              class="rounded-2xl border px-4 py-3 text-sm font-semibold transition"
              :class="settingsStore.theme === 'dark'
                ? 'border-blue-500 bg-blue-50 text-blue-700'
                : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300'"
              @click="settingsStore.setTheme('dark')"
            >
              Gelap
            </button>
          </div>

          <div class="mt-5 rounded-2xl bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-800">
              Tema saat ini
            </p>

            <p class="mt-1 text-sm text-slate-500">
              Kamu sedang menggunakan mode
              <span class="font-semibold text-slate-900">
                {{ settingsStore.themeLabel }}
              </span>.
            </p>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-bold text-slate-900">
            Sesi Akun
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Kamu sedang login sebagai
            <span class="font-semibold text-slate-800">
              {{ studentIdentity }}
            </span>.
          </p>

          <button
            type="button"
            class="mt-6 w-full rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>

        <div class="rounded-3xl border border-blue-100 bg-blue-50 p-6">
          <h2 class="text-base font-bold text-blue-900">
            Catatan
          </h2>

          <p class="mt-2 text-sm leading-6 text-blue-700">
            Edit profil belum tersedia pada tahap ini. Data akun masih mengikuti data yang tersimpan di sistem.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
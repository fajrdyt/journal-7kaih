<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const menus = [
  {
    label: 'Dashboard',
    to: '/student/dashboard',
  },
  {
    label: 'Check-in Harian',
    to: '/student/checkin',
  },
  {
    label: 'Riwayat',
    to: '/student/history',
  },
  {
    label: 'Rekap',
    to: '/student/recap',
  },
  {
    label: 'Pengaturan',
    to: '/student/settings',
  },
]

async function handleLogout() {
  await authStore.logout()

  router.push('/login')
}
</script>

<template>
  <aside class="flex h-full min-h-screen w-72 flex-col border-r border-slate-200 bg-white px-5 py-6">
    <div class="mb-8">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-lg font-bold text-white">
          7
        </div>

        <div>
          <p class="text-lg font-bold text-slate-900">
            7KAIH
          </p>
          <p class="text-xs font-medium text-slate-500">
            Jurnal Siswa
          </p>
        </div>
      </div>
    </div>

    <nav class="flex flex-1 flex-col gap-2">
      <RouterLink
        v-for="menu in menus"
        :key="menu.to"
        :to="menu.to"
        class="rounded-2xl px-4 py-3 text-sm font-semibold transition"
        :class="$route.path === menu.to
          ? 'bg-blue-600 text-white shadow-sm'
          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
      >
        {{ menu.label }}
      </RouterLink>
    </nav>

    <div class="mt-6 rounded-3xl bg-slate-50 p-4">
      <p class="text-sm font-semibold text-slate-900">
        {{ authStore.user?.display_name ?? authStore.user?.name ?? 'Siswa' }}
      </p>

      <p class="mt-1 text-xs text-slate-500">
        {{ authStore.user?.email ?? authStore.user?.username ?? '-' }}
      </p>

      <button
        type="button"
        class="mt-4 w-full rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50"
        @click="handleLogout"
      >
        Logout
      </button>
    </div>
  </aside>
</template>
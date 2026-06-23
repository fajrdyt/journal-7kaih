<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

const menus = [
  {
    label: 'Dashboard',
    to: '/student/dashboard',
    icon: '▦',
  },
  {
    label: 'Check-in',
    to: '/student/checkin',
    icon: '☑',
  },
  {
    label: 'Riwayat',
    to: '/student/history',
    icon: '↺',
  },
  {
    label: 'Rekap',
    to: '/student/recap',
    icon: '▣',
  },
  { label: 'Pengaturan', 
  to: '/student/settings', 
  icon: '⚙', 
  },
]

async function handleLogout() {
  await authStore.logout()

  router.push('/login')
}
</script>

<template>
  <aside class="fixed left-0 top-0 flex h-screen w-60 flex-col bg-white shadow-[18px_0_45px_rgba(15,23,42,0.04)]">
    <div class="px-6 py-6">
      <h1 class="text-lg font-extrabold leading-none text-sky-700">
        Jurnal 7KAIH
      </h1>

      <p class="mt-2 text-xs font-medium text-slate-400">
        SMA N 1 Mirit
      </p>
    </div>

    <nav class="mt-4 flex flex-1 flex-col gap-2 px-4">
      <RouterLink
        v-for="menu in menus"
        :key="menu.to"
        :to="menu.to"
        class="flex items-center gap-3 rounded-r-xl border-l-4 px-4 py-3 text-sm font-semibold transition"
        :class="$route.path === menu.to
          ? 'border-sky-600 bg-sky-50 text-sky-700'
          : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
      >
        <span class="w-5 text-center text-base">
          {{ menu.icon }}
        </span>

        <span>{{ menu.label }}</span>
      </RouterLink>
    </nav>

    <div class="px-6 py-6">
      <button
        type="button"
        class="flex items-center gap-3 text-sm font-medium text-slate-500 transition hover:text-red-600"
        @click="handleLogout"
      >
        <span>↪</span>
        <span>Logout</span>
      </button>
    </div>
  </aside>
</template>
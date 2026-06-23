```vue
<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const normalizedRole = computed(() => {
  const role = String(authStore.userRole ?? '')
    .trim()
    .toLowerCase()

  const aliases = {
    student: 'siswa',
    siswa: 'siswa',
    parent: 'orang_tua',
    orang_tua: 'orang_tua',
    teacher: 'guru',
    guru: 'guru',
    admin: 'admin',
  }

  return aliases[role] ?? role
})

const menus = computed(() => {
  const roleMenus = {
    siswa: [
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
      {
        label: 'Pengaturan',
        to: '/student/settings',
        icon: '⚙',
      },
    ],

    orang_tua: [
      {
        label: 'Dashboard',
        to: '/parent/dashboard',
        icon: '▦',
      },
      {
        label: 'Validasi',
        to: '/parent/validation',
        icon: '☑',
      },
    ],

    guru: [
      {
        label: 'Dashboard',
        to: '/teacher/dashboard',
        icon: '▦',
      },
    ],

    admin: [
      {
        label: 'Dashboard',
        to: '/admin/dashboard',
        icon: '▦',
      },
    ],
  }

  return roleMenus[normalizedRole.value] ?? []
})

function isMenuActive(menuPath) {
  return (
    route.path === menuPath ||
    route.path.startsWith(`${menuPath}/`)
  )
}

async function handleLogout() {
  await authStore.logout()

  router.push('/login')
}
</script>

<template>
  <aside
    class="fixed left-0 top-0 z-40 flex h-screen w-60 flex-col bg-white shadow-[18px_0_45px_rgba(15,23,42,0.04)]"
  >
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
        :class="
          isMenuActive(menu.to)
            ? 'border-sky-600 bg-sky-50 text-sky-700'
            : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-800'
        "
      >
        <span class="w-5 text-center text-base">
          {{ menu.icon }}
        </span>

        <span>
          {{ menu.label }}
        </span>
      </RouterLink>

      <div
        v-if="!menus.length"
        class="rounded-xl bg-slate-50 px-4 py-3 text-xs font-medium text-slate-500"
      >
        Menu belum tersedia untuk role ini.
      </div>
    </nav>

    <div class="px-6 py-6">
      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-slate-500 transition hover:bg-red-50 hover:text-red-600"
        @click="handleLogout"
      >
        <span class="w-5 text-center">
          ↪
        </span>

        <span>
          Logout
        </span>
      </button>
    </div>
  </aside>
</template>
```

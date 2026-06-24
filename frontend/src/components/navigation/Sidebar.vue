<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

defineProps({
  mobileOpen: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

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
  {
    label: 'Riwayat',
    to: '/parent/history',
    icon: '↺',
  },
  {
    label: 'Rekap',
    to: '/parent/recap',
    icon: '▣',
  },
  {
    label: 'Pengaturan',
    to: '/parent/settings',
    icon: '⚙',
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

function closeSidebar() {
  emit('close')
}

async function handleLogout() {
  emit('close')
  await authStore.logout()
  await router.replace('/login')
}
</script>

<template>
  <aside
    class="fixed left-0 top-0 z-50 flex h-dvh w-60 flex-col bg-white shadow-[18px_0_45px_rgba(15,23,42,0.08)] transition-transform duration-300 lg:z-40 lg:translate-x-0 lg:shadow-[18px_0_45px_rgba(15,23,42,0.04)]"
    :class="
      mobileOpen
        ? 'translate-x-0'
        : '-translate-x-full'
    "
  >
    <div
      class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-6 lg:border-b-0 lg:px-6"
    >
      <div>
        <h1 class="text-lg font-extrabold leading-none text-sky-700">
          Jurnal 7KAIH
        </h1>

        <p class="mt-2 text-xs font-medium text-slate-400">
          SMA N 1 Mirit
        </p>
      </div>

      <button
        type="button"
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 lg:hidden"
        aria-label="Tutup navigasi"
        @click="closeSidebar"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          class="h-5 w-5"
        >
          <path
            d="M6 6l12 12M18 6L6 18"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          />
        </svg>
      </button>
    </div>

    <nav
      class="mt-3 flex flex-1 flex-col gap-2 overflow-y-auto px-4 pb-4 lg:mt-4"
    >
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
        @click="closeSidebar"
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

    <div class="border-t border-slate-100 px-4 py-5 lg:px-6 lg:py-6">
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
<template>
  <aside
    class="app-sidebar"
    :class="{ 'is-open': mobileOpen }"
  >
    <div class="sidebar-header">
      <RouterLink
        :to="dashboardPath"
        class="brand"
        @click="closeSidebar"
      >
        <div class="brand-mark">
          7K
        </div>

        <div class="brand-copy">
          <strong>Jurnal 7KAIH</strong>
          <span>SMA N 1 Mirit</span>
        </div>
      </RouterLink>

      <button
        type="button"
        class="close-sidebar"
        aria-label="Tutup navigasi"
        @click="closeSidebar"
      >
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" />
        </svg>
      </button>
    </div>

    <nav class="menu" aria-label="Navigasi utama">
      <RouterLink
        v-for="item in menuItems"
        :key="item.to"
        :to="item.to"
        class="menu-item"
        :class="{ active: isItemActive(item) }"
        :aria-current="
          isItemActive(item) ? 'page' : undefined
        "
        @click="closeSidebar"
      >
        <span class="menu-icon">
          <svg
            v-if="item.icon === 'dashboard'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <rect
              x="3"
              y="3"
              width="7"
              height="7"
              rx="1.5"
            />
            <rect
              x="14"
              y="3"
              width="7"
              height="7"
              rx="1.5"
            />
            <rect
              x="3"
              y="14"
              width="7"
              height="7"
              rx="1.5"
            />
            <rect
              x="14"
              y="14"
              width="7"
              height="7"
              rx="1.5"
            />
          </svg>

          <svg
            v-else-if="item.icon === 'checkin'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <rect
              x="5"
              y="3"
              width="14"
              height="18"
              rx="2"
            />
            <path d="M9 3.5h6" />
            <path d="m8 12 2.2 2.2L16 8.5" />
          </svg>

          <svg
            v-else-if="item.icon === 'history'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path d="M3 12a9 9 0 1 0 3-6.7" />
            <path d="M3 4v5h5" />
            <path d="M12 7v5l3 2" />
          </svg>

          <svg
            v-else-if="item.icon === 'recap'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path d="M4 20V10" />
            <path d="M10 20V4" />
            <path d="M16 20v-7" />
            <path d="M22 20H2" />
          </svg>

          <svg
            v-else-if="item.icon === 'monitoring'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <circle cx="17" cy="9" r="2.5" />
            <path d="M15.5 14.5a4.5 4.5 0 0 1 5 4.5" />
          </svg>

          <svg
            v-else-if="item.icon === 'validation'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16.5 8" />
          </svg>

          <svg
            v-else-if="item.icon === 'users'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle cx="9" cy="8" r="3" />
            <path d="M3 20a6 6 0 0 1 12 0" />
            <path d="M16 5.5a3 3 0 0 1 0 5.5" />
            <path d="M17 14a5 5 0 0 1 4 5" />
          </svg>

          <svg
            v-else-if="item.icon === 'classes'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <path
              d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z"
            />
            <path
              d="M20 5.5A2.5 2.5 0 0 0 17.5 3H13v17h4.5A2.5 2.5 0 0 1 20 22V5.5Z"
            />
          </svg>

          <svg
            v-else-if="item.icon === 'habits'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <rect
              x="4"
              y="4"
              width="16"
              height="16"
              rx="3"
            />
            <path d="m8 12 2.5 2.5L16 9" />
          </svg>

          <svg
            v-else-if="item.icon === 'relations'"
            viewBox="0 0 24 24"
            fill="none"
          >
            <circle cx="7" cy="8" r="3" />
            <circle cx="17" cy="8" r="3" />
            <path d="M2 20a5 5 0 0 1 10 0" />
            <path d="M12 20a5 5 0 0 1 10 0" />
            <path d="M10 8h4" />
          </svg>
        </span>

        <span class="menu-label">
          {{ item.label }}
        </span>
      </RouterLink>

      <div
        v-if="!menuItems.length"
        class="empty-menu"
      >
        Menu belum tersedia untuk akun ini.
      </div>
    </nav>

    <div class="sidebar-footer">
      <RouterLink
        :to="settingsPath"
        class="menu-item"
        :class="{ active: isSettingsActive }"
        @click="closeSidebar"
      >
        <span class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="3" />
            <path
              d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.17.37.38.7.6 1 .28.3.67.47 1.1.49H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z"
            />
          </svg>
        </span>

        <span class="menu-label">
          Pengaturan
        </span>
      </RouterLink>

      <button
        type="button"
        class="menu-item logout-item"
        :disabled="loggingOut"
        @click="handleLogout"
      >
        <span class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M10 17l5-5-5-5" />
            <path d="M15 12H3" />
            <path
              d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
            />
          </svg>
        </span>

        <span class="menu-label">
          {{ loggingOut ? 'Keluar...' : 'Logout' }}
        </span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/authStore'
import { ROLES } from '@/utils/constants'

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

const loggingOut = ref(false)

const normalizedRole = computed(() => {
  const sourceRole =
    typeof authStore.role === 'object'
      ? authStore.role?.name ?? authStore.role?.code ?? ''
      : authStore.role

  const value = String(sourceRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const aliases = {
    student: ROLES.SISWA,
    siswa: ROLES.SISWA,
    teacher: ROLES.GURU,
    guru: ROLES.GURU,
    parent: ROLES.ORANG_TUA,
    orang_tua: ROLES.ORANG_TUA,
    orangtua: ROLES.ORANG_TUA,
    admin: ROLES.ADMIN,
  }

  return aliases[value] ?? value
})

const dashboardPath = computed(() => {
  const paths = {
    [ROLES.SISWA]: '/student/dashboard',
    [ROLES.GURU]: '/teacher/dashboard',
    [ROLES.ORANG_TUA]: '/parent/dashboard',
    [ROLES.ADMIN]: '/admin/dashboard',
  }

  return paths[normalizedRole.value] ?? '/login'
})

const settingsPath = computed(() => {
  const paths = {
    [ROLES.SISWA]: '/student/settings',
    [ROLES.ORANG_TUA]: '/parent/settings',
    [ROLES.GURU]: '/settings',
    [ROLES.ADMIN]: '/settings',
  }

  return paths[normalizedRole.value] ?? '/settings'
})

const isSettingsActive = computed(() => {
  return (
    route.path === settingsPath.value ||
    route.path.startsWith(`${settingsPath.value}/`)
  )
})

const menuItems = computed(() => {
  const menus = {
    [ROLES.SISWA]: [
      {
        label: 'Dashboard',
        to: '/student/dashboard',
        icon: 'dashboard',
        exact: true,
      },
      {
        label: 'Check-in',
        to: '/student/checkin',
        icon: 'checkin',
      },
      {
        label: 'Riwayat',
        to: '/student/history',
        icon: 'history',
      },
      {
        label: 'Rekap',
        to: '/student/recap',
        icon: 'recap',
      },
    ],

    [ROLES.GURU]: [
      {
        label: 'Dashboard',
        to: '/teacher/dashboard',
        icon: 'dashboard',
        exact: true,
      },
      {
        label: 'Monitoring & Validasi',
        to: '/teacher/monitoring',
        icon: 'monitoring',
      },
      {
        label: 'Rekap Kelas',
        to: '/teacher/recap',
        icon: 'recap',
      },
    ],

    [ROLES.ORANG_TUA]: [
      {
        label: 'Dashboard',
        to: '/parent/dashboard',
        icon: 'dashboard',
        exact: true,
      },
      {
        label: 'Validasi',
        to: '/parent/validation',
        icon: 'validation',
      },
      {
        label: 'Riwayat',
        to: '/parent/history',
        icon: 'history',
      },
      {
        label: 'Rekap',
        to: '/parent/recap',
        icon: 'recap',
      },
    ],

    [ROLES.ADMIN]: [
      {
        label: 'Dashboard',
        to: '/admin/dashboard',
        icon: 'dashboard',
        exact: true,
      },
      {
        label: 'Pengguna',
        to: '/admin/users',
        icon: 'users',
      },
      {
        label: 'Kelas',
        to: '/admin/classes',
        icon: 'classes',
      },
      {
        label: 'Relasi Orang Tua',
        to: '/admin/relations',
        icon: 'relations',
      },
      {
        label: 'Daftar Kebiasaan',
        to: '/admin/habits',
        icon: 'habits',
      },
    ],
  }

  return menus[normalizedRole.value] ?? []
})

function isItemActive(item) {
  if (item.exact) {
    return route.path === item.to
  }

  return (
    route.path === item.to ||
    route.path.startsWith(`${item.to}/`)
  )
}

function closeSidebar() {
  emit('close')
}

async function handleLogout() {
  if (loggingOut.value) {
    return
  }

  try {
    loggingOut.value = true
    await authStore.logout()
    await router.replace('/login')
  } finally {
    loggingOut.value = false
    closeSidebar()
  }
}
</script>

<style scoped>
.app-sidebar {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  width: 232px;
  height: 100dvh;
  flex-shrink: 0;
  flex-direction: column;
  overflow-y: auto;
  border-right: 1px solid #e6edf5;
  background: #ffffff;
  box-shadow: 8px 0 30px rgba(15, 23, 42, 0.035);
}

.sidebar-header {
  position: relative;
  padding: 24px 18px 20px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  color: inherit;
  text-decoration: none;
}

.brand-mark {
  display: grid;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  place-items: center;
  border-radius: 12px;
  background: #209cee;
  color: #ffffff;
  font-size: 14px;
  font-weight: 900;
  letter-spacing: -0.04em;
  box-shadow: 0 8px 20px rgba(32, 156, 238, 0.24);
}

.brand-copy {
  min-width: 0;
}

.brand-copy strong {
  display: block;
  color: #168ad3;
  font-size: 15px;
  font-weight: 900;
  line-height: 1.2;
}

.brand-copy span {
  display: block;
  margin-top: 4px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.close-sidebar {
  display: none;
}

.menu {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 5px;
  padding: 4px 10px 16px;
}

.menu-item {
  position: relative;
  display: flex;
  width: 100%;
  min-height: 44px;
  align-items: center;
  gap: 12px;
  padding: 0 13px;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: #64748b;
  font: inherit;
  font-size: 12px;
  font-weight: 600;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  transition:
    background 0.18s ease,
    color 0.18s ease,
    transform 0.18s ease;
}

.menu-item::before {
  position: absolute;
  top: 7px;
  bottom: 7px;
  left: -10px;
  width: 3px;
  border-radius: 0 4px 4px 0;
  background: transparent;
  content: '';
}

.menu-item:hover {
  background: #f3f8fd;
  color: #1e86c8;
}

.menu-item.active {
  background: #edf7fe;
  color: #168ad3;
  font-weight: 800;
}

.menu-item.active::before {
  background: #209cee;
}

.menu-icon {
  display: grid;
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  place-items: center;
}

.menu-icon svg {
  width: 18px;
  height: 18px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.menu-label {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.empty-menu {
  margin: 8px 4px;
  padding: 14px;
  border-radius: 12px;
  background: #f8fafc;
  color: #64748b;
  font-size: 12px;
  line-height: 1.5;
}

.sidebar-footer {
  display: flex;
  flex-direction: column;
  gap: 5px;
  margin-top: auto;
  padding: 16px 10px 20px;
  border-top: 1px solid #edf2f7;
}

.logout-item {
  color: #64748b;
}

.logout-item:hover {
  background: #fff1f2;
  color: #dc2626;
}

.logout-item:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 900px) {
  .app-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: min(82vw, 280px);
    transform: translateX(-105%);
    transition: transform 0.24s ease;
  }

  .app-sidebar.is-open {
    transform: translateX(0);
  }

  .close-sidebar {
    position: absolute;
    top: 12px;
    right: 12px;
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 0;
    border-radius: 10px;
    background: #f1f5f9;
    color: #475569;
    cursor: pointer;
  }

  .close-sidebar svg {
    width: 18px;
    height: 18px;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
  }

  .sidebar-header {
    padding-right: 54px;
  }

  .menu-item {
    min-height: 48px;
    font-size: 13px;
  }
}
</style>
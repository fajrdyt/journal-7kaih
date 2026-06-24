<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

const route = useRoute()
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
        icon: 'home',
      },
      {
        label: 'Riwayat',
        to: '/student/history',
        icon: 'history',
      },
      {
        label: 'Check-in',
        to: '/student/checkin',
        icon: 'checkin',
      },
      {
        label: 'Rekap',
        to: '/student/recap',
        icon: 'recap',
      },
      {
        label: 'Pengaturan',
        to: '/student/settings',
        icon: 'settings',
      },
    ],

    orang_tua: [
      {
        label: 'Dashboard',
        to: '/parent/dashboard',
        icon: 'home',
      },
      {
        label: 'Riwayat',
        to: '/parent/history',
        icon: 'history',
      },
      {
        label: 'Validasi',
        to: '/parent/validation',
        icon: 'checkin',
      },
      {
        label: 'Rekap',
        to: '/parent/recap',
        icon: 'recap',
      },
      {
        label: 'Pengaturan',
        to: '/parent/settings',
        icon: 'settings',
      },
    ],

    guru: [
      {
        label: 'Dashboard',
        to: '/teacher/dashboard',
        icon: 'home',
      },
      {
        label: 'Monitoring',
        to: '/teacher/monitoring',
        icon: 'monitoring',
      },
      {
        label: 'Rekap',
        to: '/teacher/recap',
        icon: 'recap',
      },
      {
        label: 'Pengaturan',
        to: '/settings',
        icon: 'settings',
      },
    ],

    admin: [
      {
        label: 'Dashboard',
        to: '/admin/dashboard',
        icon: 'home',
      },
    ],
  }

  return roleMenus[normalizedRole.value] ?? []
})

const navigationStyle = computed(() => {
  return {
    gridTemplateColumns: `repeat(${Math.max(menus.value.length, 1)}, minmax(0, 1fr))`,
  }
})

function isMenuActive(menuPath) {
  return (
    route.path === menuPath ||
    route.path.startsWith(`${menuPath}/`)
  )
}
</script>

<template>
  <nav
    v-if="menus.length"
    class="mobile-navigation lg:hidden"
    aria-label="Navigasi utama"
  >
    <div
      class="mobile-navigation__inner"
      :style="navigationStyle"
    >
      <RouterLink
        v-for="menu in menus"
        :key="menu.to"
        :to="menu.to"
        class="navigation-item"
        :class="{
          'navigation-item--active': isMenuActive(menu.to),
        }"
        :aria-current="
          isMenuActive(menu.to) ? 'page' : undefined
        "
      >
        <span class="navigation-icon">
          <svg
            v-if="menu.icon === 'home'"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <path d="m4 10 8-7 8 7" />
            <path d="M6 9v11h12V9" />
            <path d="M10 20v-6h4v6" />
          </svg>

          <svg
            v-else-if="menu.icon === 'history'"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <path d="M3 12a9 9 0 1 0 3-6.7" />
            <path d="M3 4v6h6" />
            <path d="M12 7v5l3 2" />
          </svg>

          <svg
            v-else-if="menu.icon === 'checkin'"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <rect
              x="5"
              y="3"
              width="14"
              height="18"
              rx="3"
            />
            <path d="M9 3.5h6" />
            <path d="m8.5 12 2.2 2.2 4.8-5" />
          </svg>

          <svg
            v-else-if="menu.icon === 'monitoring'"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" />
            <circle cx="17" cy="9" r="2.5" />
            <path d="M15.5 14.5a4.5 4.5 0 0 1 5 4.5" />
          </svg>

          <svg
            v-else-if="menu.icon === 'recap'"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <path d="M5 20V10" />
            <path d="M12 20V4" />
            <path d="M19 20v-7" />
            <path d="M3 20h18" />
          </svg>

          <svg
            v-else
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="3" />
            <path
              d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06a1.7 1.7 0 0 0-1.87-.34A1.7 1.7 0 0 0 14 20.92V21h-4v-.08a1.7 1.7 0 0 0-1.04-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.56-1.04H3v-4h.04A1.7 1.7 0 0 0 4.6 8.92a1.7 1.7 0 0 0-.34-1.87L4.2 6.99l2.83-2.83.06.06a1.7 1.7 0 0 0 1.87.34A1.7 1.7 0 0 0 10 3.04V3h4v.04a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06a1.7 1.7 0 0 0-.34 1.87A1.7 1.7 0 0 0 20.96 10H21v4h-.04A1.7 1.7 0 0 0 19.4 15Z"
            />
          </svg>
        </span>

        <span class="navigation-label">
          {{ menu.label }}
        </span>
      </RouterLink>
    </div>
  </nav>
</template>

<style scoped>
.mobile-navigation {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 60;
}

.mobile-navigation__inner {
  min-height: 72px;
  display: grid;
  align-items: center;
  padding:
    7px
    8px
    calc(7px + env(safe-area-inset-bottom));
  border-top: 1px solid rgba(226, 232, 240, 0.96);
  border-radius: 22px 22px 0 0;
  background: #ffffff;
  box-shadow: 0 -10px 32px rgba(15, 23, 42, 0.1);
}

.navigation-item {
  position: relative;
  min-width: 0;
  min-height: 58px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 5px 2px;
  border-radius: 15px;
  color: #94a3b8;
  text-decoration: none;
  transition:
    color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease;
}

.navigation-item::before {
  position: absolute;
  top: -7px;
  left: 50%;
  width: 26px;
  height: 3px;
  border-radius: 999px;
  background: #0ea5e9;
  content: '';
  opacity: 0;
  transform: translateX(-50%);
  transition: opacity 0.18s ease;
}

.navigation-item--active {
  color: #0284c7;
}

.navigation-item--active::before {
  opacity: 1;
}

.navigation-icon {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  transition:
    color 0.18s ease,
    background 0.18s ease,
    box-shadow 0.18s ease;
}

.navigation-item--active .navigation-icon {
  background: #e0f2fe;
  color: #0284c7;
  box-shadow: 0 5px 14px rgba(14, 165, 233, 0.12);
}

.navigation-icon svg {
  width: 19px;
  height: 19px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.navigation-label {
  max-width: 100%;
  overflow: hidden;
  font-size: 9px;
  font-weight: 700;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-item:active {
  transform: scale(0.96);
}

@media (max-width: 370px) {
  .mobile-navigation__inner {
    padding-right: 3px;
    padding-left: 3px;
  }

  .navigation-item {
    padding-right: 1px;
    padding-left: 1px;
  }

  .navigation-label {
    font-size: 8px;
  }

  .navigation-icon {
    width: 30px;
    height: 30px;
  }

  .navigation-icon svg {
    width: 18px;
    height: 18px;
  }
}
</style>

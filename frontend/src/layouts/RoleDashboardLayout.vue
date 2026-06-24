<template>
  <div class="dashboard-shell">
    <AppSidebar
      :mobile-open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <button
      v-if="sidebarOpen"
      type="button"
      class="sidebar-backdrop"
      aria-label="Tutup sidebar"
      @click="sidebarOpen = false"
    ></button>

    <section class="workspace">
      <header class="topbar">
        <div class="topbar-left">
          <button
            type="button"
            class="menu-button"
            aria-label="Buka sidebar"
            @click="sidebarOpen = true"
          >
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 7h16" />
              <path d="M4 12h16" />
              <path d="M4 17h16" />
            </svg>
          </button>

          <div class="page-info">
            <span>{{ roleLabel }}</span>
            <strong>{{ pageTitle }}</strong>
          </div>
        </div>

        <button
          type="button"
          class="profile-button"
          aria-label="Buka pengaturan akun"
          @click="router.push('/settings')"
        >
          <span class="avatar">{{ initials }}</span>

          <span class="profile-copy">
            <strong>{{ displayName }}</strong>
            <small>{{ roleLabel }}</small>
          </span>
        </button>
      </header>

      <main class="dashboard-content">
        <router-view />
      </main>
    </section>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AppSidebar from '@/components/common/AppSidebar.vue'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const sidebarOpen = ref(false)

const displayName = computed(() => {
  return (
    authStore.user?.display_name ||
    authStore.user?.full_name ||
    authStore.user?.name ||
    authStore.user?.username ||
    'Pengguna'
  )
})

const initials = computed(() => {
  const words = displayName.value
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (words.length === 0) {
    return 'U'
  }

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words[words.length - 1][0]}`.toUpperCase()
})

const roleLabel = computed(() => {
  const labels = {
    admin: 'Admin',
    siswa: 'Siswa',
    guru: 'Guru',
    orang_tua: 'Orang Tua',
    parent: 'Orang Tua',
  }

  return labels[authStore.role] || 'Pengguna'
})

const pageTitle = computed(() => {
  if (route.meta?.title) {
    return route.meta.title
  }

  const path = route.path

  if (
    path === '/student' ||
    path === '/student/dashboard'
  ) {
    return 'Dashboard'
  }

  if (path.startsWith('/student/checkin')) {
    return 'Check-in'
  }

  if (path.startsWith('/student/history')) {
    return 'Riwayat'
  }

  if (path.startsWith('/student/recap')) {
    return 'Rekap'
  }

  if (
    path === '/teacher' ||
    path === '/teacher/dashboard'
  ) {
    return 'Dashboard'
  }

  if (path.startsWith('/teacher/monitoring')) {
    return 'Monitoring & Validasi'
  }

  if (path.startsWith('/teacher/recap')) {
    return 'Rekap Kelas'
  }

  if (
    path === '/parent' ||
    path === '/parent/dashboard'
  ) {
    return 'Dashboard'
  }

  if (path.startsWith('/parent/validation')) {
    return 'Validasi'
  }

  if (path.startsWith('/parent/history')) {
    return 'Riwayat'
  }

  if (path.startsWith('/parent/recap')) {
    return 'Rekap'
  }

  if (path.startsWith('/admin/dashboard')) {
    return 'Dashboard'
  }

  if (path.startsWith('/admin/users')) {
    return 'Manajemen Pengguna'
  }

  if (path.startsWith('/admin/classes')) {
    return 'Manajemen Kelas'
  }

  if (path.startsWith('/admin/habits')) {
    return 'Manajemen Kebiasaan'
  }

  if (path.startsWith('/admin/relations')) {
    return 'Relasi Siswa dan Orang Tua'
  }

  if (path.startsWith('/settings')) {
    return 'Pengaturan Akun'
  }

  return 'Dashboard'
})

watch(
  () => route.fullPath,
  () => {
    sidebarOpen.value = false
  },
)
</script>

<style scoped>
.dashboard-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 232px minmax(0, 1fr);
  background: #f5f8fc;
  color: #0f172a;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

.workspace {
  min-width: 0;
  min-height: 100vh;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  min-height: 68px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 0 26px;
  background: rgba(255, 255, 255, 0.94);
  border-bottom: 1px solid #e8eef5;
  backdrop-filter: blur(14px);
}

.topbar-left {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 14px;
}

.menu-button {
  display: none;
  width: 38px;
  height: 38px;
  place-items: center;
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #ffffff;
  color: #475569;
  cursor: pointer;
}

.menu-button svg {
  width: 19px;
  height: 19px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.page-info {
  min-width: 0;
}

.page-info span {
  display: block;
  margin-bottom: 2px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.page-info strong {
  display: block;
  overflow: hidden;
  color: #1e293b;
  font-size: 15px;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 7px 4px 4px;
  border: 1px solid transparent;
  border-radius: 24px;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
  transition: background 0.18s ease;
}

.profile-button:hover {
  background: #f1f7fc;
}

.avatar {
  width: 35px;
  height: 35px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  border-radius: 50%;
  background: linear-gradient(135deg, #209cee, #75caff);
  color: #ffffff;
  font-size: 11px;
  font-weight: 900;
  box-shadow: 0 7px 18px rgba(32, 156, 238, 0.22);
}

.profile-copy {
  min-width: 0;
  display: block;
  text-align: left;
}

.profile-copy strong {
  display: block;
  max-width: 145px;
  overflow: hidden;
  color: #1e293b;
  font-size: 12px;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-copy small {
  display: block;
  margin-top: 2px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 600;
}

.dashboard-content {
  min-width: 0;
  padding: 24px;
}

.sidebar-backdrop {
  display: none;
}

@media (max-width: 900px) {
  .dashboard-shell {
    display: block;
  }

  .menu-button {
    display: grid;
  }

  .sidebar-backdrop {
    position: fixed;
    inset: 0;
    z-index: 40;
    display: block;
    border: 0;
    background: rgba(15, 23, 42, 0.38);
    backdrop-filter: blur(2px);
  }

  .topbar {
    min-height: 64px;
    padding: 0 16px;
  }

  .dashboard-content {
    padding: 18px 16px 28px;
  }

  .profile-copy {
    display: none;
  }
}

@media (max-width: 520px) {
  .page-info span {
    display: none;
  }

  .page-info strong {
    max-width: 190px;
    font-size: 14px;
  }
}
</style>
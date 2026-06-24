<template>
  <div class="dashboard-shell">
    <AppSidebar
      :mobile-open="sidebarOpen"
      @close="closeSidebar"
    />

    <button
      v-if="sidebarOpen"
      type="button"
      class="sidebar-backdrop"
      aria-label="Tutup navigasi"
      @click="closeSidebar"
    ></button>

    <section class="workspace">
      <header class="topbar">
        <div class="topbar-left">
          <button
            type="button"
            class="menu-button"
            aria-label="Buka navigasi"
            @click="openSidebar"
          >
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 7h16" />
              <path d="M4 12h16" />
              <path d="M4 17h16" />
            </svg>
          </button>

          <div class="page-info">
            <span>{{ roleLabel }}</span>
            <h1>{{ pageTitle }}</h1>
          </div>
        </div>

        <button
          type="button"
          class="profile-button"
          aria-label="Buka pengaturan akun"
          @click="openSettings"
        >
          <div class="profile-copy">
            <strong>{{ displayName }}</strong>
            <span>{{ roleLabel }}</span>
          </div>

          <div class="avatar">
            <img
              v-if="avatarUrl && !avatarLoadError"
              :key="avatarUrl"
              :src="avatarUrl"
              :alt="`Foto profil ${displayName}`"
              @load="handleAvatarLoad"
              @error="handleAvatarError"
            />

            <span v-else>
              {{ initials }}
            </span>
          </div>
        </button>
      </header>

      <main class="dashboard-content">
        <RouterView />
      </main>
    </section>
  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AppSidebar from '@/components/common/AppSidebar.vue'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const sidebarOpen = ref(false)
const avatarLoadError = ref(false)

const normalizedRole = computed(() => {
  const sourceRole =
    typeof authStore.role === 'object'
      ? authStore.role?.name ??
        authStore.role?.code ??
        ''
      : authStore.role

  const value = String(sourceRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const aliases = {
    student: 'siswa',
    siswa: 'siswa',
    teacher: 'guru',
    guru: 'guru',
    parent: 'orang_tua',
    orang_tua: 'orang_tua',
    orangtua: 'orang_tua',
    admin: 'admin',
  }

  return aliases[value] ?? value
})

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

const roleLabel = computed(() => {
  const labels = {
    siswa: 'Siswa',
    guru: 'Guru',
    orang_tua: 'Orang Tua',
    admin: 'Admin',
  }

  return labels[normalizedRole.value] ?? 'Pengguna'
})

const pageTitle = computed(() => {
  return route.meta?.title ?? 'Dashboard'
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

function openSidebar() {
  sidebarOpen.value = true
}

function closeSidebar() {
  sidebarOpen.value = false
}

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
  () => route.fullPath,
  () => {
    closeSidebar()
  },
)

watch(sidebarOpen, (isOpen) => {
  document.body.style.overflow = isOpen
    ? 'hidden'
    : ''
})

watch(
  avatarUrl,
  () => {
    avatarLoadError.value = false
  },
  {
    immediate: true,
  },
)

onMounted(async () => {
  if (!authStore.token) return

  try {
    await authStore.fetchProfile()
    avatarLoadError.value = false
  } catch {
    avatarLoadError.value = false
  }
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
.dashboard-shell {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 232px minmax(0, 1fr);
  background: #f5f8fc;
  color: #0f172a;
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
  border-bottom: 1px solid #e7edf5;
  background: rgba(255, 255, 255, 0.94);
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
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  place-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #ffffff;
  color: #475569;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    color 0.18s ease;
}

.menu-button:hover {
  border-color: #bae6fd;
  background: #f0f9ff;
  color: #0284c7;
}

.menu-button svg {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.page-info {
  min-width: 0;
}

.page-info span {
  display: block;
  margin-bottom: 3px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.page-info h1 {
  max-width: 440px;
  margin: 0;
  overflow: hidden;
  color: #1e293b;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.3;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-button {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 5px 6px 5px 12px;
  border: 1px solid transparent;
  border-radius: 999px;
  background: transparent;
  color: inherit;
  font: inherit;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    box-shadow 0.18s ease;
}

.profile-button:hover {
  border-color: #e2e8f0;
  background: #ffffff;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}

.profile-copy {
  min-width: 0;
  text-align: right;
}

.profile-copy strong {
  display: block;
  max-width: 170px;
  overflow: hidden;
  color: #1e293b;
  font-size: 12px;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-copy span {
  display: block;
  margin-top: 2px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 600;
}

.avatar {
  width: 38px;
  height: 38px;
  display: grid;
  flex-shrink: 0;
  place-items: center;
  overflow: hidden;
  border: 2px solid #dbeafe;
  border-radius: 50%;
  background: #dbeafe;
  color: #2563eb;
  font-size: 11px;
  font-weight: 900;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.16);
}

.avatar img {
  width: 100%;
  height: 100%;
  display: block;
  border-radius: inherit;
  object-fit: cover;
}

.avatar span {
  display: grid;
  width: 100%;
  height: 100%;
  place-items: center;
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
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(2px);
  }

  .topbar {
    min-height: 64px;
    padding: 0 16px;
  }

  .dashboard-content {
    padding: 18px 16px 28px;
  }
}

@media (max-width: 640px) {
  .profile-copy {
    display: none;
  }

  .profile-button {
    padding: 3px;
  }

  .page-info h1 {
    max-width: 230px;
    font-size: 15px;
  }
}

@media (max-width: 420px) {
  .topbar {
    gap: 10px;
    padding: 0 12px;
  }

  .topbar-left {
    gap: 10px;
  }

  .page-info span {
    display: none;
  }

  .page-info h1 {
    max-width: 180px;
    font-size: 14px;
  }

  .dashboard-content {
    padding: 14px 12px 24px;
  }
}
</style>
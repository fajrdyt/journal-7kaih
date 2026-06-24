<template>
  <div class="dashboard-shell">
    <AppSidebar :mobile-open="false" />

    <section class="workspace">
      <header class="topbar">
        <div class="topbar-left">
          <div class="page-info">
            <span>{{ roleLabel }}</span>
            <h1>{{ pageTitle }}</h1>
          </div>
        </div>

        <div
          ref="accountMenu"
          class="account-menu"
        >
          <button
            type="button"
            class="profile-button"
            aria-label="Buka menu akun"
            aria-haspopup="menu"
            :aria-expanded="accountMenuOpen"
            @click.stop="toggleAccountMenu"
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

            <svg
              viewBox="0 0 20 20"
              fill="none"
              class="profile-chevron"
              :class="{ open: accountMenuOpen }"
              aria-hidden="true"
            >
              <path d="m5 7.5 5 5 5-5" />
            </svg>
          </button>

          <Transition name="account-menu">
            <div
              v-if="accountMenuOpen"
              class="account-dropdown"
              role="menu"
            >
              <div class="account-summary">
                <div class="dropdown-avatar">
                  <img
                    v-if="avatarUrl && !avatarLoadError"
                    :src="avatarUrl"
                    :alt="`Foto profil ${displayName}`"
                  />

                  <span v-else>
                    {{ initials }}
                  </span>
                </div>

                <div>
                  <strong>{{ displayName }}</strong>
                  <span>{{ roleLabel }}</span>
                </div>
              </div>

              <div class="account-actions">
                <button
                  type="button"
                  class="account-action"
                  role="menuitem"
                  @click="openSettings"
                >
                  <span class="account-action-icon">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      aria-hidden="true"
                    >
                      <circle cx="12" cy="8" r="4" />
                      <path d="M4 21a8 8 0 0 1 16 0" />
                    </svg>
                  </span>

                  <span>Pengaturan Akun</span>
                </button>

                <button
                  type="button"
                  class="account-action logout-action"
                  role="menuitem"
                  :disabled="loggingOut"
                  @click="openLogoutModal"
                >
                  <span class="account-action-icon">
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      aria-hidden="true"
                    >
                      <path d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4" />
                      <path d="M14 8l4 4-4 4M18 12H9" />
                    </svg>
                  </span>

                  <span>Logout</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </header>

      <main class="dashboard-content">
        <RouterView />
      </main>
    </section>

    <MobileBottomNavigation />

    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="logoutModalOpen"
          class="logout-overlay"
          role="dialog"
          aria-modal="true"
          aria-labelledby="role-layout-logout-title"
          @click.self="closeLogoutModal"
        >
          <div class="logout-modal">
            <div class="logout-icon">
              <svg
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <path d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4" />
                <path d="M14 8l4 4-4 4M18 12H9" />
              </svg>
            </div>

            <h2 id="role-layout-logout-title">
              Keluar dari akun?
            </h2>

            <p>
              Kamu perlu login kembali untuk mengakses aplikasi dan melihat
              jurnal kebiasaan.
            </p>

            <div
              v-if="logoutError"
              class="logout-error"
            >
              {{ logoutError }}
            </div>

            <div class="logout-actions">
              <button
                type="button"
                class="cancel-button"
                :disabled="loggingOut"
                @click="closeLogoutModal"
              >
                Batal
              </button>

              <button
                type="button"
                class="confirm-logout-button"
                :disabled="loggingOut"
                @click="confirmLogout"
              >
                <span
                  v-if="loggingOut"
                  class="button-loader"
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
import MobileBottomNavigation from '@/components/navigation/MobileBottomNavigation.vue'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const accountMenu = ref(null)
const accountMenuOpen = ref(false)
const logoutModalOpen = ref(false)
const loggingOut = ref(false)
const logoutError = ref('')
const avatarLoadError = ref(false)

const normalizedRole = computed(() => {
  const sourceRole =
    authStore.user?.role ??
    authStore.role ??
    ''

  const rawRole =
    typeof sourceRole === 'object'
      ? sourceRole.name ??
        sourceRole.code ??
        sourceRole.slug ??
        ''
      : sourceRole

  const value = String(rawRole)
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

  if (
    logoutModalOpen.value &&
    !loggingOut.value
  ) {
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

onMounted(async () => {
  document.addEventListener(
    'click',
    handleDocumentClick,
  )

  document.addEventListener(
    'keydown',
    handleDocumentKeydown,
  )

  if (!authStore.token) {
    return
  }

  try {
    await authStore.fetchProfile()
  } catch {
    avatarLoadError.value = false
  }
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
  background: #ffffff;
}

.topbar-left {
  min-width: 0;
  display: flex;
  align-items: center;
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

.account-menu {
  position: relative;
  flex-shrink: 0;
}

.profile-button {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 5px 8px 5px 12px;
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

.avatar,
.dropdown-avatar {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  overflow: hidden;
  border: 2px solid #dbeafe;
  border-radius: 50%;
  background: #dbeafe;
  color: #2563eb;
  font-weight: 900;
}

.avatar {
  width: 38px;
  height: 38px;
  font-size: 11px;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.16);
}

.dropdown-avatar {
  width: 42px;
  height: 42px;
  font-size: 11px;
}

.avatar img,
.dropdown-avatar img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
}

.profile-chevron {
  width: 15px;
  height: 15px;
  color: #94a3b8;
  transition: transform 0.18s ease;
}

.profile-chevron path {
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.profile-chevron.open {
  transform: rotate(180deg);
}

.account-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 250px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  background: #ffffff;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.16);
}

.account-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-bottom: 1px solid #f1f5f9;
}

.account-summary > div:last-child {
  min-width: 0;
}

.account-summary strong {
  display: block;
  overflow: hidden;
  color: #0f172a;
  font-size: 13px;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.account-summary span {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.account-actions {
  padding: 8px;
}

.account-action {
  width: 100%;
  min-height: 44px;
  display: flex;
  align-items: center;
  gap: 11px;
  border: 0;
  border-radius: 11px;
  padding: 0 11px;
  background: transparent;
  color: #475569;
  font: inherit;
  font-size: 12px;
  font-weight: 700;
  text-align: left;
  cursor: pointer;
  transition:
    background 0.18s ease,
    color 0.18s ease;
}

.account-action:hover {
  background: #eff6ff;
  color: #2563eb;
}

.account-action-icon {
  width: 30px;
  height: 30px;
  display: grid;
  flex-shrink: 0;
  place-items: center;
  border-radius: 9px;
  background: #eff6ff;
}

.account-action-icon svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.logout-action {
  margin-top: 3px;
  color: #dc2626;
}

.logout-action:hover {
  background: #fff1f2;
  color: #dc2626;
}

.logout-action .account-action-icon {
  background: #fff1f2;
}

.account-menu-enter-active,
.account-menu-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
}

.account-menu-enter-from,
.account-menu-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

.dashboard-content {
  min-width: 0;
  padding: 24px;
}

.logout-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.5);
}

.logout-modal {
  width: 100%;
  max-width: 370px;
  padding: 24px;
  border: 1px solid #e2e8f0;
  border-radius: 24px;
  background: #ffffff;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
}

.logout-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 16px;
  background: #fff1f2;
  color: #dc2626;
}

.logout-icon svg {
  width: 24px;
  height: 24px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.logout-modal h2 {
  margin: 20px 0 0;
  color: #0f172a;
  font-size: 20px;
  font-weight: 900;
}

.logout-modal > p {
  margin: 9px 0 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
}

.logout-error {
  margin-top: 16px;
  padding: 12px 14px;
  border: 1px solid #fecdd3;
  border-radius: 12px;
  background: #fff1f2;
  color: #be123c;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.55;
}

.logout-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
}

.cancel-button,
.confirm-logout-button {
  min-height: 43px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 11px;
  padding: 0 17px;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.cancel-button {
  border: 1px solid #dce6ef;
  background: #ffffff;
  color: #475569;
}

.confirm-logout-button {
  border: 0;
  background: #dc2626;
  color: #ffffff;
}

.cancel-button:disabled,
.confirm-logout-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.button-loader {
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.18s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 900px) {
  .dashboard-shell {
    display: block;
  }

  .topbar {
    min-height: 64px;
    padding: 0 16px;
  }

  .dashboard-content {
    padding: 18px 16px 104px;
  }
}

@media (max-width: 640px) {
  .profile-copy,
  .profile-chevron {
    display: none;
  }

  .profile-button {
    padding: 3px;
  }

  .page-info h1 {
    max-width: 230px;
    font-size: 15px;
  }

  .account-dropdown {
    width: min(250px, calc(100vw - 24px));
  }
}

@media (max-width: 420px) {
  .topbar {
    gap: 10px;
    padding: 0 12px;
  }

  .page-info span {
    display: none;
  }

  .page-info h1 {
    max-width: 210px;
    font-size: 14px;
  }

  .dashboard-content {
    padding: 14px 12px 98px;
  }

  .logout-overlay {
    align-items: flex-end;
    padding: 12px;
  }

  .logout-modal {
    max-width: none;
    padding: 20px;
    border-radius: 22px;
  }

  .logout-actions {
    flex-direction: column-reverse;
  }

  .cancel-button,
  .confirm-logout-button {
    width: 100%;
  }
}
</style>

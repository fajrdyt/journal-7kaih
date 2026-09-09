<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  // Teks yang muncul di "Kamu sedang login sebagai <identityLabel>."
  identityLabel: {
    type: String,
    default: '',
  },
})

const router = useRouter()
const authStore = useAuthStore()

const logoutModalOpen = ref(false)
const loggingOut = ref(false)
const logoutError = ref('')

function openLogoutModal() {
  logoutError.value = ''
  logoutModalOpen.value = true
}

function closeLogoutModal() {
  if (loggingOut.value) return
  logoutModalOpen.value = false
  logoutError.value = ''
}

async function confirmLogout() {
  if (loggingOut.value) return

  loggingOut.value = true
  logoutError.value = ''

  try {
    await authStore.logout()
    logoutModalOpen.value = false

    // `replace` (bukan `push`) supaya dashboard yang baru di-logout
    // tidak tersisa di history browser.
    await router.replace('/login')
  } catch (error) {
    logoutError.value =
      error.response?.data?.message ?? 'Gagal keluar dari akun. Silakan coba kembali.'
  } finally {
    loggingOut.value = false
  }
}

function handleKeydown(event) {
  if (event.key === 'Escape' && logoutModalOpen.value && !loggingOut.value) {
    closeLogoutModal()
  }
}

watch(logoutModalOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
    <h2 class="text-lg font-bold text-slate-900">Sesi Akun</h2>

    <p class="mt-1 text-sm leading-6 text-slate-500">
      Kamu sedang login sebagai
      <span class="font-semibold text-slate-800">{{ identityLabel }}</span>.
    </p>

    <button
      type="button"
      class="mt-6 w-full rounded-2xl bg-danger px-5 py-3 text-sm font-semibold text-white transition hover:opacity-90"
      @click="openLogoutModal"
    >
      Logout
    </button>
  </div>

  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="logoutModalOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 p-5"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logout-session-title"
        @click.self="closeLogoutModal"
      >
        <div class="w-full max-w-[370px] rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-danger-tint text-danger">
            <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
              <path
                d="M10 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h4"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <path
                d="M14 8l4 4-4 4M18 12H9"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <h2 id="logout-session-title" class="mt-5 text-xl font-bold text-slate-900">
            Keluar dari akun?
          </h2>

          <p class="mt-2 text-sm leading-6 text-slate-500">
            Kamu perlu login kembali untuk mengakses aplikasi dan melihat jurnal kebiasaan.
          </p>

          <div
            v-if="logoutError"
            class="mt-4 rounded-2xl border border-danger-tint bg-danger-tint px-4 py-3 text-sm font-semibold text-danger"
          >
            {{ logoutError }}
          </div>

          <div class="mt-6 flex justify-end gap-2.5">
            <button
              type="button"
              class="min-h-[43px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loggingOut"
              @click="closeLogoutModal"
            >
              Batal
            </button>

            <button
              type="button"
              class="flex min-h-[43px] items-center gap-2 rounded-xl bg-danger px-4 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="loggingOut"
              @click="confirmLogout"
            >
              <span
                v-if="loggingOut"
                class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/45 border-t-white"
              />
              {{ loggingOut ? 'Sedang keluar...' : 'Ya, Logout' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.18s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
</style>

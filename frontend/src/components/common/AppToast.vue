<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref,
  watch,
} from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const visible = ref(false)
const message = ref('')
const type = ref('success')

let hideTimer = null

const toastClasses = computed(() => {
  const variants = {
    success: 'toast-success',
    error: 'toast-error',
    info: 'toast-info',
  }

  return variants[type.value] ?? variants.success
})

function showToast(toastMessage, toastType = 'success') {
  if (!toastMessage) {
    return
  }

  if (hideTimer) {
    clearTimeout(hideTimer)
  }

  message.value = toastMessage
  type.value = toastType
  visible.value = true

  hideTimer = setTimeout(() => {
    visible.value = false
  }, 3000)
}

function readPendingToast() {
  const storedToast = sessionStorage.getItem('app_toast')

  if (!storedToast) {
    return
  }

  sessionStorage.removeItem('app_toast')

  try {
    const toastData = JSON.parse(storedToast)

    showToast(
      toastData.message,
      toastData.type ?? 'success',
    )
  } catch {
    showToast(storedToast)
  }
}

function closeToast() {
  visible.value = false

  if (hideTimer) {
    clearTimeout(hideTimer)
    hideTimer = null
  }
}

watch(
  () => route.fullPath,
  () => {
    requestAnimationFrame(() => {
      readPendingToast()
    })
  },
)

onMounted(() => {
  readPendingToast()
})

onBeforeUnmount(() => {
  if (hideTimer) {
    clearTimeout(hideTimer)
  }
})
</script>

<template>
  <Transition name="toast">
    <div
      v-if="visible"
      class="app-toast"
      :class="toastClasses"
      role="status"
      aria-live="polite"
    >
      <div class="toast-icon">
        <svg
          v-if="type === 'success'"
          viewBox="0 0 24 24"
          fill="none"
          aria-hidden="true"
        >
          <path d="m5 12 4 4L19 6" />
        </svg>

        <svg
          v-else-if="type === 'error'"
          viewBox="0 0 24 24"
          fill="none"
          aria-hidden="true"
        >
          <circle cx="12" cy="12" r="9" />
          <path d="M12 7v6" />
          <path d="M12 17h.01" />
        </svg>

        <svg
          v-else
          viewBox="0 0 24 24"
          fill="none"
          aria-hidden="true"
        >
          <circle cx="12" cy="12" r="9" />
          <path d="M12 11v6" />
          <path d="M12 7h.01" />
        </svg>
      </div>

      <div class="toast-content">
        <strong>
          {{
            type === 'success'
              ? 'Berhasil'
              : type === 'error'
                ? 'Gagal'
                : 'Informasi'
          }}
        </strong>

        <p>{{ message }}</p>
      </div>

      <button
        type="button"
        class="toast-close"
        aria-label="Tutup notifikasi"
        @click="closeToast"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          aria-hidden="true"
        >
          <path d="m6 6 12 12" />
          <path d="m18 6-12 12" />
        </svg>
      </button>
    </div>
  </Transition>
</template>

<style scoped>
.app-toast {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  width: min(390px, calc(100vw - 32px));
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  border: 1px solid;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.16);
}

.toast-success {
  border-color: #bbf7d0;
  color: #15803d;
}

.toast-error {
  border-color: #fecaca;
  color: #dc2626;
}

.toast-info {
  border-color: #bfdbfe;
  color: #2563eb;
}

.toast-icon {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 10px;
}

.toast-success .toast-icon {
  background: #dcfce7;
}

.toast-error .toast-icon {
  background: #fee2e2;
}

.toast-info .toast-icon {
  background: #dbeafe;
}

.toast-icon svg {
  width: 19px;
  height: 19px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.toast-content {
  min-width: 0;
}

.toast-content strong {
  display: block;
  color: #0f172a;
  font-size: 12px;
  font-weight: 800;
}

.toast-content p {
  margin: 3px 0 0;
  color: #475569;
  font-size: 11.5px;
  font-weight: 500;
  line-height: 1.5;
}

.toast-close {
  width: 28px;
  height: 28px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #94a3b8;
  cursor: pointer;
}

.toast-close:hover {
  background: #f1f5f9;
  color: #475569;
}

.toast-close svg {
  width: 16px;
  height: 16px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@media (max-width: 640px) {
  .app-toast {
    top: 14px;
    right: 16px;
    left: 16px;
    width: auto;
  }
}
</style>
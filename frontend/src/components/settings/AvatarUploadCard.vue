<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'

const authStore = useAuthStore()

const avatarInput = ref(null)
const avatarFile = ref(null)
const avatarPreviewUrl = ref('')
const avatarSuccess = ref('')
const avatarError = ref('')

const allowedAvatarTypes = ['image/jpeg', 'image/png', 'image/webp']
const maxAvatarSize = 2 * 1024 * 1024

const user = computed(() => authStore.user)

const displayName = computed(() => {
  return (
    user.value?.display_name ??
    user.value?.full_name ??
    user.value?.name ??
    'Pengguna'
  )
})

const initialName = computed(() => {
  return displayName.value.charAt(0).toUpperCase() || 'P'
})

const avatarUrl = computed(() => {
  return avatarPreviewUrl.value || user.value?.avatar_url || ''
})

const hasAvatar = computed(() => Boolean(user.value?.avatar_url))
const hasSelectedAvatar = computed(() => Boolean(avatarFile.value))

const avatarFileDescription = computed(() => {
  if (!avatarFile.value) return ''
  const sizeInMb = avatarFile.value.size / (1024 * 1024)
  return `${avatarFile.value.name} · ${sizeInMb.toFixed(2)} MB`
})

// Label badge kecil di bawah nama, otomatis menyesuaikan role user
// yang sedang login — dipakai sama di semua halaman settings.
const roleBadgeLabel = computed(() => {
  const sourceRole =
    typeof user.value?.role === 'object'
      ? user.value?.role?.name ?? user.value?.role?.code ?? ''
      : user.value?.role

  const role = String(sourceRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const labels = {
    student: 'Profil Siswa',
    siswa: 'Profil Siswa',
    teacher: 'Profil Guru',
    guru: 'Profil Guru',
    parent: 'Profil Orang Tua',
    orang_tua: 'Profil Orang Tua',
    orangtua: 'Profil Orang Tua',
    admin: 'Profil Admin',
  }

  return labels[role] ?? 'Profil Pengguna'
})

function clearAvatarFeedback() {
  avatarSuccess.value = ''
  avatarError.value = ''
}

function openAvatarPicker() {
  if (authStore.avatarLoading) return

  if (avatarInput.value) {
    avatarInput.value.value = ''
    avatarInput.value.click()
  }
}

function releaseAvatarPreview() {
  if (avatarPreviewUrl.value) {
    URL.revokeObjectURL(avatarPreviewUrl.value)
    avatarPreviewUrl.value = ''
  }
}

function clearAvatarSelection() {
  releaseAvatarPreview()
  avatarFile.value = null

  if (avatarInput.value) {
    avatarInput.value.value = ''
  }
}

function getImageDimensions(url) {
  return new Promise((resolve, reject) => {
    const image = new Image()

    image.onload = () => {
      resolve({ width: image.naturalWidth, height: image.naturalHeight })
    }

    image.onerror = () => {
      reject(new Error('File gambar tidak dapat dibaca.'))
    }

    image.src = url
  })
}

async function handleAvatarChange(event) {
  clearAvatarFeedback()

  const file = event.target.files?.[0]
  if (!file) return

  releaseAvatarPreview()
  avatarFile.value = null

  if (!allowedAvatarTypes.includes(file.type)) {
    avatarError.value = 'Format foto harus JPG, JPEG, PNG, atau WEBP.'
    event.target.value = ''
    return
  }

  if (file.size > maxAvatarSize) {
    avatarError.value = 'Ukuran foto profil maksimal 2 MB.'
    event.target.value = ''
    return
  }

  const previewUrl = URL.createObjectURL(file)

  try {
    const dimensions = await getImageDimensions(previewUrl)

    if (dimensions.width < 128 || dimensions.height < 128) {
      throw new Error(
        'Foto terlalu kecil. Gunakan foto yang lebih jelas dan tidak pecah.',
      )
    }

    if (dimensions.width > 4096 || dimensions.height > 4096) {
      throw new Error(
        'Resolusi foto terlalu besar. Pilih foto lain dengan ukuran yang lebih kecil.',
      )
    }

    avatarFile.value = file
    avatarPreviewUrl.value = previewUrl
  } catch (error) {
    URL.revokeObjectURL(previewUrl)
    avatarError.value = error.message ?? 'Foto profil tidak valid.'
    event.target.value = ''
  }
}

function normalizeAvatarValidation(message) {
  const normalized = String(message ?? '').toLowerCase()

  if (normalized.includes('dimension') || normalized.includes('dimensi')) {
    return 'Foto tidak sesuai. Gunakan foto yang jelas, tidak terlalu kecil, dan tidak terlalu besar.'
  }

  if (normalized.includes('kilobytes') || normalized.includes('maximum') || normalized.includes('maksimal')) {
    return 'Ukuran foto maksimal 2 MB.'
  }

  return message
}

async function handleUploadAvatar() {
  if (authStore.avatarLoading || !avatarFile.value) return

  clearAvatarFeedback()

  try {
    const result = await authStore.uploadAvatar(avatarFile.value)
    clearAvatarSelection()
    avatarSuccess.value = result?.message ?? 'Foto profil berhasil diperbarui.'
  } catch (error) {
    const rawMessage = error.response?.data?.errors?.avatar?.[0]

    avatarError.value = rawMessage
      ? normalizeAvatarValidation(rawMessage)
      : error.response?.data?.message ?? authStore.error ?? 'Gagal memperbarui foto profil.'
  }
}

async function handleDeleteAvatar() {
  if (authStore.avatarLoading || !hasAvatar.value) return

  const confirmed = window.confirm('Hapus foto profil saat ini?')
  if (!confirmed) return

  clearAvatarFeedback()
  clearAvatarSelection()

  try {
    const result = await authStore.deleteAvatar()
    avatarSuccess.value = result?.message ?? 'Foto profil berhasil dihapus.'
  } catch (error) {
    avatarError.value = error.response?.data?.message ?? authStore.error ?? 'Gagal menghapus foto profil.'
  }
}

function handleCancelAvatar() {
  clearAvatarFeedback()
  clearAvatarSelection()
}

onBeforeUnmount(() => {
  releaseAvatarPreview()
})

defineExpose({ displayName, roleBadgeLabel })
</script>

<template>
  <div class="border-b border-slate-100 pb-6">
    <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-start">
      <div class="flex min-w-0 flex-col items-center gap-5 sm:flex-row sm:items-center">
        <div
          class="relative h-44 w-44 shrink-0 overflow-hidden rounded-full bg-slate-200 shadow-sm ring-4 ring-white"
        >
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            :alt="`Foto profil ${displayName}`"
            class="h-full w-full object-cover"
          />

          <div
            v-else
            class="flex h-full w-full items-center justify-center bg-primary-tint text-5xl font-bold text-primary-deep"
          >
            {{ initialName }}
          </div>

          <button
            type="button"
            class="absolute inset-0 flex flex-col items-center justify-end bg-gradient-to-t from-black/90 via-black/45 to-transparent pb-7 text-white transition duration-200 hover:from-black hover:via-black/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/40 disabled:cursor-not-allowed"
            :disabled="authStore.avatarLoading"
            :aria-label="hasAvatar || hasSelectedAvatar ? 'Ganti foto profil' : 'Pilih foto profil'"
            @click="openAvatarPicker"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.2"
              stroke-linecap="round"
              stroke-linejoin="round"
              class="h-10 w-10"
              aria-hidden="true"
            >
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
            </svg>

            <span class="mt-2 text-base font-semibold">
              {{ hasAvatar || hasSelectedAvatar ? 'Ganti foto' : 'Pilih foto' }}
            </span>
          </button>

          <div
            v-if="authStore.avatarLoading"
            class="absolute inset-0 z-10 flex items-center justify-center bg-black/65"
          >
            <div class="h-10 w-10 animate-spin rounded-full border-4 border-white/40 border-t-white" />
          </div>
        </div>

        <div class="min-w-0 text-center sm:text-left">
          <h2 class="truncate text-xl font-bold text-slate-900">
            {{ displayName }}
          </h2>

          <p class="mt-3 max-w-sm text-sm leading-6 text-slate-500">
            Gunakan foto JPG, PNG, atau WEBP maksimal 2 MB.
            Sebaiknya pilih foto persegi, jelas, dan tidak buram.
          </p>

          <button
            v-if="hasAvatar && !hasSelectedAvatar"
            type="button"
            class="mt-3 text-sm font-semibold text-danger transition hover:opacity-80 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="authStore.avatarLoading"
            @click="handleDeleteAvatar"
          >
            Hapus foto profil
          </button>
        </div>
      </div>

      <span
        class="w-fit rounded-full bg-primary-tint px-3 py-1.5 text-xs font-semibold text-primary-deep"
      >
        {{ roleBadgeLabel }}
      </span>
    </div>

    <input
      ref="avatarInput"
      type="file"
      accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
      class="hidden"
      @change="handleAvatarChange"
    />

    <div
      v-if="hasSelectedAvatar"
      class="mt-6 rounded-2xl border border-primary-tint bg-primary-tint/40 p-4"
    >
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <p class="text-sm font-bold text-slate-900">Gunakan foto ini?</p>
          <p class="mt-1 truncate text-xs text-slate-500">{{ avatarFileDescription }}</p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="authStore.avatarLoading"
            @click="handleCancelAvatar"
          >
            Batal
          </button>

          <button
            type="button"
            class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="authStore.avatarLoading"
            @click="handleUploadAvatar"
          >
            {{ authStore.avatarLoading ? 'Mengunggah...' : 'Gunakan Foto' }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="avatarSuccess"
      class="mt-4 rounded-2xl border border-success-tint bg-success-tint px-4 py-3 text-sm font-semibold text-success"
    >
      {{ avatarSuccess }}
    </div>

    <div
      v-if="avatarError"
      class="mt-4 rounded-2xl border border-danger-tint bg-danger-tint px-4 py-3 text-sm font-semibold text-danger"
    >
      {{ avatarError }}
    </div>
  </div>
</template>

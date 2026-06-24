<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'
import { useSettingsStore } from '../../stores/settingsStore'

const router = useRouter()
const authStore = useAuthStore()
const settingsStore = useSettingsStore()

const avatarInput = ref(null)
const avatarFile = ref(null)
const avatarPreviewUrl = ref('')
const avatarSuccess = ref('')
const avatarError = ref('')

const profileSaving = ref(false)
const passwordSaving = ref(false)

const profileSuccess = ref('')
const profileError = ref('')
const profileErrors = ref({})

const passwordSuccess = ref('')
const passwordError = ref('')
const passwordErrors = ref({})

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showPasswordConfirmation = ref(false)

const profileForm = reactive({
  nisn: '',
  full_name: '',
  username: '',
  email: '',
  phone: '',
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const allowedAvatarTypes = [
  'image/jpeg',
  'image/png',
  'image/webp',
]

const maxAvatarSize = 2 * 1024 * 1024

const user = computed(() => authStore.user)

const displayName = computed(() => {
  return (
    user.value?.display_name ??
    user.value?.full_name ??
    user.value?.name ??
    'Siswa'
  )
})

const initialName = computed(() => {
  return displayName.value.charAt(0).toUpperCase() || 'S'
})

const avatarUrl = computed(() => {
  return (
    avatarPreviewUrl.value ||
    user.value?.avatar_url ||
    ''
  )
})

const hasAvatar = computed(() => {
  return Boolean(user.value?.avatar_url)
})

const hasSelectedAvatar = computed(() => {
  return Boolean(avatarFile.value)
})

const avatarFileDescription = computed(() => {
  if (!avatarFile.value) return ''

  const sizeInMb = avatarFile.value.size / (1024 * 1024)

  return `${avatarFile.value.name} · ${sizeInMb.toFixed(2)} MB`
})

const roleLabel = computed(() => {
  const sourceRole =
    typeof user.value?.role === 'object'
      ? user.value?.role?.name ??
        user.value?.role?.code ??
        ''
      : user.value?.role

  const role = String(sourceRole ?? '')
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')

  const labels = {
    student: 'Siswa',
    siswa: 'Siswa',
    teacher: 'Guru',
    guru: 'Guru',
    parent: 'Orang Tua',
    orang_tua: 'Orang Tua',
    orangtua: 'Orang Tua',
    admin: 'Admin',
  }

  return labels[role] ?? 'Pengguna'
})

const className = computed(() => {
  return (
    user.value?.class?.name ??
    user.value?.class_room?.name ??
    user.value?.classRoom?.name ??
    user.value?.class_name ??
    '-'
  )
})

const studentIdentity = computed(() => {
  return user.value?.username ?? user.value?.email ?? '-'
})

const isProfileDirty = computed(() => {
  const currentUser = user.value

  if (!currentUser) return false

  return (
    profileForm.full_name !==
      stringValue(
        currentUser.full_name ?? currentUser.name,
      ) ||
    profileForm.username !==
      stringValue(currentUser.username) ||
    profileForm.email !==
      stringValue(currentUser.email) ||
    profileForm.phone !==
      stringValue(currentUser.phone)
  )
})

function stringValue(value) {
  return value === null || value === undefined
    ? ''
    : String(value)
}

function syncProfileForm(profile = user.value) {
  profileForm.nisn = stringValue(profile?.nisn)
  profileForm.full_name = stringValue(
    profile?.full_name ?? profile?.name,
  )
  profileForm.username = stringValue(profile?.username)
  profileForm.email = stringValue(profile?.email)
  profileForm.phone = stringValue(profile?.phone)
}

function normalizeValidationMessage(field, message) {
  const value = String(message ?? '').trim()
  const normalized = value.toLowerCase()

  const isDuplicate =
    normalized.includes('already been taken') ||
    normalized.includes('has already been taken') ||
    normalized.includes('unique') ||
    normalized.includes('sudah digunakan') ||
    normalized.includes('telah digunakan') ||
    normalized.includes('sudah ada')

  if (field === 'username' && isDuplicate) {
    return 'Username sudah digunakan. Silakan pilih username lain.'
  }

  if (field === 'email' && isDuplicate) {
    return 'Email sudah digunakan oleh akun lain.'
  }

  if (field === 'avatar') {
    if (
      normalized.includes('dimension') ||
      normalized.includes('dimensi')
    ) {
      return 'Foto tidak sesuai. Gunakan foto yang jelas, tidak terlalu kecil, dan tidak terlalu besar.'
    }

    if (
      normalized.includes('kilobytes') ||
      normalized.includes('maximum') ||
      normalized.includes('maksimal')
    ) {
      return 'Ukuran foto maksimal 2 MB.'
    }
  }

  return value
}

function extractValidationErrors(error) {
  const errors = error.response?.data?.errors

  if (!errors || typeof errors !== 'object') {
    return {}
  }

  const fieldAliases = {
    new_password: 'password',
    new_password_confirmation: 'password_confirmation',
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => {
      const resolvedField = fieldAliases[field] || field
      const message = Array.isArray(messages)
        ? messages[0]
        : messages

      return [
        resolvedField,
        normalizeValidationMessage(
          resolvedField,
          message,
        ),
      ]
    }),
  )
}

function clearAvatarFeedback() {
  avatarSuccess.value = ''
  avatarError.value = ''
}

function clearProfileFeedback() {
  profileSuccess.value = ''
  profileError.value = ''
  profileErrors.value = {}
}

function clearPasswordFeedback() {
  passwordSuccess.value = ''
  passwordError.value = ''
  passwordErrors.value = {}
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
      resolve({
        width: image.naturalWidth,
        height: image.naturalHeight,
      })
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
    avatarError.value =
      'Format foto harus JPG, JPEG, PNG, atau WEBP.'

    event.target.value = ''
    return
  }

  if (file.size > maxAvatarSize) {
    avatarError.value =
      'Ukuran foto profil maksimal 2 MB.'

    event.target.value = ''
    return
  }

  const previewUrl = URL.createObjectURL(file)

  try {
    const dimensions = await getImageDimensions(previewUrl)

    if (
      dimensions.width < 128 ||
      dimensions.height < 128
    ) {
      throw new Error(
        'Foto terlalu kecil. Gunakan foto yang lebih jelas dan tidak pecah.',
      )
    }

    if (
      dimensions.width > 4096 ||
      dimensions.height > 4096
    ) {
      throw new Error(
        'Resolusi foto terlalu besar. Pilih foto lain dengan ukuran yang lebih kecil.',
      )
    }

    avatarFile.value = file
    avatarPreviewUrl.value = previewUrl
  } catch (error) {
    URL.revokeObjectURL(previewUrl)

    avatarError.value =
      error.message ?? 'Foto profil tidak valid.'

    event.target.value = ''
  }
}

async function handleUploadAvatar() {
  if (
    authStore.avatarLoading ||
    !avatarFile.value
  ) {
    return
  }

  clearAvatarFeedback()

  try {
    const result = await authStore.uploadAvatar(
      avatarFile.value,
    )

    clearAvatarSelection()

    avatarSuccess.value =
      result?.message ??
      'Foto profil berhasil diperbarui.'
  } catch (error) {
    const errors = extractValidationErrors(error)

    avatarError.value =
      errors.avatar ??
      error.response?.data?.message ??
      authStore.error ??
      'Gagal memperbarui foto profil.'
  }
}

async function handleDeleteAvatar() {
  if (
    authStore.avatarLoading ||
    !hasAvatar.value
  ) {
    return
  }

  const confirmed = window.confirm(
    'Hapus foto profil saat ini?',
  )

  if (!confirmed) return

  clearAvatarFeedback()
  clearAvatarSelection()

  try {
    const result = await authStore.deleteAvatar()

    avatarSuccess.value =
      result?.message ??
      'Foto profil berhasil dihapus.'
  } catch (error) {
    avatarError.value =
      error.response?.data?.message ??
      authStore.error ??
      'Gagal menghapus foto profil.'
  }
}

function handleCancelAvatar() {
  clearAvatarFeedback()
  clearAvatarSelection()
}

function handleResetProfile() {
  clearProfileFeedback()
  syncProfileForm()
}

async function handleUpdateProfile() {
  if (
    profileSaving.value ||
    !isProfileDirty.value
  ) {
    return
  }

  clearProfileFeedback()

  const fullName = profileForm.full_name.trim()
  const username = profileForm.username.trim()
  const email = profileForm.email.trim()
  const phone = profileForm.phone.trim()

  const clientErrors = {}

  if (!fullName) {
    clientErrors.full_name =
      'Nama lengkap wajib diisi.'
  }

  if (!username) {
    clientErrors.username =
      'Username wajib diisi.'
  }

  if (
    email &&
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  ) {
    clientErrors.email =
      'Format email belum valid.'
  }

  if (phone.length > 20) {
    clientErrors.phone =
      'Nomor telepon maksimal 20 karakter.'
  }

  if (Object.keys(clientErrors).length) {
    profileErrors.value = clientErrors
    profileError.value =
      'Periksa kembali data profil yang diisi.'
    return
  }

  profileSaving.value = true

  const payload = {
    full_name: fullName,
    username,
    email: email || null,
    phone: phone || null,
  }

  try {
    const result = await authStore.updateProfile(payload)

    profileSuccess.value =
      result?.message ??
      'Profil berhasil diperbarui.'

    syncProfileForm(result?.user ?? authStore.user)
  } catch (error) {
    const validationErrors =
      extractValidationErrors(error)

    profileErrors.value = validationErrors

    const firstValidationMessage =
      Object.values(validationErrors)[0]

    if (error.response?.status === 422) {
      profileError.value =
        firstValidationMessage ??
        'Data profil belum dapat disimpan. Periksa kembali kolom yang ditandai.'
    } else {
      profileError.value =
        error.response?.data?.message ??
        authStore.error ??
        'Gagal memperbarui profil.'
    }
  } finally {
    profileSaving.value = false
  }
}

function validatePasswordForm() {
  const errors = {}

  if (!passwordForm.current_password) {
    errors.current_password =
      'Password saat ini wajib diisi.'
  }

  if (!passwordForm.password) {
    errors.password =
      'Password baru wajib diisi.'
  } else if (passwordForm.password.length < 8) {
    errors.password =
      'Password baru minimal terdiri dari 8 karakter.'
  }

  if (!passwordForm.password_confirmation) {
    errors.password_confirmation =
      'Konfirmasi password baru wajib diisi.'
  } else if (
    passwordForm.password !==
    passwordForm.password_confirmation
  ) {
    errors.password_confirmation =
      'Konfirmasi password tidak sama.'
  }

  passwordErrors.value = errors

  return Object.keys(errors).length === 0
}

async function handleUpdatePassword() {
  if (passwordSaving.value) return

  clearPasswordFeedback()

  if (!validatePasswordForm()) return

  passwordSaving.value = true

  try {
    const result = await authStore.updatePassword({
      current_password:
        passwordForm.current_password,
      new_password: passwordForm.password,
      new_password_confirmation:
        passwordForm.password_confirmation,
    })

    passwordSuccess.value =
      result?.message ??
      'Password berhasil diperbarui.'

    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''

    showCurrentPassword.value = false
    showNewPassword.value = false
    showPasswordConfirmation.value = false
  } catch (error) {
    passwordErrors.value =
      extractValidationErrors(error)

    passwordError.value =
      error.response?.data?.message ??
      authStore.error ??
      'Gagal memperbarui password.'
  } finally {
    passwordSaving.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  await router.replace('/login')
}

watch(
  user,
  (profile) => {
    if (profile) {
      syncProfileForm(profile)
    }
  },
  {
    immediate: true,
    deep: true,
  },
)

onMounted(async () => {
  if (!settingsStore.initialized) {
    settingsStore.init()
  }

  if (!authStore.token) return

  try {
    await authStore.fetchProfile()
  } catch (error) {
    profileError.value =
      error.response?.data?.message ??
      'Gagal mengambil data profil.'
  }
})

onBeforeUnmount(() => {
  releaseAvatarPreview()
})
</script>

<template>
  <section class="space-y-5 pb-10 pt-4 sm:space-y-6 sm:pt-5 lg:pt-6">
    <div
      class="rounded-[28px] bg-white px-6 py-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)] sm:px-8 sm:py-7"
    >
      <p
        class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600"
      >
        Pengaturan
      </p>

      <h1 class="mt-2 text-2xl font-bold text-slate-900">
        Pengaturan Akun
      </h1>

      <p
        class="mt-2 max-w-2xl text-sm leading-6 text-slate-500"
      >
        Perbarui informasi profil, ubah password, atur
        tampilan, dan kelola sesi akunmu.
      </p>
    </div>

    <div
      v-if="authStore.loading && !user"
      class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm"
    >
      <div
        class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-blue-100 border-t-blue-600"
      />

      <p class="mt-4 text-sm font-medium text-slate-500">
        Memuat data profil...
      </p>
    </div>

    <div
      v-else
      class="grid gap-6 lg:gap-8 xl:grid-cols-[minmax(0,1.15fr)_minmax(380px,0.85fr)] xl:gap-10"
    >
      <form
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
        @submit.prevent="handleUpdateProfile"
      >
        <div
          class="border-b border-slate-100 pb-6"
        >
          <div
            class="flex flex-col justify-between gap-6 lg:flex-row lg:items-start"
          >
            <div
              class="flex min-w-0 flex-col items-center gap-5 sm:flex-row sm:items-center"
            >
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
                  class="flex h-full w-full items-center justify-center bg-blue-100 text-5xl font-bold text-blue-700"
                >
                  {{ initialName }}
                </div>

                <button
                  type="button"
                  class="absolute inset-0 flex flex-col items-center justify-end bg-gradient-to-t from-black/90 via-black/45 to-transparent pb-7 text-white transition duration-200 hover:from-black hover:via-black/60 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-300 disabled:cursor-not-allowed"
                  :disabled="authStore.avatarLoading"
                  :aria-label="
                    hasAvatar || hasSelectedAvatar
                      ? 'Ganti foto profil'
                      : 'Pilih foto profil'
                  "
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
                    <path
                      d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"
                    />
                  </svg>

                  <span class="mt-2 text-base font-semibold">
                    {{
                      hasAvatar || hasSelectedAvatar
                        ? 'Ganti foto'
                        : 'Pilih foto'
                    }}
                  </span>
                </button>

                <div
                  v-if="authStore.avatarLoading"
                  class="absolute inset-0 z-10 flex items-center justify-center bg-black/65"
                >
                  <div
                    class="h-10 w-10 animate-spin rounded-full border-4 border-white/40 border-t-white"
                  />
                </div>
              </div>

              <div class="min-w-0 text-center sm:text-left">
                <h2
                  class="truncate text-xl font-bold text-slate-900"
                >
                  {{ displayName }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                  {{ roleLabel }}

                  <span v-if="className !== '-'">
                    · {{ className }}
                  </span>
                </p>

                <p
                  class="mt-3 max-w-sm text-sm leading-6 text-slate-500"
                >
                  Gunakan foto JPG, PNG, atau WEBP maksimal 2 MB.
                  Sebaiknya pilih foto persegi, jelas, dan tidak buram.
                </p>

                <button
                  v-if="hasAvatar && !hasSelectedAvatar"
                  type="button"
                  class="mt-3 text-sm font-semibold text-red-600 transition hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="authStore.avatarLoading"
                  @click="handleDeleteAvatar"
                >
                  Hapus foto profil
                </button>
              </div>
            </div>

            <span
              class="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"
            >
              Profil Siswa
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
            class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-4"
          >
            <div
              class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-900">
                  Gunakan foto ini?
                </p>

                <p
                  class="mt-1 truncate text-xs text-slate-500"
                >
                  {{ avatarFileDescription }}
                </p>
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
                  class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="authStore.avatarLoading"
                  @click="handleUploadAvatar"
                >
                  {{
                    authStore.avatarLoading
                      ? 'Mengunggah...'
                      : 'Gunakan Foto'
                  }}
                </button>
              </div>
            </div>
          </div>

          <div
            v-if="avatarSuccess"
            class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
          >
            {{ avatarSuccess }}
          </div>

          <div
            v-if="avatarError"
            class="mt-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
          >
            {{ avatarError }}
          </div>
        </div>

        <div class="mt-6">
          <h2 class="text-lg font-bold text-slate-900">
            Edit Profil
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Pastikan informasi akunmu lengkap dan sesuai.
          </p>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
          <div>
            <label
              for="nisn"
              class="text-sm font-semibold text-slate-700"
            >
              NISN
            </label>

            <input
              id="nisn"
              v-model="profileForm.nisn"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              readonly
              class="mt-2 w-full cursor-not-allowed rounded-2xl border bg-slate-50 px-4 py-3 text-sm text-slate-500 outline-none transition placeholder:text-slate-400"
              :class="
                profileErrors.nisn
                  ? 'border-red-300'
                  : 'border-slate-200'
              "
              placeholder="Masukkan NISN"
            />

            <p
              v-if="profileErrors.nisn"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.nisn }}
            </p>
          </div>

          <div>
            <label
              for="full-name"
              class="text-sm font-semibold text-slate-700"
            >
              Nama Lengkap
            </label>

            <input
              id="full-name"
              v-model="profileForm.full_name"
              type="text"
              autocomplete="name"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              :class="
                profileErrors.full_name
                  ? 'border-red-300'
                  : 'border-slate-200'
              "
              placeholder="Masukkan nama lengkap"
              @input="clearProfileFeedback"
            />

            <p
              v-if="profileErrors.full_name"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.full_name }}
            </p>
          </div>

          <div>
            <label
              for="username"
              class="text-sm font-semibold text-slate-700"
            >
              Username
            </label>

            <input
              id="username"
              v-model="profileForm.username"
              type="text"
              autocomplete="username"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              :class="
                profileErrors.username
                  ? 'border-red-300'
                  : 'border-slate-200'
              "
              placeholder="Masukkan username"
              @input="clearProfileFeedback"
            />

            <p
              v-if="!profileErrors.username"
              class="mt-2 text-xs leading-5 text-slate-400"
            >
              Username harus unik dan tidak boleh sama dengan pengguna lain.
            </p>

            <p
              v-if="profileErrors.username"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.username }}
            </p>
          </div>

          <div>
            <label
              for="email"
              class="text-sm font-semibold text-slate-700"
            >
              Email
            </label>

            <input
              id="email"
              v-model="profileForm.email"
              type="email"
              autocomplete="email"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              :class="
                profileErrors.email
                  ? 'border-red-300'
                  : 'border-slate-200'
              "
              placeholder="Masukkan email"
              @input="clearProfileFeedback"
            />

            <p
              v-if="profileErrors.email"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.email }}
            </p>
          </div>

          <div class="sm:col-span-2">
            <label
              for="phone"
              class="text-sm font-semibold text-slate-700"
            >
              Nomor Telepon
            </label>

            <input
              id="phone"
              v-model="profileForm.phone"
              type="tel"
              inputmode="tel"
              autocomplete="tel"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              :class="
                profileErrors.phone
                  ? 'border-red-300'
                  : 'border-slate-200'
              "
              placeholder="Contoh: 081234567890"
              @input="clearProfileFeedback"
            />

            <p
              v-if="profileErrors.phone"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.phone }}
            </p>
          </div>

          <div
            class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
          >
            <p
              class="text-xs font-semibold uppercase tracking-wide text-slate-400"
            >
              Kelas
            </p>

            <p class="mt-1 font-medium text-slate-800">
              {{ className }}
            </p>
          </div>

          <div
            class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
          >
            <p
              class="text-xs font-semibold uppercase tracking-wide text-slate-400"
            >
              Role
            </p>

            <p class="mt-1 font-medium text-slate-800">
              {{ roleLabel }}
            </p>
          </div>
        </div>

        <div
          v-if="profileSuccess"
          class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
        >
          {{ profileSuccess }}
        </div>

        <div
          v-if="profileError"
          class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
        >
          {{ profileError }}
        </div>

        <div
          class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"
        >
          <button
            type="button"
            class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="
              profileSaving || !isProfileDirty
            "
            @click="handleResetProfile"
          >
            Batal
          </button>

          <button
            type="submit"
            class="rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="
              profileSaving || !isProfileDirty
            "
          >
            {{
              profileSaving
                ? 'Menyimpan...'
                : 'Simpan Perubahan'
            }}
          </button>
        </div>
      </form>

      <div class="space-y-6 lg:space-y-8">
        <form
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
          @submit.prevent="handleUpdatePassword"
        >
          <h2 class="text-lg font-bold text-slate-900">
            Ubah Password
          </h2>

          <p class="mt-1 text-sm leading-6 text-slate-500">
            Gunakan password yang kuat dan tidak mudah
            ditebak.
          </p>

          <div class="mt-6 space-y-5">
            <div>
              <label
                for="current-password"
                class="text-sm font-semibold text-slate-700"
              >
                Password Saat Ini
              </label>

              <div class="relative mt-2">
                <input
                  id="current-password"
                  v-model="
                    passwordForm.current_password
                  "
                  :type="
                    showCurrentPassword
                      ? 'text'
                      : 'password'
                  "
                  autocomplete="current-password"
                  class="w-full rounded-2xl border bg-white px-4 py-3 pr-24 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                  :class="
                    passwordErrors.current_password
                      ? 'border-red-300'
                      : 'border-slate-200'
                  "
                  placeholder="Masukkan password saat ini"
                  @input="clearPasswordFeedback"
                />

                <button
                  type="button"
                  class="absolute inset-y-0 right-4 text-xs font-semibold text-blue-600"
                  @click="
                    showCurrentPassword =
                      !showCurrentPassword
                  "
                >
                  {{
                    showCurrentPassword
                      ? 'Sembunyikan'
                      : 'Lihat'
                  }}
                </button>
              </div>

              <p
                v-if="
                  passwordErrors.current_password
                "
                class="mt-2 text-xs font-medium text-red-600"
              >
                {{
                  passwordErrors.current_password
                }}
              </p>
            </div>

            <div>
              <label
                for="new-password"
                class="text-sm font-semibold text-slate-700"
              >
                Password Baru
              </label>

              <div class="relative mt-2">
                <input
                  id="new-password"
                  v-model="passwordForm.password"
                  :type="
                    showNewPassword
                      ? 'text'
                      : 'password'
                  "
                  autocomplete="new-password"
                  class="w-full rounded-2xl border bg-white px-4 py-3 pr-24 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                  :class="
                    passwordErrors.password
                      ? 'border-red-300'
                      : 'border-slate-200'
                  "
                  placeholder="Minimal 8 karakter"
                  @input="clearPasswordFeedback"
                />

                <button
                  type="button"
                  class="absolute inset-y-0 right-4 text-xs font-semibold text-blue-600"
                  @click="
                    showNewPassword =
                      !showNewPassword
                  "
                >
                  {{
                    showNewPassword
                      ? 'Sembunyikan'
                      : 'Lihat'
                  }}
                </button>
              </div>

              <p
                v-if="passwordErrors.password"
                class="mt-2 text-xs font-medium text-red-600"
              >
                {{ passwordErrors.password }}
              </p>
            </div>

            <div>
              <label
                for="password-confirmation"
                class="text-sm font-semibold text-slate-700"
              >
                Konfirmasi Password Baru
              </label>

              <div class="relative mt-2">
                <input
                  id="password-confirmation"
                  v-model="
                    passwordForm.password_confirmation
                  "
                  :type="
                    showPasswordConfirmation
                      ? 'text'
                      : 'password'
                  "
                  autocomplete="new-password"
                  class="w-full rounded-2xl border bg-white px-4 py-3 pr-24 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                  :class="
                    passwordErrors.password_confirmation
                      ? 'border-red-300'
                      : 'border-slate-200'
                  "
                  placeholder="Ulangi password baru"
                  @input="clearPasswordFeedback"
                />

                <button
                  type="button"
                  class="absolute inset-y-0 right-4 text-xs font-semibold text-blue-600"
                  @click="
                    showPasswordConfirmation =
                      !showPasswordConfirmation
                  "
                >
                  {{
                    showPasswordConfirmation
                      ? 'Sembunyikan'
                      : 'Lihat'
                  }}
                </button>
              </div>

              <p
                v-if="
                  passwordErrors.password_confirmation
                "
                class="mt-2 text-xs font-medium text-red-600"
              >
                {{
                  passwordErrors.password_confirmation
                }}
              </p>
            </div>
          </div>

          <div
            v-if="passwordSuccess"
            class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"
          >
            {{ passwordSuccess }}
          </div>

          <div
            v-if="passwordError"
            class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"
          >
            {{ passwordError }}
          </div>

          <button
            type="submit"
            class="mt-6 w-full rounded-2xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="passwordSaving"
          >
            {{
              passwordSaving
                ? 'Memperbarui...'
                : 'Ubah Password'
            }}
          </button>
        </form>

        <div
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
        >
          <h2 class="text-lg font-bold text-slate-900">
            Tampilan
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Pilih tema yang nyaman digunakan saat mengisi
            jurnal.
          </p>

          <div class="mt-6 grid grid-cols-2 gap-3">
            <button
              type="button"
              class="rounded-2xl border px-4 py-3 text-sm font-semibold transition"
              :class="
                settingsStore.theme === 'light'
                  ? 'border-blue-500 bg-blue-50 text-blue-700'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300'
              "
              @click="
                settingsStore.setTheme('light')
              "
            >
              Terang
            </button>

            <button
              type="button"
              class="rounded-2xl border px-4 py-3 text-sm font-semibold transition"
              :class="
                settingsStore.theme === 'dark'
                  ? 'border-blue-500 bg-blue-50 text-blue-700'
                  : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300'
              "
              @click="
                settingsStore.setTheme('dark')
              "
            >
              Gelap
            </button>
          </div>

          <div class="mt-5 rounded-2xl bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-800">
              Tema saat ini
            </p>

            <p class="mt-1 text-sm text-slate-500">
              Kamu sedang menggunakan mode

              <span
                class="font-semibold text-slate-900"
              >
                {{ settingsStore.themeLabel }}
              </span>.
            </p>
          </div>
        </div>

        <div
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
        >
          <h2 class="text-lg font-bold text-slate-900">
            Sesi Akun
          </h2>

          <p
            class="mt-1 text-sm leading-6 text-slate-500"
          >
            Kamu sedang login sebagai

            <span class="font-semibold text-slate-800">
              {{ studentIdentity }}
            </span>.
          </p>

          <button
            type="button"
            class="mt-6 w-full rounded-2xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-700"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
```vue
<script setup>
import {
  computed,
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

const roleLabel = computed(() => {
  const role = user.value?.role

  if (role === 'siswa' || role === 'student') return 'Siswa'
  if (role === 'guru' || role === 'teacher') return 'Guru'
  if (role === 'orang_tua' || role === 'parent') return 'Orang Tua'
  if (role === 'admin') return 'Admin'

  return role ?? '-'
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
    profileForm.nisn !== stringValue(currentUser.nisn) ||
    profileForm.full_name !== stringValue(
      currentUser.full_name ?? currentUser.name,
    ) ||
    profileForm.username !== stringValue(currentUser.username) ||
    profileForm.email !== stringValue(currentUser.email) ||
    profileForm.phone !== stringValue(currentUser.phone)
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

function extractValidationErrors(error) {
  const errors = error.response?.data?.errors

  if (!errors || typeof errors !== 'object') {
    return {}
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field,
      Array.isArray(messages)
        ? messages[0]
        : String(messages),
    ]),
  )
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

function handleResetProfile() {
  clearProfileFeedback()
  syncProfileForm()
}

async function handleUpdateProfile() {
  if (profileSaving.value || !isProfileDirty.value) return

  clearProfileFeedback()
  profileSaving.value = true

  const payload = {
    nisn: profileForm.nisn.trim() || null,
    full_name: profileForm.full_name.trim(),
    username: profileForm.username.trim(),
    email: profileForm.email.trim() || null,
    phone: profileForm.phone.trim() || null,
  }

  try {
    const result = await authStore.updateProfile(payload)

    profileSuccess.value =
      result?.message ?? 'Profil berhasil diperbarui.'

    syncProfileForm(result?.user ?? authStore.user)
  } catch (error) {
    profileErrors.value = extractValidationErrors(error)

    profileError.value =
      error.response?.data?.message ??
      authStore.error ??
      'Gagal memperbarui profil.'
  } finally {
    profileSaving.value = false
  }
}

function validatePasswordForm() {
  const errors = {}

  if (!passwordForm.current_password) {
    errors.current_password = 'Password saat ini wajib diisi.'
  }

  if (!passwordForm.password) {
    errors.password = 'Password baru wajib diisi.'
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
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation:
        passwordForm.password_confirmation,
    })

    passwordSuccess.value =
      result?.message ?? 'Password berhasil diperbarui.'

    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''

    showCurrentPassword.value = false
    showNewPassword.value = false
    showPasswordConfirmation.value = false
  } catch (error) {
    passwordErrors.value = extractValidationErrors(error)

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
  router.push('/login')
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
</script>

<template>
  <section class="space-y-6">
    <!-- Page header -->
    <div
      class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
    >
      <p
        class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-600"
      >
        Pengaturan
      </p>

      <h1 class="mt-2 text-2xl font-bold text-slate-900">
        Pengaturan Akun
      </h1>

      <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
        Perbarui informasi profil, ubah password, atur tampilan,
        dan kelola sesi akunmu.
      </p>
    </div>

    <!-- Initial loading -->
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
      class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(380px,0.85fr)]"
    >
      <!-- Edit profile -->
      <form
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="handleUpdateProfile"
      >
        <div
          class="flex flex-col justify-between gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center"
        >
          <div class="flex items-center gap-4">
            <div
              class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-xl font-bold text-blue-700"
            >
              {{ initialName }}
            </div>

            <div>
              <h2 class="text-lg font-bold text-slate-900">
                {{ displayName }}
              </h2>

              <p class="mt-1 text-sm text-slate-500">
                {{ roleLabel }}
                <span v-if="className !== '-'">
                  · {{ className }}
                </span>
              </p>
            </div>
          </div>

          <span
            class="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700"
          >
            Profil Siswa
          </span>
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
          <!-- NISN -->
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
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
              :class="
                profileErrors.nisn
                  ? 'border-red-300'
                  : 'border-slate-200'
              "
              placeholder="Masukkan NISN"
              @input="clearProfileFeedback"
            />

            <p
              v-if="profileErrors.nisn"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.nisn }}
            </p>
          </div>

          <!-- Full name -->
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

          <!-- Username -->
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
              v-if="profileErrors.username"
              class="mt-2 text-xs font-medium text-red-600"
            >
              {{ profileErrors.username }}
            </p>
          </div>

          <!-- Email -->
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

          <!-- Phone -->
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

          <!-- Read-only class -->
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

          <!-- Read-only role -->
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
            :disabled="profileSaving || !isProfileDirty"
            @click="handleResetProfile"
          >
            Batal
          </button>

          <button
            type="submit"
            class="rounded-2xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="profileSaving || !isProfileDirty"
          >
            {{
              profileSaving
                ? 'Menyimpan...'
                : 'Simpan Perubahan'
            }}
          </button>
        </div>
      </form>

      <div class="space-y-6">
        <!-- Change password -->
        <form
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
          @submit.prevent="handleUpdatePassword"
        >
          <h2 class="text-lg font-bold text-slate-900">
            Ubah Password
          </h2>

          <p class="mt-1 text-sm leading-6 text-slate-500">
            Gunakan password yang kuat dan tidak mudah ditebak.
          </p>

          <div class="mt-6 space-y-5">
            <!-- Current password -->
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
                  v-model="passwordForm.current_password"
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
                v-if="passwordErrors.current_password"
                class="mt-2 text-xs font-medium text-red-600"
              >
                {{ passwordErrors.current_password }}
              </p>
            </div>

            <!-- New password -->
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
                    showNewPassword ? 'text' : 'password'
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
                    showNewPassword = !showNewPassword
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

            <!-- Confirmation -->
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

        <!-- Theme -->
        <div
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <h2 class="text-lg font-bold text-slate-900">
            Tampilan
          </h2>

          <p class="mt-1 text-sm text-slate-500">
            Pilih tema yang nyaman digunakan saat mengisi jurnal.
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
              @click="settingsStore.setTheme('light')"
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
              @click="settingsStore.setTheme('dark')"
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
              <span class="font-semibold text-slate-900">
                {{ settingsStore.themeLabel }}
              </span>.
            </p>
          </div>
        </div>

        <!-- Session -->
        <div
          class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        >
          <h2 class="text-lg font-bold text-slate-900">
            Sesi Akun
          </h2>

          <p class="mt-1 text-sm leading-6 text-slate-500">
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
```

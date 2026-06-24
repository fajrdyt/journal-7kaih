<template>
  <section class="settings-page">
    <div class="page-banner">
      <div>
        <p class="banner-kicker">Pengaturan Akun</p>

        <h1>Kelola Profil dan Keamanan</h1>

        <p>
          Perbarui informasi akun, password, dan sesi masuk yang digunakan
          pada Jurnal 7KAIH.
        </p>
      </div>

      <div class="profile-summary">
        <span class="summary-avatar">
          <img
            v-if="avatarUrl && !avatarLoadError"
            :src="avatarUrl"
            :alt="`Foto profil ${displayName}`"
            @load="avatarLoadError = false"
            @error="avatarLoadError = true"
          />

          <span v-else>{{ initials }}</span>
        </span>

        <div class="summary-copy">
          <strong>{{ displayName }}</strong>
          <span>{{ roleLabel }}</span>
        </div>
      </div>
    </div>

    <div
      v-if="pageError"
      class="alert error-alert"
      role="alert"
    >
      {{ pageError }}
    </div>

    <div
      v-if="loadingProfile"
      class="loading-card"
    >
      <span class="loader"></span>
      <p>Memuat profil...</p>
    </div>

    <template v-else>
      <section class="settings-grid">
        <article class="settings-card profile-card">
          <div class="card-heading">
            <div>
              <p class="section-kicker">Informasi Profil</p>
              <h2>Edit Profil</h2>
              <p>
                Perubahan nama dan foto profil akan langsung tampil pada
                header setelah berhasil disimpan.
              </p>
            </div>
          </div>

          <div
            v-if="profileSuccess"
            class="alert success-alert"
            role="status"
          >
            {{ profileSuccess }}
          </div>

          <div
            v-if="profileError"
            class="alert error-alert"
            role="alert"
          >
            {{ profileError }}
          </div>

          <form
            class="profile-form"
            @submit.prevent="saveProfile"
          >
            <div class="form-grid">
              <div class="form-group full-width">
                <label for="fullName">Nama Lengkap</label>

                <input
                  id="fullName"
                  v-model.trim="profileForm.full_name"
                  type="text"
                  maxlength="255"
                  autocomplete="name"
                  :class="{ 'input-error': profileErrors.full_name }"
                  required
                  @input="clearProfileFeedback"
                />

                <small
                  v-if="profileErrors.full_name"
                  class="field-error"
                >
                  {{ profileErrors.full_name }}
                </small>
              </div>

              <div class="form-group">
                <label for="username">Username</label>

                <input
                  id="username"
                  v-model.trim="profileForm.username"
                  type="text"
                  maxlength="255"
                  autocomplete="username"
                  :class="{ 'input-error': profileErrors.username }"
                  required
                  @input="clearProfileFeedback"
                />

                <small
                  v-if="profileErrors.username"
                  class="field-error"
                >
                  {{ profileErrors.username }}
                </small>

                <small v-else>
                  Username harus unik dan belum digunakan akun lain.
                </small>
              </div>

              <div class="form-group">
                <label for="email">Email</label>

                <input
                  id="email"
                  v-model.trim="profileForm.email"
                  type="email"
                  maxlength="255"
                  autocomplete="email"
                  :class="{ 'input-error': profileErrors.email }"
                  placeholder="Belum diisi"
                  @input="clearProfileFeedback"
                />

                <small
                  v-if="profileErrors.email"
                  class="field-error"
                >
                  {{ profileErrors.email }}
                </small>
              </div>

              <div class="form-group">
                <label for="phone">Nomor Telepon</label>

                <input
                  id="phone"
                  v-model.trim="profileForm.phone"
                  type="tel"
                  maxlength="20"
                  autocomplete="tel"
                  inputmode="tel"
                  :class="{ 'input-error': profileErrors.phone }"
                  placeholder="Belum diisi"
                  @input="clearProfileFeedback"
                />

                <small
                  v-if="profileErrors.phone"
                  class="field-error"
                >
                  {{ profileErrors.phone }}
                </small>
              </div>

              <div class="form-group readonly-group">
                <label>Role</label>
                <div class="readonly-value">{{ roleLabel }}</div>
              </div>

              <div class="form-group readonly-group">
                <label>Kelas</label>
                <div class="readonly-value">{{ classLabel }}</div>
              </div>
            </div>

            <div class="form-actions">
              <button
                type="button"
                class="secondary-button"
                :disabled="savingProfile || !isProfileDirty"
                @click="resetProfileForm"
              >
                Batal
              </button>

              <button
                type="submit"
                class="primary-button"
                :disabled="savingProfile || !isProfileDirty"
              >
                <span
                  v-if="savingProfile"
                  class="small-loader"
                ></span>

                {{
                  savingProfile
                    ? 'Menyimpan...'
                    : 'Simpan Perubahan'
                }}
              </button>
            </div>
          </form>
        </article>

        <div class="settings-side">
          <article class="settings-card security-card">
            <div class="card-heading">
              <div>
                <p class="section-kicker">Keamanan</p>
                <h2>Ganti Password</h2>
                <p>
                  Gunakan minimal 8 karakter dan jangan membagikan password
                  kepada pengguna lain.
                </p>
              </div>
            </div>

            <div
              v-if="passwordSuccess"
              class="alert success-alert"
              role="status"
            >
              {{ passwordSuccess }}
            </div>

            <div
              v-if="passwordError"
              class="alert error-alert"
              role="alert"
            >
              {{ passwordError }}
            </div>

            <form
              class="password-form"
              @submit.prevent="changePassword"
            >
              <div class="form-group">
                <label for="currentPassword">
                  Password Saat Ini
                </label>

                <div class="password-field">
                  <input
                    id="currentPassword"
                    v-model="passwordForm.current_password"
                    :type="showCurrentPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    :class="{
                      'input-error': passwordErrors.current_password,
                    }"
                    required
                    @input="clearPasswordFeedback"
                  />

                  <button
                    type="button"
                    class="password-toggle"
                    :aria-label="
                      showCurrentPassword
                        ? 'Sembunyikan password saat ini'
                        : 'Tampilkan password saat ini'
                    "
                    @click="showCurrentPassword = !showCurrentPassword"
                  >
                    {{ showCurrentPassword ? 'Sembunyikan' : 'Lihat' }}
                  </button>
                </div>

                <small
                  v-if="passwordErrors.current_password"
                  class="field-error"
                >
                  {{ passwordErrors.current_password }}
                </small>
              </div>

              <div class="form-group">
                <label for="newPassword">Password Baru</label>

                <div class="password-field">
                  <input
                    id="newPassword"
                    v-model="passwordForm.new_password"
                    :type="showNewPassword ? 'text' : 'password'"
                    minlength="8"
                    autocomplete="new-password"
                    :class="{ 'input-error': passwordErrors.new_password }"
                    required
                    @input="clearPasswordFeedback"
                  />

                  <button
                    type="button"
                    class="password-toggle"
                    :aria-label="
                      showNewPassword
                        ? 'Sembunyikan password baru'
                        : 'Tampilkan password baru'
                    "
                    @click="showNewPassword = !showNewPassword"
                  >
                    {{ showNewPassword ? 'Sembunyikan' : 'Lihat' }}
                  </button>
                </div>

                <small
                  v-if="passwordErrors.new_password"
                  class="field-error"
                >
                  {{ passwordErrors.new_password }}
                </small>
              </div>

              <div class="form-group">
                <label for="confirmPassword">
                  Konfirmasi Password Baru
                </label>

                <div class="password-field">
                  <input
                    id="confirmPassword"
                    v-model="passwordForm.new_password_confirmation"
                    :type="
                      showPasswordConfirmation
                        ? 'text'
                        : 'password'
                    "
                    minlength="8"
                    autocomplete="new-password"
                    :class="{
                      'input-error':
                        passwordErrors.new_password_confirmation,
                    }"
                    required
                    @input="clearPasswordFeedback"
                  />

                  <button
                    type="button"
                    class="password-toggle"
                    :aria-label="
                      showPasswordConfirmation
                        ? 'Sembunyikan konfirmasi password'
                        : 'Tampilkan konfirmasi password'
                    "
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

                <small
                  v-if="passwordErrors.new_password_confirmation"
                  class="field-error"
                >
                  {{ passwordErrors.new_password_confirmation }}
                </small>
              </div>

              <button
                type="submit"
                class="primary-button password-button"
                :disabled="savingPassword"
              >
                <span
                  v-if="savingPassword"
                  class="small-loader"
                ></span>

                {{
                  savingPassword
                    ? 'Memproses...'
                    : 'Perbarui Password'
                }}
              </button>
            </form>
          </article>

          <article class="settings-card session-card">
            <div class="card-heading">
              <div>
                <p class="section-kicker danger-kicker">Sesi Akun</p>
                <h2>Keluar dari Akun</h2>
                <p>
                  Kamu sedang masuk sebagai
                  <strong>{{ accountIdentity }}</strong>.
                  Keluar jika perangkat ini digunakan bersama orang lain.
                </p>
              </div>
            </div>

            <button
              type="button"
              class="logout-button"
              @click="openLogoutModal"
            >
              Logout
            </button>
          </article>
        </div>
      </section>
    </template>

    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="logoutModalOpen"
          class="logout-overlay"
          role="dialog"
          aria-modal="true"
          aria-labelledby="profile-logout-title"
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

            <h2 id="profile-logout-title">
              Keluar dari akun?
            </h2>

            <p>
              Kamu perlu login kembali untuk mengakses aplikasi dan melihat
              jurnal kebiasaan.
            </p>

            <div
              v-if="logoutError"
              class="alert error-alert logout-error"
            >
              {{ logoutError }}
            </div>

            <div class="logout-actions">
              <button
                type="button"
                class="secondary-button"
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
                  class="small-loader"
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
  </section>
</template>

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

import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const auth = useAuthStore()

const loadingProfile = ref(true)
const savingProfile = ref(false)
const savingPassword = ref(false)
const loggingOut = ref(false)

const pageError = ref('')
const profileError = ref('')
const profileSuccess = ref('')
const profileErrors = ref({})
const passwordError = ref('')
const passwordSuccess = ref('')
const passwordErrors = ref({})
const logoutError = ref('')

const logoutModalOpen = ref(false)
const avatarLoadError = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showPasswordConfirmation = ref(false)

const loadedProfile = ref(null)

const profileForm = reactive({
  full_name: '',
  username: '',
  email: '',
  phone: '',
})

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

const displayName = computed(() => {
  return (
    auth.user?.display_name ??
    auth.user?.full_name ??
    auth.user?.name ??
    auth.user?.username ??
    'Pengguna'
  )
})

const avatarUrl = computed(() => {
  return String(auth.user?.avatar_url ?? '').trim()
})

const initials = computed(() => {
  const words = displayName.value
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!words.length) {
    return 'U'
  }

  if (words.length === 1) {
    return words[0].slice(0, 2).toUpperCase()
  }

  return `${words[0][0]}${words.at(-1)[0]}`.toUpperCase()
})

const normalizedRole = computed(() => {
  const sourceRole =
    loadedProfile.value?.role ??
    auth.user?.role ??
    auth.role ??
    'pengguna'

  const role =
    typeof sourceRole === 'object'
      ? sourceRole.name ??
        sourceRole.code ??
        sourceRole.slug ??
        ''
      : sourceRole

  return String(role)
    .trim()
    .toLowerCase()
    .replace(/[\s-]+/g, '_')
})

const roleLabel = computed(() => {
  const labels = {
    admin: 'Admin',
    siswa: 'Siswa',
    student: 'Siswa',
    guru: 'Guru',
    teacher: 'Guru',
    orang_tua: 'Orang Tua',
    orangtua: 'Orang Tua',
    parent: 'Orang Tua',
  }

  return labels[normalizedRole.value] ?? 'Pengguna'
})

const classLabel = computed(() => {
  const classData =
    loadedProfile.value?.class ??
    loadedProfile.value?.class_room ??
    loadedProfile.value?.classRoom ??
    auth.user?.class ??
    auth.user?.class_room ??
    auth.user?.classRoom

  if (!classData) {
    return normalizedRole.value === 'orang_tua' ||
      normalizedRole.value === 'orangtua' ||
      normalizedRole.value === 'parent'
      ? 'Tidak berlaku'
      : 'Tidak terikat kelas'
  }

  const grade = String(classData.grade_level ?? '').trim()
  const name = String(classData.name ?? '').trim()

  if (
    name &&
    grade &&
    name.toUpperCase().startsWith(grade.toUpperCase())
  ) {
    return name
  }

  return (
    [grade, name].filter(Boolean).join(' - ') ||
    'Tidak terikat kelas'
  )
})

const accountIdentity = computed(() => {
  return (
    auth.user?.username ??
    auth.user?.email ??
    roleLabel.value
  )
})

const isProfileDirty = computed(() => {
  const profile =
    loadedProfile.value ??
    auth.user ??
    {}

  return (
    profileForm.full_name !== stringValue(
      profile.full_name ?? profile.name,
    ) ||
    profileForm.username !== stringValue(profile.username) ||
    profileForm.email !== stringValue(profile.email) ||
    profileForm.phone !== stringValue(profile.phone)
  )
})

onMounted(async () => {
  document.addEventListener('keydown', handleKeydown)
  await loadProfile()
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

watch(avatarUrl, () => {
  avatarLoadError.value = false
})

watch(logoutModalOpen, (isOpen) => {
  document.body.style.overflow = isOpen ? 'hidden' : ''
})

function stringValue(value) {
  return value === null || value === undefined
    ? ''
    : String(value)
}

async function loadProfile() {
  try {
    loadingProfile.value = true
    pageError.value = ''

    const profile = await auth.fetchProfile()

    loadedProfile.value = profile ?? auth.user ?? {}
    fillProfileForm(loadedProfile.value)
  } catch (error) {
    pageError.value = extractApiError(
      error,
      'Gagal memuat data profil.',
    )
  } finally {
    loadingProfile.value = false
  }
}

function fillProfileForm(profile = {}) {
  profileForm.full_name = stringValue(
    profile.full_name ?? profile.name,
  )
  profileForm.username = stringValue(profile.username)
  profileForm.email = stringValue(profile.email)
  profileForm.phone = stringValue(profile.phone)
}

function clearProfileFeedback() {
  profileError.value = ''
  profileSuccess.value = ''
  profileErrors.value = {}
}

function clearPasswordFeedback() {
  passwordError.value = ''
  passwordSuccess.value = ''
  passwordErrors.value = {}
}

function resetProfileForm() {
  clearProfileFeedback()

  fillProfileForm(
    loadedProfile.value ??
    auth.user ??
    {},
  )
}

async function saveProfile() {
  if (
    savingProfile.value ||
    !isProfileDirty.value
  ) {
    return
  }

  clearProfileFeedback()

  const fullName = profileForm.full_name.trim()
  const username = profileForm.username.trim()
  const email = profileForm.email.trim()
  const phone = profileForm.phone.trim()

  const localErrors = {}

  if (!fullName) {
    localErrors.full_name = 'Nama lengkap wajib diisi.'
  }

  if (!username) {
    localErrors.username = 'Username wajib diisi.'
  }

  if (
    email &&
    !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
  ) {
    localErrors.email = 'Format email tidak valid.'
  }

  if (phone.length > 20) {
    localErrors.phone =
      'Nomor telepon maksimal 20 karakter.'
  }

  if (Object.keys(localErrors).length) {
    profileErrors.value = localErrors
    profileError.value =
      'Periksa kembali data profil yang diisi.'
    return
  }

  savingProfile.value = true

  const payload = {
    full_name: fullName,
    username,
    phone: phone || null,
  }

  if (email) {
    payload.email = email
  }

  try {
    const result = await auth.updateProfile(payload)

    loadedProfile.value =
      result?.user ??
      auth.user ??
      loadedProfile.value

    fillProfileForm(loadedProfile.value)

    profileSuccess.value =
      result?.message ??
      'Profil berhasil diperbarui.'
  } catch (error) {
    profileErrors.value =
      extractValidationErrors(error)

    profileError.value =
      Object.values(profileErrors.value)[0] ??
      extractApiError(
        error,
        'Gagal memperbarui profil.',
      )
  } finally {
    savingProfile.value = false
  }
}

async function changePassword() {
  if (savingPassword.value) {
    return
  }

  clearPasswordFeedback()

  const currentPassword =
    passwordForm.current_password.trim()

  const newPassword =
    passwordForm.new_password.trim()

  const confirmation =
    passwordForm.new_password_confirmation.trim()

  const localErrors = {}

  if (!currentPassword) {
    localErrors.current_password =
      'Password saat ini wajib diisi.'
  }

  if (!newPassword) {
    localErrors.new_password =
      'Password baru wajib diisi.'
  } else if (newPassword.length < 8) {
    localErrors.new_password =
      'Password baru minimal 8 karakter.'
  }

  if (!confirmation) {
    localErrors.new_password_confirmation =
      'Konfirmasi password baru wajib diisi.'
  } else if (newPassword !== confirmation) {
    localErrors.new_password_confirmation =
      'Konfirmasi password baru tidak sama.'
  }

  if (Object.keys(localErrors).length) {
    passwordErrors.value = localErrors
    passwordError.value =
      'Periksa kembali data password yang diisi.'
    return
  }

  savingPassword.value = true

  try {
    const result = await auth.updatePassword({
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirmation: confirmation,
    })

    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirmation = ''

    showCurrentPassword.value = false
    showNewPassword.value = false
    showPasswordConfirmation.value = false

    passwordSuccess.value =
      result?.message ??
      'Password berhasil diperbarui.'
  } catch (error) {
    passwordErrors.value =
      extractValidationErrors(error)

    passwordError.value =
      Object.values(passwordErrors.value)[0] ??
      extractApiError(
        error,
        'Gagal memperbarui password.',
      )
  } finally {
    savingPassword.value = false
  }
}

function openLogoutModal() {
  logoutError.value = ''
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
    await auth.logout()
    logoutModalOpen.value = false
    await router.replace('/login')
  } catch (error) {
    logoutError.value = extractApiError(
      error,
      'Gagal keluar dari akun. Silakan coba kembali.',
    )
  } finally {
    loggingOut.value = false
  }
}

function handleKeydown(event) {
  if (
    event.key === 'Escape' &&
    logoutModalOpen.value &&
    !loggingOut.value
  ) {
    closeLogoutModal()
  }
}

function extractValidationErrors(error) {
  const errors = error.response?.data?.errors

  if (!errors || typeof errors !== 'object') {
    return {}
  }

  const aliases = {
    password: 'new_password',
    password_confirmation:
      'new_password_confirmation',
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => {
      const normalizedField = aliases[field] ?? field
      let message = Array.isArray(messages)
        ? messages[0]
        : String(messages)

      if (
        normalizedField === 'username' &&
        /taken|digunakan|terdaftar|unique/i.test(message)
      ) {
        message =
          'Username sudah digunakan. Silakan pilih username lain.'
      }

      if (
        normalizedField === 'email' &&
        /taken|digunakan|terdaftar|unique/i.test(message)
      ) {
        message =
          'Email sudah digunakan oleh akun lain.'
      }

      return [normalizedField, message]
    }),
  )
}

function extractApiError(error, fallback) {
  const response = error.response?.data
  const errors = response?.errors

  if (errors && typeof errors === 'object') {
    return Object.values(errors)
      .flat()
      .filter(Boolean)
      .join(' ')
  }

  return response?.message ?? fallback
}
</script>

<style scoped>
.settings-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 18px 0 34px;
}

.page-banner {
  min-height: 165px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 26px;
  padding: 30px 32px;
  border-radius: 22px;
  background:
    radial-gradient(
      circle at 91% 8%,
      rgba(255, 255, 255, 0.2),
      transparent 28%
    ),
    linear-gradient(135deg, #1d9bf0, #1686d1);
  color: #ffffff;
  box-shadow: 0 16px 36px rgba(29, 155, 240, 0.18);
}

.banner-kicker,
.section-kicker {
  margin: 0 0 7px;
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
}

.banner-kicker {
  color: rgba(255, 255, 255, 0.72);
}

.page-banner h1 {
  margin: 0;
  font-size: clamp(24px, 3vw, 32px);
  font-weight: 900;
  letter-spacing: -0.035em;
}

.page-banner > div:first-child > p:last-child {
  max-width: 650px;
  margin: 10px 0 0;
  color: rgba(255, 255, 255, 0.84);
  font-size: 13px;
  line-height: 1.7;
}

.profile-summary {
  min-width: 210px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.13);
}

.summary-avatar {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  overflow: hidden;
  border-radius: 15px;
  background: #ffffff;
  color: #1686d1;
  font-size: 14px;
  font-weight: 900;
}

.summary-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.summary-copy {
  min-width: 0;
}

.profile-summary strong,
.profile-summary .summary-copy > span {
  display: block;
}

.profile-summary strong {
  overflow: hidden;
  font-size: 14px;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.profile-summary .summary-copy > span {
  margin-top: 4px;
  color: rgba(255, 255, 255, 0.78);
  font-size: 11px;
  font-weight: 700;
}

.settings-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 1.45fr)
    minmax(320px, 0.8fr);
  gap: 22px;
  align-items: start;
}

.settings-side {
  display: flex;
  flex-direction: column;
  gap: 22px;
}

.settings-card,
.loading-card {
  border: 1px solid #e4edf6;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 9px 28px rgba(15, 23, 42, 0.045);
}

.settings-card {
  padding: 26px;
}

.loading-card {
  min-height: 250px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #64748b;
}

.loading-card p {
  margin: 0;
  font-size: 12px;
}

.card-heading {
  margin-bottom: 20px;
}

.section-kicker {
  color: #209cee;
}

.danger-kicker {
  color: #dc2626;
}

.card-heading h2 {
  margin: 0;
  color: #0f172a;
  font-size: 20px;
  font-weight: 900;
}

.card-heading p:last-child {
  margin: 7px 0 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.65;
}

.card-heading strong {
  color: #334155;
}

.profile-form,
.password-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 15px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  color: #475569;
  font-size: 11px;
  font-weight: 800;
}

.form-group input,
.readonly-value {
  width: 100%;
  min-height: 45px;
  border: 1px solid #dce6ef;
  border-radius: 11px;
  padding: 0 12px;
  background: #fbfdff;
  color: #1e293b;
  font: inherit;
  font-size: 12px;
  outline: none;
  box-sizing: border-box;
  transition:
    border-color 0.18s ease,
    box-shadow 0.18s ease,
    background 0.18s ease;
}

.form-group input:focus {
  border-color: #209cee;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(32, 156, 238, 0.1);
}

.form-group input.input-error {
  border-color: #fda4af;
  background: #fffafb;
}

.form-group input.input-error:focus {
  border-color: #f43f5e;
  box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.1);
}

.readonly-value {
  display: flex;
  align-items: center;
  background: #f1f5f9;
  color: #64748b;
}

.form-group small {
  color: #94a3b8;
  font-size: 9.5px;
  line-height: 1.5;
}

.form-group .field-error {
  color: #dc2626;
  font-weight: 700;
}

.password-field {
  position: relative;
}

.password-field input {
  padding-right: 92px;
}

.password-toggle {
  position: absolute;
  top: 0;
  right: 4px;
  bottom: 0;
  border: 0;
  padding: 0 8px;
  background: transparent;
  color: #1686d1;
  font: inherit;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

.password-toggle:hover {
  color: #0369a1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 4px;
}

.primary-button,
.secondary-button,
.confirm-logout-button,
.logout-button {
  min-height: 43px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 17px;
  border-radius: 11px;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
  transition:
    background 0.18s ease,
    border-color 0.18s ease,
    color 0.18s ease,
    opacity 0.18s ease;
}

.primary-button {
  border: 0;
  background: #209cee;
  color: #ffffff;
}

.primary-button:hover:not(:disabled) {
  background: #1686d1;
}

.secondary-button {
  border: 1px solid #dce6ef;
  background: #ffffff;
  color: #475569;
}

.secondary-button:hover:not(:disabled) {
  background: #f8fafc;
}

.primary-button:disabled,
.secondary-button:disabled,
.confirm-logout-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.password-button,
.logout-button {
  width: 100%;
  margin-top: 3px;
}

.logout-button {
  border: 1px solid #fecaca;
  background: #fff1f2;
  color: #dc2626;
}

.logout-button:hover {
  border-color: #fca5a5;
  background: #fee2e2;
}

.alert {
  margin-bottom: 15px;
  padding: 12px 14px;
  border-radius: 12px;
  font-size: 11.5px;
  font-weight: 700;
  line-height: 1.55;
}

.error-alert {
  border: 1px solid #fecdd3;
  background: #fff1f2;
  color: #be123c;
}

.success-alert {
  border: 1px solid #bbf7d0;
  background: #ecfdf5;
  color: #047857;
}

.loader,
.small-loader {
  display: inline-block;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

.loader {
  width: 25px;
  height: 25px;
  border: 3px solid #dbeafe;
  border-top-color: #209cee;
}

.small-loader {
  width: 13px;
  height: 13px;
  border: 2px solid rgba(255, 255, 255, 0.45);
  border-top-color: #ffffff;
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
  margin-bottom: 0;
}

.logout-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
}

.confirm-logout-button {
  border: 0;
  background: #dc2626;
  color: #ffffff;
}

.confirm-logout-button:hover:not(:disabled) {
  background: #b91c1c;
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

@media (max-width: 1040px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }

  .settings-side {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .settings-page {
    padding-top: 12px;
  }

  .page-banner {
    min-height: 0;
    align-items: flex-start;
    flex-direction: column;
    padding: 24px;
  }

  .profile-summary {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
  }

  .settings-side {
    display: flex;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full-width {
    grid-column: auto;
  }

  .form-actions,
  .logout-actions {
    flex-direction: column-reverse;
  }

  .primary-button,
  .secondary-button,
  .confirm-logout-button {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .settings-page {
    gap: 16px;
  }

  .page-banner,
  .settings-card {
    border-radius: 18px;
  }

  .page-banner {
    padding: 21px;
  }

  .settings-card {
    padding: 20px;
  }

  .page-banner h1 {
    font-size: 25px;
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
}
</style>

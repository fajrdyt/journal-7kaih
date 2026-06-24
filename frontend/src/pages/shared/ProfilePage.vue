<template>
  <section class="settings-page">
    <div class="page-banner">
      <div>
        <p class="banner-kicker">Pengaturan Akun</p>
        <h1>Kelola Profil dan Keamanan</h1>
        <p>
          Perbarui informasi akun dan password yang digunakan untuk masuk
          ke Jurnal 7KAIH.
        </p>
      </div>

      <div class="profile-summary">
        <span class="summary-avatar">{{ initials }}</span>

        <div>
          <strong>{{ displayName }}</strong>
          <span>{{ roleLabel }}</span>
        </div>
      </div>
    </div>

    <div v-if="pageError" class="alert error-alert">
      {{ pageError }}
    </div>

    <div v-if="loadingProfile" class="loading-card">
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
                Nama yang disimpan akan langsung diperbarui pada header
                setelah berhasil disimpan.
              </p>
            </div>
          </div>

          <div v-if="profileSuccess" class="alert success-alert">
            {{ profileSuccess }}
          </div>

          <div v-if="profileError" class="alert error-alert">
            {{ profileError }}
          </div>

          <form class="profile-form" @submit.prevent="saveProfile">
            <div class="form-grid">
              <div class="form-group full-width">
                <label for="fullName">Nama Lengkap</label>
                <input
                  id="fullName"
                  v-model.trim="profileForm.full_name"
                  type="text"
                  maxlength="255"
                  autocomplete="name"
                  required
                />
              </div>

              <div class="form-group">
                <label for="username">Username</label>
                <input
                  id="username"
                  v-model.trim="profileForm.username"
                  type="text"
                  maxlength="255"
                  autocomplete="username"
                  required
                />
              </div>

              <div class="form-group">
                <label for="email">Email</label>
                <input
                  id="email"
                  v-model.trim="profileForm.email"
                  type="email"
                  maxlength="255"
                  autocomplete="email"
                  required
                />
              </div>

              <div class="form-group">
                <label for="phone">Nomor Telepon</label>
                <input
                  id="phone"
                  v-model.trim="profileForm.phone"
                  type="tel"
                  maxlength="20"
                  autocomplete="tel"
                  placeholder="Belum diisi"
                />
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
                :disabled="savingProfile"
                @click="resetProfileForm"
              >
                Reset
              </button>

              <button
                type="submit"
                class="primary-button"
                :disabled="savingProfile"
              >
                <span v-if="savingProfile" class="small-loader"></span>
                {{ savingProfile ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </form>
        </article>

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

          <div v-if="passwordSuccess" class="alert success-alert">
            {{ passwordSuccess }}
          </div>

          <div v-if="passwordError" class="alert error-alert">
            {{ passwordError }}
          </div>

          <form class="password-form" @submit.prevent="changePassword">
            <div class="form-group">
              <label for="currentPassword">Password Saat Ini</label>
              <input
                id="currentPassword"
                v-model="passwordForm.current_password"
                type="password"
                autocomplete="current-password"
                required
              />
            </div>

            <div class="form-group">
              <label for="newPassword">Password Baru</label>
              <input
                id="newPassword"
                v-model="passwordForm.new_password"
                type="password"
                minlength="8"
                autocomplete="new-password"
                required
              />
            </div>

            <div class="form-group">
              <label for="confirmPassword">Konfirmasi Password Baru</label>
              <input
                id="confirmPassword"
                v-model="passwordForm.new_password_confirmation"
                type="password"
                minlength="8"
                autocomplete="new-password"
                required
              />
            </div>

            <button
              type="submit"
              class="primary-button password-button"
              :disabled="savingPassword"
            >
              <span v-if="savingPassword" class="small-loader"></span>
              {{ savingPassword ? 'Memproses...' : 'Perbarui Password' }}
            </button>
          </form>
        </article>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'

import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()

const loadingProfile = ref(true)
const savingProfile = ref(false)
const savingPassword = ref(false)

const pageError = ref('')
const profileError = ref('')
const profileSuccess = ref('')
const passwordError = ref('')
const passwordSuccess = ref('')

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
    auth.user?.full_name ||
    auth.user?.name ||
    auth.user?.username ||
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
  const role =
    loadedProfile.value?.role?.name ||
    loadedProfile.value?.role ||
    auth.role ||
    'pengguna'

  const labels = {
    admin: 'Admin',
    siswa: 'Siswa',
    guru: 'Guru',
    orang_tua: 'Orang Tua',
  }

  return labels[role] || role
})

const classLabel = computed(() => {
  const classData =
    loadedProfile.value?.class ||
    loadedProfile.value?.class_room ||
    auth.user?.class ||
    auth.user?.class_room

  if (!classData) {
    return 'Tidak terikat kelas'
  }

  const grade = String(classData.grade_level || '').trim()
  const name = String(classData.name || '').trim()

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

onMounted(async () => {
  await loadProfile()
})

async function loadProfile() {
  try {
    loadingProfile.value = true
    pageError.value = ''

    const profile = await auth.fetchProfile()

    loadedProfile.value = profile || auth.user || {}
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
  profileForm.full_name =
    profile.full_name ||
    profile.name ||
    ''

  profileForm.username = profile.username || ''
  profileForm.email = profile.email || ''
  profileForm.phone = profile.phone || ''
}

function resetProfileForm() {
  profileError.value = ''
  profileSuccess.value = ''

  fillProfileForm(
    loadedProfile.value ||
    auth.user ||
    {},
  )
}

async function saveProfile() {
  try {
    savingProfile.value = true
    profileError.value = ''
    profileSuccess.value = ''

    const payload = {
      full_name: profileForm.full_name.trim(),
      username: profileForm.username.trim(),
      email: profileForm.email.trim(),
      phone: profileForm.phone.trim() || null,
    }

    const result = await auth.updateProfile(payload)

    loadedProfile.value = result?.user || auth.user
    fillProfileForm(loadedProfile.value)

    profileSuccess.value =
      result?.message ||
      'Profil berhasil diperbarui.'
  } catch (error) {
    profileError.value = extractApiError(
      error,
      'Gagal memperbarui profil.',
    )
  } finally {
    savingProfile.value = false
  }
}

async function changePassword() {
  try {
    savingPassword.value = true
    passwordError.value = ''
    passwordSuccess.value = ''

    const currentPassword =
      passwordForm.current_password.trim()

    const newPassword =
      passwordForm.new_password.trim()

    const confirmation =
      passwordForm.new_password_confirmation.trim()

    if (!currentPassword) {
      passwordError.value =
        'Password saat ini wajib diisi.'
      return
    }

    if (newPassword.length < 8) {
      passwordError.value =
        'Password baru minimal 8 karakter.'
      return
    }

    if (newPassword !== confirmation) {
      passwordError.value =
        'Konfirmasi password baru tidak sama.'
      return
    }

    const result = await auth.updatePassword({
      current_password: currentPassword,
      new_password: newPassword,
      new_password_confirmation: confirmation,
    })

    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirmation = ''

    passwordSuccess.value =
      result?.message ||
      'Password berhasil diperbarui.'
  } catch (error) {
    passwordError.value = extractApiError(
      error,
      'Gagal memperbarui password.',
    )
  } finally {
    savingPassword.value = false
  }
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

  return response?.message || fallback
}
</script>

<style scoped>
.settings-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-bottom: 30px;
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
  color: rgba(255, 255, 255, 0.7);
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
  color: rgba(255, 255, 255, 0.82);
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
  border-radius: 15px;
  background: #ffffff;
  color: #1686d1;
  font-size: 14px;
  font-weight: 900;
}

.profile-summary strong,
.profile-summary span:last-child {
  display: block;
}

.profile-summary strong {
  font-size: 14px;
  font-weight: 900;
}

.profile-summary span:last-child {
  margin-top: 4px;
  color: rgba(255, 255, 255, 0.78);
  font-size: 11px;
  font-weight: 700;
}

.settings-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(300px, 0.8fr);
  gap: 18px;
  align-items: start;
}

.settings-card,
.loading-card {
  border: 1px solid #e4edf6;
  border-radius: 20px;
  background: #ffffff;
  box-shadow: 0 9px 28px rgba(15, 23, 42, 0.045);
}

.settings-card {
  padding: 24px;
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
  min-height: 43px;
  border: 1px solid #dce6ef;
  border-radius: 11px;
  padding: 0 12px;
  background: #fbfdff;
  color: #1e293b;
  font: inherit;
  font-size: 12px;
  outline: none;
  box-sizing: border-box;
}

.form-group input:focus {
  border-color: #209cee;
  box-shadow: 0 0 0 4px rgba(32, 156, 238, 0.1);
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
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 4px;
}

.primary-button,
.secondary-button {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 16px;
  border-radius: 11px;
  font: inherit;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.primary-button {
  border: 0;
  background: #209cee;
  color: #ffffff;
}

.secondary-button {
  border: 1px solid #dce6ef;
  background: #ffffff;
  color: #475569;
}

.primary-button:disabled,
.secondary-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.password-button {
  width: 100%;
  margin-top: 3px;
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

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 960px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .page-banner {
    align-items: flex-start;
    flex-direction: column;
    padding: 24px;
  }

  .profile-summary {
    width: 100%;
    box-sizing: border-box;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-group.full-width {
    grid-column: auto;
  }

  .form-actions {
    flex-direction: column-reverse;
  }

  .primary-button,
  .secondary-button {
    width: 100%;
  }
}
</style>

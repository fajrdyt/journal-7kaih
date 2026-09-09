<template>
  <section class="settings-page">
    <div class="page-banner hero-gradient">
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

    <div v-if="pageError" class="alert error-alert" role="alert">
      {{ pageError }}
    </div>

    <div v-if="loadingProfile" class="loading-card">
      <span class="loader"></span>
      <p>Memuat profil...</p>
    </div>

    <template v-else>
      <section class="settings-grid">
        <article class="settings-card profile-card">
          <AvatarUploadCard />

          <div class="card-heading" style="margin-top: 22px">
            <div>
              <p class="section-kicker">Informasi Profil</p>
              <h2>Edit Profil</h2>
              <p>
                Perubahan nama dan foto profil akan langsung tampil pada
                header setelah berhasil disimpan.
              </p>
            </div>
          </div>

          <div v-if="profileSuccess" class="alert success-alert" role="status">
            {{ profileSuccess }}
          </div>

          <div v-if="profileError" class="alert error-alert" role="alert">
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
                  :class="{ 'input-error': profileErrors.full_name }"
                  required
                  @input="clearProfileFeedback"
                />

                <small v-if="profileErrors.full_name" class="field-error">
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

                <small v-if="profileErrors.username" class="field-error">
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

                <small v-if="profileErrors.email" class="field-error">
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

                <small v-if="profileErrors.phone" class="field-error">
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
                <span v-if="savingProfile" class="small-loader"></span>
                {{ savingProfile ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </div>
          </form>
        </article>

        <div class="settings-side">
          <ChangePasswordCard />
          <LogoutSessionCard :identity-label="accountIdentity" />
        </div>
      </section>
    </template>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import AvatarUploadCard from '@/components/settings/AvatarUploadCard.vue'
import ChangePasswordCard from '@/components/settings/ChangePasswordCard.vue'
import LogoutSessionCard from '@/components/settings/LogoutSessionCard.vue'

const auth = useAuthStore()

const loadingProfile = ref(true)
const savingProfile = ref(false)

const pageError = ref('')
const profileError = ref('')
const profileSuccess = ref('')
const profileErrors = ref({})

const avatarLoadError = ref(false)
const loadedProfile = ref(null)

const profileForm = reactive({
  full_name: '',
  username: '',
  email: '',
  phone: '',
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
  const words = displayName.value.trim().split(/\s+/).filter(Boolean)
  if (!words.length) return 'U'
  if (words.length === 1) return words[0].slice(0, 2).toUpperCase()
  return `${words[0][0]}${words.at(-1)[0]}`.toUpperCase()
})

const normalizedRole = computed(() => {
  const sourceRole = loadedProfile.value?.role ?? auth.user?.role ?? auth.role ?? 'pengguna'

  const role =
    typeof sourceRole === 'object'
      ? sourceRole.name ?? sourceRole.code ?? sourceRole.slug ?? ''
      : sourceRole

  return String(role).trim().toLowerCase().replace(/[\s-]+/g, '_')
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
    return ['orang_tua', 'orangtua', 'parent'].includes(normalizedRole.value)
      ? 'Tidak berlaku'
      : 'Tidak terikat kelas'
  }

  const grade = String(classData.grade_level ?? '').trim()
  const name = String(classData.name ?? '').trim()

  if (name && grade && name.toUpperCase().startsWith(grade.toUpperCase())) {
    return name
  }

  return [grade, name].filter(Boolean).join(' - ') || 'Tidak terikat kelas'
})

const accountIdentity = computed(() => {
  return auth.user?.username ?? auth.user?.email ?? roleLabel.value
})

const isProfileDirty = computed(() => {
  const profile = loadedProfile.value ?? auth.user ?? {}

  return (
    profileForm.full_name !== stringValue(profile.full_name ?? profile.name) ||
    profileForm.username !== stringValue(profile.username) ||
    profileForm.email !== stringValue(profile.email) ||
    profileForm.phone !== stringValue(profile.phone)
  )
})

onMounted(async () => {
  await loadProfile()
})

watch(avatarUrl, () => {
  avatarLoadError.value = false
})

function stringValue(value) {
  return value === null || value === undefined ? '' : String(value)
}

async function loadProfile() {
  try {
    loadingProfile.value = true
    pageError.value = ''

    const profile = await auth.fetchProfile()
    loadedProfile.value = profile ?? auth.user ?? {}
    fillProfileForm(loadedProfile.value)
  } catch (error) {
    pageError.value = extractApiError(error, 'Gagal memuat data profil.')
  } finally {
    loadingProfile.value = false
  }
}

function fillProfileForm(profile = {}) {
  profileForm.full_name = stringValue(profile.full_name ?? profile.name)
  profileForm.username = stringValue(profile.username)
  profileForm.email = stringValue(profile.email)
  profileForm.phone = stringValue(profile.phone)
}

function clearProfileFeedback() {
  profileError.value = ''
  profileSuccess.value = ''
  profileErrors.value = {}
}

function resetProfileForm() {
  clearProfileFeedback()
  fillProfileForm(loadedProfile.value ?? auth.user ?? {})
}

async function saveProfile() {
  if (savingProfile.value || !isProfileDirty.value) return

  clearProfileFeedback()

  const fullName = profileForm.full_name.trim()
  const username = profileForm.username.trim()
  const email = profileForm.email.trim()
  const phone = profileForm.phone.trim()

  const localErrors = {}

  if (!fullName) localErrors.full_name = 'Nama lengkap wajib diisi.'
  if (!username) localErrors.username = 'Username wajib diisi.'
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    localErrors.email = 'Format email tidak valid.'
  }
  if (phone.length > 20) localErrors.phone = 'Nomor telepon maksimal 20 karakter.'

  if (Object.keys(localErrors).length) {
    profileErrors.value = localErrors
    profileError.value = 'Periksa kembali data profil yang diisi.'
    return
  }

  savingProfile.value = true

  const payload = { full_name: fullName, username, phone: phone || null }
  if (email) payload.email = email

  try {
    const result = await auth.updateProfile(payload)
    loadedProfile.value = result?.user ?? auth.user ?? loadedProfile.value
    fillProfileForm(loadedProfile.value)
    profileSuccess.value = result?.message ?? 'Profil berhasil diperbarui.'
  } catch (error) {
    profileErrors.value = extractValidationErrors(error)
    profileError.value =
      Object.values(profileErrors.value)[0] ?? extractApiError(error, 'Gagal memperbarui profil.')
  } finally {
    savingProfile.value = false
  }
}

function extractValidationErrors(error) {
  const errors = error.response?.data?.errors
  if (!errors || typeof errors !== 'object') return {}

  const aliases = {
    password: 'new_password',
    password_confirmation: 'new_password_confirmation',
  }

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => {
      const normalizedField = aliases[field] ?? field
      let message = Array.isArray(messages) ? messages[0] : String(messages)

      if (normalizedField === 'username' && /taken|digunakan|terdaftar|unique/i.test(message)) {
        message = 'Username sudah digunakan. Silakan pilih username lain.'
      }

      if (normalizedField === 'email' && /taken|digunakan|terdaftar|unique/i.test(message)) {
        message = 'Email sudah digunakan oleh akun lain.'
      }

      return [normalizedField, message]
    }),
  )
}

function extractApiError(error, fallback) {
  const response = error.response?.data
  const errors = response?.errors

  if (errors && typeof errors === 'object') {
    return Object.values(errors).flat().filter(Boolean).join(' ')
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
  color: #ffffff;
  box-shadow: 0 16px 36px rgba(13, 153, 255, 0.18);
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
  color: var(--color-primary-deep);
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
  grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.8fr);
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
  color: var(--color-primary);
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

.profile-form {
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
  border-color: var(--color-primary);
  background: #ffffff;
  box-shadow: 0 0 0 4px var(--color-primary-tint);
}

.form-group input.input-error {
  border-color: #fda4af;
  background: #fffafb;
}

.form-group input.input-error:focus {
  border-color: var(--color-danger);
  box-shadow: 0 0 0 4px var(--color-danger-tint);
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
  color: var(--color-danger);
  font-weight: 700;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 4px;
}

.primary-button,
.secondary-button {
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
  background: var(--color-primary);
  color: #ffffff;
}

.primary-button:hover:not(:disabled) {
  background: var(--color-primary-hover);
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
.secondary-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
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
  border: 1px solid var(--color-danger-tint);
  background: var(--color-danger-tint);
  color: var(--color-danger);
}

.success-alert {
  border: 1px solid var(--color-success-tint);
  background: var(--color-success-tint);
  color: var(--color-success);
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
  border: 3px solid var(--color-primary-tint);
  border-top-color: var(--color-primary);
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

  .form-actions {
    flex-direction: column-reverse;
  }

  .primary-button,
  .secondary-button {
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
}
</style>

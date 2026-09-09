<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import AvatarUploadCard from '@/components/settings/AvatarUploadCard.vue'
import ChangePasswordCard from '@/components/settings/ChangePasswordCard.vue'
import LogoutSessionCard from '@/components/settings/LogoutSessionCard.vue'

const authStore = useAuthStore()

const profileSaving = ref(false)
const profileSuccess = ref('')
const profileError = ref('')
const profileErrors = ref({})

const profileForm = reactive({
  nisn: '',
  full_name: '',
  username: '',
  email: '',
  phone: '',
})

const user = computed(() => authStore.user)

const roleLabel = computed(() => {
  const sourceRole =
    typeof user.value?.role === 'object'
      ? user.value?.role?.name ?? user.value?.role?.code ?? ''
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
    profileForm.full_name !== stringValue(currentUser.full_name ?? currentUser.name) ||
    profileForm.username !== stringValue(currentUser.username) ||
    profileForm.email !== stringValue(currentUser.email) ||
    profileForm.phone !== stringValue(currentUser.phone)
  )
})

function stringValue(value) {
  return value === null || value === undefined ? '' : String(value)
}

function syncProfileForm(profile = user.value) {
  profileForm.nisn = stringValue(profile?.nisn)
  profileForm.full_name = stringValue(profile?.full_name ?? profile?.name)
  profileForm.username = stringValue(profile?.username)
  profileForm.email = stringValue(profile?.email)
  profileForm.phone = stringValue(profile?.phone)
}

function extractValidationErrors(error) {
  const errors = error.response?.data?.errors
  if (!errors || typeof errors !== 'object') return {}

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => {
      const message = Array.isArray(messages) ? messages[0] : messages
      return [field, normalizeValidationMessage(field, message)]
    }),
  )
}

function normalizeValidationMessage(field, message) {
  const normalized = String(message ?? '').trim().toLowerCase()

  const isDuplicate =
    normalized.includes('already been taken') ||
    normalized.includes('unique') ||
    normalized.includes('sudah digunakan') ||
    normalized.includes('telah digunakan')

  if (field === 'username' && isDuplicate) {
    return 'Username sudah digunakan. Silakan pilih username lain.'
  }

  if (field === 'email' && isDuplicate) {
    return 'Email sudah digunakan oleh akun lain.'
  }

  return message
}

function clearProfileFeedback() {
  profileSuccess.value = ''
  profileError.value = ''
  profileErrors.value = {}
}

function handleResetProfile() {
  clearProfileFeedback()
  syncProfileForm()
}

async function handleUpdateProfile() {
  if (profileSaving.value || !isProfileDirty.value) return

  clearProfileFeedback()

  const fullName = profileForm.full_name.trim()
  const username = profileForm.username.trim()
  const email = profileForm.email.trim()
  const phone = profileForm.phone.trim()

  const clientErrors = {}

  if (!fullName) clientErrors.full_name = 'Nama lengkap wajib diisi.'
  if (!username) clientErrors.username = 'Username wajib diisi.'
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    clientErrors.email = 'Format email belum valid.'
  }
  if (phone.length > 20) clientErrors.phone = 'Nomor telepon maksimal 20 karakter.'

  if (Object.keys(clientErrors).length) {
    profileErrors.value = clientErrors
    profileError.value = 'Periksa kembali data profil yang diisi.'
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
    profileSuccess.value = result?.message ?? 'Profil berhasil diperbarui.'
    syncProfileForm(result?.user ?? authStore.user)
  } catch (error) {
    const validationErrors = extractValidationErrors(error)
    profileErrors.value = validationErrors

    const firstValidationMessage = Object.values(validationErrors)[0]

    profileError.value =
      error.response?.status === 422
        ? firstValidationMessage ?? 'Data profil belum dapat disimpan. Periksa kembali kolom yang ditandai.'
        : error.response?.data?.message ?? authStore.error ?? 'Gagal memperbarui profil.'
  } finally {
    profileSaving.value = false
  }
}

watch(
  user,
  (profile) => {
    if (profile) syncProfileForm(profile)
  },
  { immediate: true, deep: true },
)

onMounted(async () => {
  if (!authStore.token) return

  try {
    await authStore.fetchProfile()
  } catch (error) {
    profileError.value = error.response?.data?.message ?? 'Gagal mengambil data profil.'
  }
})
</script>

<template>
  <section class="space-y-5 pb-10 pt-4 sm:space-y-6 sm:pt-5 lg:pt-6">
    <div class="rounded-[28px] bg-white px-6 py-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)] sm:px-8 sm:py-7">
      <p class="text-sm font-semibold uppercase tracking-[0.2em] text-primary">
        Pengaturan
      </p>

      <h1 class="mt-2 text-2xl font-bold text-slate-900">Pengaturan Akun</h1>

      <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
        Perbarui informasi profil, ubah password, dan kelola sesi akunmu.
      </p>
    </div>

    <div
      v-if="authStore.loading && !user"
      class="rounded-3xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm"
    >
      <div class="mx-auto h-9 w-9 animate-spin rounded-full border-4 border-primary-tint border-t-primary" />
      <p class="mt-4 text-sm font-medium text-slate-500">Memuat data profil...</p>
    </div>

    <div
      v-else
      class="grid gap-6 lg:gap-8 xl:grid-cols-[minmax(0,1.15fr)_minmax(380px,0.85fr)] xl:gap-10"
    >
      <form
        class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
        @submit.prevent="handleUpdateProfile"
      >
        <AvatarUploadCard />

        <div class="mt-6">
          <h2 class="text-lg font-bold text-slate-900">Edit Profil</h2>
          <p class="mt-1 text-sm text-slate-500">
            Pastikan informasi akunmu lengkap dan sesuai.
          </p>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
          <div>
            <label for="nisn" class="text-sm font-semibold text-slate-700">NISN</label>

            <input
              id="nisn"
              v-model="profileForm.nisn"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              readonly
              class="mt-2 w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500 outline-none transition placeholder:text-slate-400"
              placeholder="Masukkan NISN"
            />
          </div>

          <div>
            <label for="full-name" class="text-sm font-semibold text-slate-700">Nama Lengkap</label>

            <input
              id="full-name"
              v-model="profileForm.full_name"
              type="text"
              autocomplete="name"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
              :class="profileErrors.full_name ? 'border-danger' : 'border-slate-200'"
              placeholder="Masukkan nama lengkap"
              @input="clearProfileFeedback"
            />

            <p v-if="profileErrors.full_name" class="mt-2 text-xs font-medium text-danger">
              {{ profileErrors.full_name }}
            </p>
          </div>

          <div>
            <label for="username" class="text-sm font-semibold text-slate-700">Username</label>

            <input
              id="username"
              v-model="profileForm.username"
              type="text"
              autocomplete="username"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
              :class="profileErrors.username ? 'border-danger' : 'border-slate-200'"
              placeholder="Masukkan username"
              @input="clearProfileFeedback"
            />

            <p v-if="!profileErrors.username" class="mt-2 text-xs leading-5 text-slate-400">
              Username harus unik dan tidak boleh sama dengan pengguna lain.
            </p>

            <p v-else class="mt-2 text-xs font-medium text-danger">
              {{ profileErrors.username }}
            </p>
          </div>

          <div>
            <label for="email" class="text-sm font-semibold text-slate-700">Email</label>

            <input
              id="email"
              v-model="profileForm.email"
              type="email"
              autocomplete="email"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
              :class="profileErrors.email ? 'border-danger' : 'border-slate-200'"
              placeholder="Masukkan email"
              @input="clearProfileFeedback"
            />

            <p v-if="profileErrors.email" class="mt-2 text-xs font-medium text-danger">
              {{ profileErrors.email }}
            </p>
          </div>

          <div class="sm:col-span-2">
            <label for="phone" class="text-sm font-semibold text-slate-700">Nomor Telepon</label>

            <input
              id="phone"
              v-model="profileForm.phone"
              type="tel"
              inputmode="tel"
              autocomplete="tel"
              class="mt-2 w-full rounded-2xl border bg-white px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
              :class="profileErrors.phone ? 'border-danger' : 'border-slate-200'"
              placeholder="Contoh: 081234567890"
              @input="clearProfileFeedback"
            />

            <p v-if="profileErrors.phone" class="mt-2 text-xs font-medium text-danger">
              {{ profileErrors.phone }}
            </p>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kelas</p>
            <p class="mt-1 font-medium text-slate-800">{{ className }}</p>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Role</p>
            <p class="mt-1 font-medium text-slate-800">{{ roleLabel }}</p>
          </div>
        </div>

        <div
          v-if="profileSuccess"
          class="mt-6 rounded-2xl border border-success-tint bg-success-tint px-4 py-3 text-sm font-semibold text-success"
        >
          {{ profileSuccess }}
        </div>

        <div
          v-if="profileError"
          class="mt-6 rounded-2xl border border-danger-tint bg-danger-tint px-4 py-3 text-sm font-semibold text-danger"
        >
          {{ profileError }}
        </div>

        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-primary/40 hover:bg-primary-tint hover:text-primary-deep disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="profileSaving || !isProfileDirty"
            @click="handleResetProfile"
          >
            Batal
          </button>

          <button
            type="submit"
            class="rounded-2xl bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="profileSaving || !isProfileDirty"
          >
            {{ profileSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </form>

      <div class="space-y-6 lg:space-y-8">
        <ChangePasswordCard />
        <LogoutSessionCard :identity-label="studentIdentity" />
      </div>
    </div>
  </section>
</template>

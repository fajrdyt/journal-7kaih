<script setup>
import { reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'

const authStore = useAuthStore()

const passwordSaving = ref(false)
const passwordSuccess = ref('')
const passwordError = ref('')
const passwordErrors = ref({})

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showPasswordConfirmation = ref(false)

const passwordForm = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

function clearPasswordFeedback() {
  passwordSuccess.value = ''
  passwordError.value = ''
  passwordErrors.value = {}
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
      const message = Array.isArray(messages) ? messages[0] : String(messages)
      return [normalizedField, message]
    }),
  )
}

function validatePasswordForm() {
  const errors = {}

  if (!passwordForm.current_password) {
    errors.current_password = 'Password saat ini wajib diisi.'
  }

  if (!passwordForm.new_password) {
    errors.new_password = 'Password baru wajib diisi.'
  } else if (passwordForm.new_password.length < 8) {
    errors.new_password = 'Password baru minimal terdiri dari 8 karakter.'
  }

  if (!passwordForm.new_password_confirmation) {
    errors.new_password_confirmation = 'Konfirmasi password baru wajib diisi.'
  } else if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
    errors.new_password_confirmation = 'Konfirmasi password tidak sama.'
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
      new_password: passwordForm.new_password,
      new_password_confirmation: passwordForm.new_password_confirmation,
    })

    passwordSuccess.value = result?.message ?? 'Password berhasil diperbarui.'

    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirmation = ''

    showCurrentPassword.value = false
    showNewPassword.value = false
    showPasswordConfirmation.value = false
  } catch (error) {
    passwordErrors.value = extractValidationErrors(error)

    passwordError.value =
      error.response?.data?.message ?? authStore.error ?? 'Gagal memperbarui password.'
  } finally {
    passwordSaving.value = false
  }
}
</script>

<template>
  <form
    class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
    @submit.prevent="handleUpdatePassword"
  >
    <h2 class="text-lg font-bold text-slate-900">Ubah Password</h2>

    <p class="mt-1 text-sm leading-6 text-slate-500">
      Gunakan password yang kuat dan tidak mudah ditebak.
    </p>

    <div class="mt-6 space-y-5">
      <div>
        <label for="current-password" class="text-sm font-semibold text-slate-700">
          Password Saat Ini
        </label>

        <div class="relative mt-2">
          <input
            id="current-password"
            v-model="passwordForm.current_password"
            :type="showCurrentPassword ? 'text' : 'password'"
            autocomplete="current-password"
            class="w-full rounded-2xl border bg-white px-4 py-3 pr-24 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
            :class="passwordErrors.current_password ? 'border-danger' : 'border-slate-200'"
            placeholder="Masukkan password saat ini"
            @input="clearPasswordFeedback"
          />

          <button
            type="button"
            class="absolute inset-y-0 right-4 text-xs font-semibold text-primary"
            @click="showCurrentPassword = !showCurrentPassword"
          >
            {{ showCurrentPassword ? 'Sembunyikan' : 'Lihat' }}
          </button>
        </div>

        <p v-if="passwordErrors.current_password" class="mt-2 text-xs font-medium text-danger">
          {{ passwordErrors.current_password }}
        </p>
      </div>

      <div>
        <label for="new-password" class="text-sm font-semibold text-slate-700">
          Password Baru
        </label>

        <div class="relative mt-2">
          <input
            id="new-password"
            v-model="passwordForm.new_password"
            :type="showNewPassword ? 'text' : 'password'"
            autocomplete="new-password"
            class="w-full rounded-2xl border bg-white px-4 py-3 pr-24 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
            :class="passwordErrors.new_password ? 'border-danger' : 'border-slate-200'"
            placeholder="Minimal 8 karakter"
            @input="clearPasswordFeedback"
          />

          <button
            type="button"
            class="absolute inset-y-0 right-4 text-xs font-semibold text-primary"
            @click="showNewPassword = !showNewPassword"
          >
            {{ showNewPassword ? 'Sembunyikan' : 'Lihat' }}
          </button>
        </div>

        <p v-if="passwordErrors.new_password" class="mt-2 text-xs font-medium text-danger">
          {{ passwordErrors.new_password }}
        </p>
      </div>

      <div>
        <label for="password-confirmation" class="text-sm font-semibold text-slate-700">
          Konfirmasi Password Baru
        </label>

        <div class="relative mt-2">
          <input
            id="password-confirmation"
            v-model="passwordForm.new_password_confirmation"
            :type="showPasswordConfirmation ? 'text' : 'password'"
            autocomplete="new-password"
            class="w-full rounded-2xl border bg-white px-4 py-3 pr-24 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-4 focus:ring-primary-tint"
            :class="passwordErrors.new_password_confirmation ? 'border-danger' : 'border-slate-200'"
            placeholder="Ulangi password baru"
            @input="clearPasswordFeedback"
          />

          <button
            type="button"
            class="absolute inset-y-0 right-4 text-xs font-semibold text-primary"
            @click="showPasswordConfirmation = !showPasswordConfirmation"
          >
            {{ showPasswordConfirmation ? 'Sembunyikan' : 'Lihat' }}
          </button>
        </div>

        <p v-if="passwordErrors.new_password_confirmation" class="mt-2 text-xs font-medium text-danger">
          {{ passwordErrors.new_password_confirmation }}
        </p>
      </div>
    </div>

    <div
      v-if="passwordSuccess"
      class="mt-6 rounded-2xl border border-success-tint bg-success-tint px-4 py-3 text-sm font-semibold text-success"
    >
      {{ passwordSuccess }}
    </div>

    <div
      v-if="passwordError"
      class="mt-6 rounded-2xl border border-danger-tint bg-danger-tint px-4 py-3 text-sm font-semibold text-danger"
    >
      {{ passwordError }}
    </div>

    <button
      type="submit"
      class="mt-6 w-full rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-50"
      :disabled="passwordSaving"
    >
      {{ passwordSaving ? 'Memperbarui...' : 'Ubah Password' }}
    </button>
  </form>
</template>

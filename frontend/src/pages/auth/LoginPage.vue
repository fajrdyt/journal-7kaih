<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import AuthLayout from '@/layouts/AuthLayout.vue'
import { useAuthStore } from '@/stores/authStore'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref('')
const showPassword = ref(false)

const form = reactive({
  identifier: '',
  password: '',
})

async function handleLogin() {
  if (loading.value) return

  try {
    loading.value = true
    error.value = ''

    await authStore.login({
      identifier: form.identifier.trim(),
      password: form.password,
    })

    const requestedPath =
      typeof route.query.redirect === 'string'
        ? route.query.redirect
        : null

    const destination = isAllowedRedirect(requestedPath)
      ? requestedPath
      : authStore.dashboardPath

    const userName =
      authStore.user?.display_name ??
      authStore.user?.full_name ??
      authStore.user?.name ??
      authStore.user?.username ??
      'Pengguna'

    sessionStorage.setItem(
      'app_toast',
      JSON.stringify({
        type: 'success',
        message: `Login berhasil. Selamat datang, ${userName}.`,
      }),
    )

    await router.replace(destination)
  } catch (err) {
    error.value =
      err.response?.data?.message ??
      authStore.error ??
      'Login gagal. Periksa kembali username/email dan password.'
  } finally {
    loading.value = false
  }
}

function isAllowedRedirect(path) {
  if (!path || !path.startsWith('/')) {
    return false
  }

  const allowedPrefixes = {
    siswa: '/student',
    guru: '/teacher',
    orang_tua: '/parent',
    admin: '/admin',
  }

  const prefix = allowedPrefixes[authStore.userRole]

  return Boolean(prefix && path.startsWith(prefix))
}

function clearError() {
  if (error.value) {
    error.value = ''
  }
}
</script>

<template>
  <AuthLayout>
    <template #hero>
      <span class="platform-badge">
        <span>✦</span>
        Platform Edukasi
      </span>

      <h2 class="hero-title">
        <span class="desktop-title-prefix">
          Jurnal 7 KAIH
        </span>

        Kebiasaan Anak Indonesia Hebat
      </h2>

      <p class="hero-description">
        Bangun Kebiasaan Baik, Ciptakan Generasi Hebat
      </p>

      <ul class="hero-features">
        <li>
          <span class="feature-icon">✓</span>
          <span>Catat 7 kebiasaan harianmu</span>
        </li>

        <li>
          <span class="feature-icon">⌁</span>
          <span>Pantau perkembangan dari waktu ke waktu</span>
        </li>

        <li>
          <span class="feature-icon">♧</span>
          <span>Bangun karakter positif dan konsisten</span>
        </li>
      </ul>
    </template>

    <template #illustration>
      <img
        src="/images/login-illustration.png"
        alt="Ilustrasi Jurnal 7 Kebiasaan Anak Indonesia Hebat"
      />
    </template>

    <section class="login-content">
      <header class="login-header">
        <h1>
          Masuk
        </h1>

        <p>
          Masukkan username/email dan password akun kamu.
        </p>
      </header>

      <div
        v-if="error"
        class="error-message"
        role="alert"
        aria-live="assertive"
      >
        <div class="error-content">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7.5v5" />
            <path d="M12 16.5h.01" />
          </svg>

          <div>
            <strong>Login gagal</strong>
            <span>{{ error }}</span>
          </div>
        </div>

        <button
          type="button"
          aria-label="Tutup pesan kesalahan"
          @click="error = ''"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <path d="M6 6l12 12" />
            <path d="M18 6L6 18" />
          </svg>
        </button>
      </div>

      <form
        class="login-form"
        @submit.prevent="handleLogin"
      >
        <div class="form-group">
          <label for="identifier">
            Username atau Email
          </label>

          <input
            id="identifier"
            v-model="form.identifier"
            type="text"
            name="identifier"
            autocomplete="username"
            placeholder="Masukkan username atau email"
            :disabled="loading"
            required
            autofocus
            @input="clearError"
          />
        </div>

        <div class="form-group">
          <label for="password">
            Password
          </label>

          <div class="password-field">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              name="password"
              autocomplete="current-password"
              placeholder="Masukkan password"
              :disabled="loading"
              required
              @input="clearError"
            />

            <button
              type="button"
              class="password-toggle"
              :aria-label="
                showPassword
                  ? 'Sembunyikan password'
                  : 'Tampilkan password'
              "
              @click="showPassword = !showPassword"
            >
              <svg
                v-if="showPassword"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <path
                  d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"
                />
                <circle cx="12" cy="12" r="3" />
              </svg>

              <svg
                v-else
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
              >
                <path d="M3 3l18 18" />
                <path
                  d="M10.6 6.17A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a15 15 0 0 1-2.1 2.77"
                />
                <path
                  d="M6.61 6.61C3.96 8.4 2.5 12 2.5 12s3.5 6 9.5 6a9.3 9.3 0 0 0 3.39-.61"
                />
                <path
                  d="M9.88 9.88a3 3 0 0 0 4.24 4.24"
                />
              </svg>
            </button>
          </div>

          <p class="password-help">
            Lupa password? Hubungi administrator sekolah.
          </p>
        </div>

        <button
          type="submit"
          class="login-button"
          :disabled="loading"
        >
          <span
            v-if="loading"
            class="loading-spinner"
          />

          {{ loading ? 'Memproses...' : 'Masuk' }}
        </button>
      </form>
    </section>
  </AuthLayout>
</template>

<style scoped>
.login-content {
  width: 100%;
}

.login-header h1 {
  margin: 0;
  color: #111111;
  font-size: 38px;
  font-weight: 800;
  letter-spacing: -0.035em;
  line-height: 1.05;
}

.login-header p {
  margin: 10px 0 0;
  color: #7b8190;
  font-size: 14px;
  line-height: 1.55;
}

.error-message {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-top: 22px;
  padding: 13px 14px;
  border: 1px solid #fecaca;
  border-radius: 12px;
  background: #fef2f2;
  color: #dc2626;
}

.error-content {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.error-content > svg {
  width: 19px;
  height: 19px;
  flex-shrink: 0;
  margin-top: 1px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.error-content strong,
.error-content span {
  display: block;
}

.error-content strong {
  margin-bottom: 2px;
  font-size: 12px;
}

.error-content span {
  font-size: 11px;
  line-height: 1.5;
}

.error-message > button {
  width: 28px;
  height: 28px;
  display: grid;
  flex-shrink: 0;
  place-items: center;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: currentColor;
  cursor: pointer;
}

.error-message > button:hover {
  background: #fee2e2;
}

.error-message > button svg {
  width: 15px;
  height: 15px;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
  margin-top: 28px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 9px;
  color: #3f4653;
  font-size: 14px;
  font-weight: 700;
}

.form-group input {
  width: 100%;
  min-height: 48px;
  border: 1px solid #cfd4dc;
  border-radius: 12px;
  padding: 0 15px;
  box-sizing: border-box;
  background: #ffffff;
  color: #172033;
  font: inherit;
  font-size: 14px;
  outline: none;
  transition:
    border-color 0.18s ease,
    box-shadow 0.18s ease,
    background 0.18s ease;
}

.form-group input::placeholder {
  color: #9ba1ac;
}

.form-group input:hover:not(:disabled) {
  border-color: #aeb6c2;
}

.form-group input:focus {
  border-color: #168ce0;
  box-shadow: 0 0 0 4px rgba(22, 140, 224, 0.12);
}

.form-group input:disabled {
  cursor: not-allowed;
  background: #f4f5f7;
  opacity: 0.72;
}

.password-field {
  position: relative;
}

.password-field input {
  padding-right: 52px;
}

.password-toggle {
  position: absolute;
  top: 50%;
  right: 7px;
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: #8b919c;
  cursor: pointer;
  transform: translateY(-50%);
  transition:
    color 0.18s ease,
    background 0.18s ease;
}

.password-toggle:hover {
  background: #eef7ff;
  color: #168ce0;
}

.password-toggle:focus-visible {
  outline: 2px solid #168ce0;
  outline-offset: 1px;
}

.password-toggle svg {
  width: 19px;
  height: 19px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.password-help {
  margin: 8px 0 0;
  text-align: right;
  color: #168ce0;
  font-size: 11px;
  line-height: 1.4;
}

.login-button {
  width: 100%;
  min-height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  margin-top: 4px;
  border: 1px solid #cfd4dc;
  border-radius: 12px;
  background: #0D99FF;
  color: #ffffff;
  font: inherit;
  font-size: 15px;
  font-weight: 800;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background 0.18s ease,
    color 0.18s ease,
    transform 0.18s ease,
    box-shadow 0.18s ease;
}

.login-button:hover:not(:disabled) {
  background: #0b85e0;
  border-color: #0b85e0;
  color: #ffffff;
  box-shadow: 0 10px 24px rgba(13, 153, 255, 0.28);
  transform: translateY(-1px);
}

.login-button:active:not(:disabled) {
  transform: translateY(0);
}

.login-button:disabled {
  cursor: not-allowed;
  opacity: 0.68;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(22, 140, 224, 0.24);
  border-top-color: #168ce0;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.platform-badge {
  width: fit-content;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  padding: 7px 11px;
  background: rgba(255, 255, 255, 0.17);
  color: #ffffff;
  font-size: 12px;
  font-weight: 700;
  backdrop-filter: blur(8px);
}

.hero-title {
  max-width: 520px;
  margin: 18px 0 0;
  color: #ffffff;
  font-size: clamp(38px, 3.1vw, 48px);
  font-weight: 800;
  letter-spacing: -0.04em;
  line-height: 1.03;
}

.desktop-title-prefix {
  display: block;
}

.hero-description {
  max-width: 440px;
  margin: 12px 0 0;
  color: #e4f3ff;
  font-size: 13px;
  line-height: 1.5;
}

.hero-features {
  display: grid;
  gap: 9px;
  max-width: 440px;
  margin: 18px 0 0;
  padding: 0;
  list-style: none;
}

.hero-features li {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #ffffff;
  font-size: 12px;
  font-weight: 650;
  line-height: 1.35;
}

.feature-icon {
  width: 26px;
  height: 26px;
  display: grid;
  flex-shrink: 0;
  place-items: center;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.17);
  color: #ffffff;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (min-width: 901px) and (max-height: 700px) {
  .login-header h1 {
    font-size: 34px;
  }

  .login-header p {
    margin-top: 7px;
    font-size: 13px;
  }

  .login-form {
    gap: 15px;
    margin-top: 22px;
  }

  .form-group label {
    margin-bottom: 7px;
    font-size: 13px;
  }

  .form-group input,
  .login-button {
    min-height: 44px;
  }

  .password-help {
    margin-top: 6px;
  }

  .platform-badge {
    padding: 6px 10px;
    font-size: 11px;
  }

  .hero-title {
    margin-top: 14px;
    font-size: clamp(33px, 2.8vw, 42px);
  }

  .hero-description {
    margin-top: 8px;
    font-size: 12px;
  }

  .hero-features {
    gap: 7px;
    margin-top: 13px;
  }

  .hero-features li {
    font-size: 11px;
  }

  .feature-icon {
    width: 23px;
    height: 23px;
  }
}

@media (max-width: 900px) {
  .login-header h1 {
    font-size: 28px;
  }

  .login-header p {
    max-width: 330px;
    margin-top: 7px;
    font-size: 13px;
  }

  .login-form {
    gap: 20px;
    margin-top: 26px;
  }

  .platform-badge {
    display: none;
  }

  .hero-title {
    max-width: 340px;
    margin-top: 0;
    font-size: 25px;
    line-height: 1.08;
  }

  .desktop-title-prefix {
    display: none;
  }

  .hero-description {
    max-width: 350px;
    margin-top: 6px;
    font-size: 12px;
  }

  .hero-features {
    display: none;
  }
}

@media (max-width: 480px) {
  .form-group input,
  .login-button {
    min-height: 50px;
  }

  .form-group label {
    font-size: 13px;
  }

  .password-help {
    font-size: 10px;
  }
}
</style>
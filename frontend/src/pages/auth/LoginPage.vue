<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

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
  if (loading.value) {
    return
  }

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
      err.response?.data?.message ||
      authStore.error ||
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
  <main class="login-page">
    <section class="login-container">
      <div class="login-left">
        <div class="login-form-wrapper">
          <h1>Masuk</h1>

          <p class="login-description">
            Masukkan username/email dan password akun kamu.
          </p>

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
              aria-label="Tutup pesan"
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
            </div>

            <button
              type="submit"
              class="login-button"
              :disabled="loading"
            >
              <span
                v-if="loading"
                class="loading-spinner"
              ></span>

              {{ loading ? 'Memproses...' : 'Masuk' }}
            </button>
          </form>
        </div>
      </div>

      <div class="login-right">
        <div class="right-content">
          <div class="right-copy">
            <h2>
              Journal 7<br />
              Kebiasaan Anak<br />
              Indonesia Hebat
            </h2>

            <p>
              Bangun Kebiasaan Baik, Ciptakan Generasi Hebat
            </p>
          </div>

          <div class="illustration-wrapper">
            <img
              src="/images/login-illustration.png"
              alt="Ilustrasi Jurnal 7 Kebiasaan Anak Indonesia Hebat"
            />
          </div>
        </div>
      </div>
    </section>
  </main>
</template>
<style scoped>
.login-page {
  width: 100%;
  min-height: 100vh;
  min-height: 100dvh;
  background: #ededed;
  color: #111827;
}

.login-container {
  width: 100%;
  min-height: 100vh;
  min-height: 100dvh;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  overflow: hidden;
  background: #ffffff;
}

.login-left {
  min-width: 0;
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: clamp(40px, 6vw, 88px);
  box-sizing: border-box;
  background: #ffffff;
}

.login-form-wrapper {
  width: 100%;
  max-width: 440px;
}

.login-form-wrapper h1 {
  margin: 0;
  color: #000000;
  font-size: clamp(42px, 4vw, 56px);
  font-weight: 700;
  line-height: 1;
}

.login-description {
  margin: 16px 0 48px;
  color: #8a8a8a;
  font-size: 14px;
  line-height: 1.6;
}

.error-message {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 24px;
  padding: 13px 14px;
  border: 1px solid #fecaca;
  border-radius: 8px;
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
  width: 20px;
  height: 20px;
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
  margin-bottom: 3px;
  font-size: 12px;
  font-weight: 700;
}

.error-content span {
  font-size: 11px;
  font-weight: 500;
  line-height: 1.5;
}

.error-message > button {
  width: 26px;
  height: 26px;
  display: grid;
  flex-shrink: 0;
  place-items: center;
  border: 0;
  border-radius: 5px;
  background: transparent;
  color: #dc2626;
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
  gap: 31px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 10px;
  color: #000000;
  font-size: 18px;
  font-weight: 400;
}

.form-group input {
  width: 100%;
  min-height: 47px;
  border: 0;
  border-bottom: 1px solid #a0a0a0;
  padding: 8px 0;
  box-sizing: border-box;
  background: transparent;
  color: #111827;
  font: inherit;
  font-size: 15px;
  outline: none;
  transition: border-color 0.18s ease;
}

.form-group input::placeholder {
  color: #b0b0b0;
  font-size: 13px;
}

.form-group input:focus {
  border-bottom-color: #2196f3;
}

.form-group input:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.password-field {
  position: relative;
}

.password-field input {
  padding-right: 44px;
}

.password-toggle {
  position: absolute;
  top: 50%;
  right: 0;
  width: 40px;
  height: 40px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #4b5563;
  cursor: pointer;
  transform: translateY(-50%);
  transition:
    color 0.18s ease,
    background 0.18s ease;
}

.password-toggle:hover {
  background: #eff6ff;
  color: #2196f3;
}

.password-toggle:focus-visible {
  outline: 2px solid #2196f3;
  outline-offset: 2px;
}

.password-toggle svg {
  width: 20px;
  height: 20px;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.login-button {
  width: 100%;
  min-height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  margin-top: 3px;
  border: 0;
  border-radius: 6px;
  background: #2196f3;
  color: #ffffff;
  font: inherit;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background 0.18s ease,
    opacity 0.18s ease;
}

.login-button:hover:not(:disabled) {
  background: #1e88e5;
}

.login-button:disabled {
  cursor: not-allowed;
  opacity: 0.72;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.login-right {
  position: relative;
  min-width: 0;
  min-height: 100vh;
  min-height: 100dvh;
  overflow: hidden;
  background: #2196f3;
}

.right-content {
  width: 100%;
  min-height: 100vh;
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  padding: clamp(44px, 5vw, 78px) clamp(40px, 6vw, 90px) 0;
  box-sizing: border-box;
  color: #ffffff;
}

.right-copy {
  position: relative;
  z-index: 2;
  flex-shrink: 0;
}

.right-copy h2 {
  margin: 0;
  color: #ffffff;
  font-size: clamp(48px, 4.6vw, 76px);
  font-weight: 700;
  letter-spacing: -0.04em;
  line-height: 1.04;
}

.right-copy p {
  margin: 26px 0 0;
  color: #dbeafe;
  font-size: 14px;
  line-height: 1.6;
}

.illustration-wrapper {
  min-height: 0;
  display: flex;
  flex: 1;
  align-items: flex-end;
  justify-content: center;
  margin-top: 20px;
}

.illustration-wrapper img {
  width: min(100%, 500px);
  max-height: 46vh;
  display: block;
  object-fit: contain;
  object-position: center bottom;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 1050px) {
  .login-left {
    padding: 48px 42px;
  }

  .right-content {
    padding: 48px 42px 0;
  }

  .right-copy h2 {
    font-size: clamp(42px, 5vw, 58px);
  }

  .illustration-wrapper img {
    max-height: 42vh;
  }
}

@media (max-width: 760px) {
  .login-container {
    grid-template-columns: 1fr;
  }

  .login-left {
    min-height: auto;
    align-items: flex-start;
    padding: 54px 24px 58px;
  }

  .login-form-wrapper {
    max-width: 480px;
    margin: 0 auto;
  }

  .login-form-wrapper h1 {
    font-size: 42px;
  }

  .login-description {
    margin-bottom: 38px;
  }

  .login-right {
    display: block;
    min-height: 430px;
  }

  .right-content {
    min-height: 430px;
    padding: 36px 28px 0;
  }

  .right-copy h2 {
    font-size: clamp(34px, 10vw, 48px);
    line-height: 1.05;
  }

  .right-copy p {
    max-width: 330px;
    margin-top: 16px;
    font-size: 12px;
  }

  .illustration-wrapper {
    margin-top: 16px;
  }

  .illustration-wrapper img {
    width: min(100%, 310px);
    max-height: 230px;
  }
}

@media (max-width: 480px) {
  .login-left {
    padding: 46px 20px 50px;
  }

  .login-form-wrapper h1 {
    font-size: 38px;
  }

  .login-description {
    margin-top: 12px;
    margin-bottom: 34px;
    font-size: 13px;
  }

  .login-form {
    gap: 26px;
  }

  .form-group label {
    font-size: 16px;
  }

  .form-group input {
    min-height: 44px;
    font-size: 14px;
  }

  .login-button {
    min-height: 46px;
  }

  .login-right {
    min-height: 390px;
  }

  .right-content {
    min-height: 390px;
    padding: 30px 22px 0;
  }

  .right-copy h2 {
    font-size: 33px;
  }

  .illustration-wrapper img {
    width: min(100%, 270px);
    max-height: 205px;
  }
}
</style>
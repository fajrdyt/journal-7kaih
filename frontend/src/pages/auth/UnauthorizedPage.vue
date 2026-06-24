<template>
  <main class="unauthorized-page">
    <section class="unauthorized-card">
      <span class="status-code">403</span>

      <h1>Akses tidak diizinkan</h1>

      <p>
        Akun kamu tidak memiliki izin untuk membuka halaman ini.
      </p>

      <button
        type="button"
        @click="goToDashboard"
      >
        Kembali ke Dashboard
      </button>
    </section>
  </main>
</template>

<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

function goToDashboard() {
  router.push(authStore.dashboardPath || '/login')
}
</script>

<style scoped>
.unauthorized-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px;
  background: #f5f8fc;
}

.unauthorized-card {
  width: min(100%, 440px);
  padding: 42px 32px;
  border: 1px solid #e2e8f0;
  border-radius: 22px;
  background: #ffffff;
  text-align: center;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
}

.status-code {
  display: inline-block;
  margin-bottom: 14px;
  color: #209cee;
  font-size: 52px;
  font-weight: 900;
  line-height: 1;
}

h1 {
  margin: 0;
  color: #0f172a;
  font-size: 24px;
}

p {
  margin: 12px 0 26px;
  color: #64748b;
  line-height: 1.65;
}

button {
  min-height: 44px;
  padding: 0 20px;
  border: 0;
  border-radius: 12px;
  background: #209cee;
  color: #ffffff;
  font: inherit;
  font-weight: 800;
  cursor: pointer;
}
</style>
<script setup>
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/authStore'

const router = useRouter()

const authStore = useAuthStore()

async function handleLogout() {

  await authStore.logout()

  router.push('/login')
}
</script>

<template>

  <div>

    <h1>Student Dashboard</h1>

    <button @click="handleLogout">
      Logout
    </button>

  </div>

</template>
<script setup>
import { onMounted } from 'vue'

import AppToast from './components/common/AppToast.vue'
import { useAuthStore } from './stores/authStore'

const authStore = useAuthStore()

onMounted(() => {
  authStore.fetchUser()
})
</script>

<template>
  <router-view />
  <AppToast />
</template>
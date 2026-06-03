<script setup>
import { onMounted } from 'vue'

import { useAuthStore } from './stores/authStore'

const authStore = useAuthStore()

onMounted(() => {
  authStore.fetchUser()
})
</script>


<template>
  <router-view />
</template>
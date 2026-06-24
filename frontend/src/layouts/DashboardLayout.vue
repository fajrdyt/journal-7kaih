<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

import HeaderBar from '../components/navigation/HeaderBar.vue'
import Sidebar from '../components/navigation/Sidebar.vue'

const route = useRoute()
const sidebarOpen = ref(false)

watch(
  () => route.fullPath,
  () => {
    sidebarOpen.value = false
  },
)
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <Sidebar
      :mobile-open="sidebarOpen"
      @close="sidebarOpen = false"
    />

    <button
      v-if="sidebarOpen"
      type="button"
      class="fixed inset-0 z-40 bg-slate-950/40 backdrop-blur-[1px] lg:hidden"
      aria-label="Tutup navigasi"
      @click="sidebarOpen = false"
    ></button>

    <main class="min-h-screen transition-[margin] duration-300 lg:ml-60">
      <HeaderBar @toggle-sidebar="sidebarOpen = true" />

      <div class="min-w-0 px-4 pb-8 sm:px-6 lg:px-8">
        <RouterView />
      </div>
    </main>
  </div>
</template>
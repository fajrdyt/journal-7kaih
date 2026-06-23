<script setup>
import HeaderBar from '../components/navigation/HeaderBar.vue'
import Sidebar from '../components/navigation/Sidebar.vue'
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <Sidebar />

    <main class="ml-60 min-h-screen">
      <HeaderBar />

      <RouterView />
    </main>
  </div>
</template>
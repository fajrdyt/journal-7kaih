<template>
  <div class="min-h-screen bg-slate-50">
    <Sidebar />

    <div class="lg:ml-64">
      <HeaderBar />

      <main class="p-4 lg:p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import Sidebar from '@/components/navigation/Sidebar.vue'
import HeaderBar from '@/components/navigation/HeaderBar.vue'
</script>
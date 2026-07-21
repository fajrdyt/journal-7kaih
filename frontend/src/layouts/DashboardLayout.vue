<script setup>
import HeaderBar from '../components/navigation/HeaderBar.vue'
import MobileBottomNavigation from '../components/navigation/MobileBottomNavigation.vue'
import AppSidebar from '../components/common/AppSidebar.vue'
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <AppSidebar />

    <main
      class="min-h-screen transition-[margin] duration-300 lg:ml-60"
    >
      <HeaderBar :show-menu-button="false" />

      <div
        class="min-w-0 px-3 pt-6 pb-32 sm:px-5 lg:px-8 lg:pb-8"
      >
        <RouterView />
      </div>
    </main>

    <MobileBottomNavigation />
  </div>
</template>
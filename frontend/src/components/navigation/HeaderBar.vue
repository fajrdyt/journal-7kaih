<script setup>
import { computed } from 'vue'

import { useAuthStore } from '../../stores/authStore'

const authStore = useAuthStore()

const displayName = computed(() => {
  return authStore.user?.display_name ?? authStore.user?.full_name ?? authStore.user?.name ?? 'Siswa'
})

const initialName = computed(() => {
  return displayName.value?.charAt(0)?.toUpperCase() ?? 'S'
})
</script>

<template>
  <header class="flex items-center justify-between px-8 py-5">
    <h1 class="text-xl font-bold text-slate-900">
      Jurnal Kebiasaan
    </h1>

    <div class="flex items-center gap-5">
      <button
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
        aria-label="Notifikasi"
      >
        🔔
      </button>

      <div class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-blue-100 text-sm font-bold text-blue-700">
        {{ initialName }}
      </div>
    </div>
  </header>
</template>
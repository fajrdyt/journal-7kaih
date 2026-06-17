<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'

const authStore = useAuthStore()

const displayName = computed(() => authStore.user?.display_name ?? 'Siswa')
</script>

<template>
  <header class="mx-4 mt-4 flex min-h-[72px] items-center justify-between rounded-xl border bg-white px-6 shadow-sm lg:mx-8">
    <div>
      <h2 class="font-semibold text-slate-900">
        Selamat Datang
      </h2>

      <p class="text-sm text-slate-500">
        Halo, {{ displayName }}! Mari bangun kebiasaan positif hari ini.
      </p>
    </div>

    <div class="flex items-center gap-4">
      <button type="button">
        🔔
      </button>

      <div class="flex items-center gap-2">
        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 font-semibold text-sky-700">
          {{ displayName.charAt(0).toUpperCase() }}
        </div>

        <span class="hidden text-sm md:inline">
          {{ displayName }}
        </span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = useRouter()
const authStore = useAuthStore()

async function handleLogout() {
  await authStore.logout()

  router.push('/login')
}
</script>

<template>
  <aside class="fixed left-0 top-0 hidden h-screen w-[240px] flex-col border-r bg-white lg:flex">
    <div class="border-b p-5">
      <h1 class="font-bold text-sky-700">
        Jurnal 7KAIH
      </h1>

      <p class="text-xs text-slate-500">
        Habit Tracking System
      </p>
    </div>

    <nav class="flex-1 p-4">
      <ul class="space-y-2">
        <li>
          <RouterLink
            to="/student/dashboard"
            class="block rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"
            active-class="bg-sky-50 text-sky-700"
          >
            Dashboard
          </RouterLink>
        </li>

        <li>
          <RouterLink
            to="/student/checkin"
            class="block rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"
            active-class="bg-sky-50 text-sky-700"
          >
            Check-in
          </RouterLink>
        </li>

        <li>
          <RouterLink
            to="/student/history"
            class="block rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"
            active-class="bg-sky-50 text-sky-700"
          >
            Riwayat
          </RouterLink>
        </li>

        <li>
          <RouterLink
            to="/student/recap"
            class="block rounded-lg px-4 py-3 text-sm font-medium text-slate-600 hover:bg-slate-100"
            active-class="bg-sky-50 text-sky-700"
          >
            Rekap
          </RouterLink>
        </li>
      </ul>
    </nav>

    <div class="border-t p-4">
      <button
        type="button"
        class="w-full rounded-lg px-4 py-3 text-left text-sm text-slate-600 hover:bg-red-50 hover:text-red-600"
        @click="handleLogout"
      >
        Logout
      </button>
    </div>
  </aside>
</template>

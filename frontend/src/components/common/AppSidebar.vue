<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useNavConfig } from '@/composables/useNavConfig'
import { resolveIcon } from '@/config/iconRegistry'

import { ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const roleRef = computed(() => authStore.role)

// `mobileItems` sengaja tidak dipakai di sini — itu dipakai oleh
// MobileBottomNavigation.vue supaya tidak ada dua bottom bar tumpang tindih.
const { brand, subtitle, items, settingsItem, isActive } = useNavConfig(roleRef)

async function handleLogout() {
  await authStore.logout()
}
</script>

<template>
  <!-- Desktop-only. Mobile ditangani sepenuhnya oleh MobileBottomNavigation.vue -->
  <aside
    class="fixed left-0 top-0 hidden h-dvh w-60 flex-col border-r border-ink-border bg-surface lg:flex"
  >
    <!-- Brand -->
    <RouterLink :to="items[0]?.to ?? '/login'" class="flex items-center gap-3 px-5 py-6">
      <div
        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary text-sm font-black text-white shadow-[0_8px_20px_rgba(13,153,255,0.24)]"
      >
        7K
      </div>
      <div class="min-w-0">
        <p class="truncate text-[15px] font-black leading-tight text-primary-dark">
          {{ brand }}
        </p>
        <p class="mt-1 truncate text-[10px] font-semibold uppercase tracking-wide text-ink-muted">
          {{ subtitle }}
        </p>
      </div>
    </RouterLink>

    <!-- Nav utama -->
    <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3" aria-label="Navigasi utama">
      <router-link
        v-for="item in items"
        :key="item.to"
        :to="item.to"
        class="flex items-center gap-3 rounded-r-lg border-l-[3px] px-3 py-2.5 text-[13px] font-semibold transition-colors"
        :class="isActive(item)
          ? 'border-primary bg-primary-tint text-primary-dark'
          : 'border-transparent text-ink hover:bg-surface-hover'"
      >
        <component
          :is="resolveIcon(item.icon)"
          class="h-[18px] w-[18px] flex-shrink-0"
          :class="isActive(item) ? 'text-primary' : 'text-ink-muted'"
        />
        <span class="truncate">{{ item.label }}</span>
      </router-link>

      <div v-if="!items.length" class="mx-1 mt-2 rounded-xl bg-surface-hover px-4 py-3 text-xs font-medium text-ink-muted">
        Menu belum tersedia untuk akun ini.
      </div>
    </nav>

    <!-- Footer: pengaturan + logout -->
    <div class="border-t border-ink-border p-3">
      <router-link
        :to="settingsItem.to"
        class="flex items-center gap-3 rounded-r-lg border-l-[3px] px-3 py-2.5 text-[13px] font-semibold transition-colors"
        :class="isActive(settingsItem)
          ? 'border-primary bg-primary-tint text-primary-dark'
          : 'border-transparent text-ink hover:bg-surface-hover'"
      >
        <component
          :is="resolveIcon(settingsItem.icon)"
          class="h-[18px] w-[18px] flex-shrink-0"
          :class="isActive(settingsItem) ? 'text-primary' : 'text-ink-muted'"
        />
        {{ settingsItem.label }}
      </router-link>

      <button
        type="button"
        class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-semibold text-ink-muted transition-colors hover:bg-danger-tint hover:text-danger"
        @click="handleLogout"
      >
        <ArrowRightOnRectangleIcon class="h-[18px] w-[18px] flex-shrink-0" />
        Logout
      </button>
    </div>
  </aside>
</template>

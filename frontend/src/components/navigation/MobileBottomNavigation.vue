<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useNavConfig } from '@/composables/useNavConfig'
import { resolveIcon } from '@/config/iconRegistry'

const authStore = useAuthStore()
const roleRef = computed(() => authStore.role)

// mobileItems = items utama + settings digabung otomatis oleh composable,
// jadi menu di sini selalu sinkron dengan AppSidebar.vue (satu sumber data).
const { mobileItems, isActive } = useNavConfig(roleRef)

const navigationStyle = computed(() => {
  return {
    gridTemplateColumns: `repeat(${Math.max(mobileItems.value.length, 1)}, minmax(0, 1fr))`,
  }
})
</script>

<template>
  <nav
    v-if="mobileItems.length"
    class="mobile-navigation lg:hidden"
    aria-label="Navigasi utama"
  >
    <div class="mobile-navigation__inner" :style="navigationStyle">
      <RouterLink
        v-for="item in mobileItems"
        :key="item.to"
        :to="item.to"
        class="navigation-item"
        :class="{ 'navigation-item--active': isActive(item) }"
        :aria-current="isActive(item) ? 'page' : undefined"
      >
        <span class="navigation-icon">
          <component :is="resolveIcon(item.icon)" class="h-[19px] w-[19px]" />
        </span>

        <span class="navigation-label">
          {{ item.mobileLabel ?? item.label }}
        </span>
      </RouterLink>
    </div>
  </nav>
</template>

<style scoped>
.mobile-navigation {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 60;
}

.mobile-navigation__inner {
  min-height: 72px;
  display: grid;
  align-items: center;
  padding:
    7px
    8px
    calc(7px + env(safe-area-inset-bottom));
  border-top: 1px solid var(--color-ink-border);
  border-radius: 22px 22px 0 0;
  background: var(--color-surface);
  box-shadow: 0 -10px 32px rgba(15, 23, 42, 0.1);
}

.navigation-item {
  position: relative;
  min-width: 0;
  min-height: 58px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  padding: 5px 2px;
  border-radius: 15px;
  color: var(--color-ink-muted);
  text-decoration: none;
  transition:
    color 0.18s ease,
    background 0.18s ease,
    transform 0.18s ease;
}

.navigation-item::before {
  position: absolute;
  top: -7px;
  left: 50%;
  width: 26px;
  height: 3px;
  border-radius: 999px;
  background: var(--color-primary);
  content: '';
  opacity: 0;
  transform: translateX(-50%);
  transition: opacity 0.18s ease;
}

.navigation-item--active {
  color: var(--color-primary-dark);
}

.navigation-item--active::before {
  opacity: 1;
}

.navigation-icon {
  width: 32px;
  height: 32px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  transition:
    color 0.18s ease,
    background 0.18s ease,
    box-shadow 0.18s ease;
}

.navigation-item--active .navigation-icon {
  background: var(--color-primary-tint);
  color: var(--color-primary-dark);
  box-shadow: 0 5px 14px rgba(13, 153, 255, 0.14);
}

.navigation-label {
  max-width: 100%;
  overflow: hidden;
  font-size: 9px;
  font-weight: 700;
  line-height: 1.2;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.navigation-item:active {
  transform: scale(0.96);
}

@media (max-width: 370px) {
  .mobile-navigation__inner {
    padding-right: 3px;
    padding-left: 3px;
  }

  .navigation-item {
    padding-right: 1px;
    padding-left: 1px;
  }

  .navigation-label {
    font-size: 8px;
  }

  .navigation-icon {
    width: 30px;
    height: 30px;
  }
}
</style>

// src/composables/useNavConfig.js
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { navConfig, brandInfo } from '@/config/navConfig'

export function useNavConfig(roleRef) {
  const route = useRoute()

  const config = computed(() => navConfig[roleRef.value] ?? navConfig.siswa)
  const items = computed(() => config.value.items)
  const settingsItem = computed(() => config.value.settings)

  // Semua item nav termasuk pengaturan — dipakai di bottom tab bar mobile
  const mobileItems = computed(() => [...items.value, settingsItem.value])

  function isActive(item) {
    if (item.exact) {
      return route.path === item.to
    }
    return route.path === item.to || route.path.startsWith(`${item.to}/`)
  }

  return {
    brand: brandInfo.name,
    subtitle: brandInfo.subtitle,
    items,
    settingsItem,
    mobileItems,
    isActive,
  }
}
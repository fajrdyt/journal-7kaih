// src/composables/useSidebarState.js
import { ref, watch } from 'vue'

const STORAGE_KEY = 'sidebar:collapsed'
const isCollapsed = ref(localStorage.getItem(STORAGE_KEY) === 'true')

watch(isCollapsed, (value) => {
  localStorage.setItem(STORAGE_KEY, String(value))
})

export function useSidebarState() {
  function toggleSidebar() {
    isCollapsed.value = !isCollapsed.value
  }
  return { isCollapsed, toggleSidebar }
}
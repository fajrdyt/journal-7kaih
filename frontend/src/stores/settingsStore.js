import { defineStore } from 'pinia'

const STORAGE_KEY = 'student_settings'

const defaultSettings = {
  theme: 'light',
}

function loadSettings() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)

    if (!saved) return { ...defaultSettings }

    return {
      ...defaultSettings,
      ...JSON.parse(saved),
    }
  } catch (error) {
    console.error(error)

    return { ...defaultSettings }
  }
}

function applyTheme(theme) {
  const root = document.documentElement

  root.classList.remove('theme-light', 'theme-dark')
  root.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light')
  root.dataset.theme = theme
}

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    theme: 'light',
    initialized: false,
  }),

  getters: {
    isDarkMode: (state) => state.theme === 'dark',
    themeLabel: (state) => (state.theme === 'dark' ? 'Gelap' : 'Terang'),
  },

  actions: {
    init() {
      const settings = loadSettings()

      this.theme = settings.theme
      this.initialized = true

      applyTheme(this.theme)
    },

    persist() {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          theme: this.theme,
        }),
      )
    },

    setTheme(theme) {
      this.theme = theme === 'dark' ? 'dark' : 'light'

      applyTheme(this.theme)
      this.persist()
    },

    toggleTheme() {
      this.setTheme(this.theme === 'dark' ? 'light' : 'dark')
    },

    resetSettings() {
      this.theme = defaultSettings.theme

      applyTheme(this.theme)
      this.persist()
    },
  },
})
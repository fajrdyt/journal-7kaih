import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import { useSettingsStore } from './stores/settingsStore'

import './assets/main.css'

async function enableMocking() {
  const useMocks = import.meta.env.VITE_USE_MOCKS === 'true'

  if (!import.meta.env.DEV || !useMocks) {
    return
  }

  const { worker } = await import('./mocks/browser')

  return worker.start({
    onUnhandledRequest: 'bypass',
  })
}

async function bootstrap() {
  await enableMocking()

  const app = createApp(App)

  app.use(createPinia())
  app.use(router)

  const settingsStore = useSettingsStore()
  settingsStore.init()

  app.mount('#app')
}

bootstrap()

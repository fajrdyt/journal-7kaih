import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

import './assets/main.css'

async function enableMocking() {

  if (import.meta.env.DEV) {

    const { worker } = await import('./mocks/browser')

    return worker.start()
  }
}

async function bootstrap() {

  await enableMocking()


const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
}

bootstrap()
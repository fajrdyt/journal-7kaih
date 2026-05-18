import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '../pages/auth/LoginPage.vue'

const routes = [
  {
    path: '/',
    name: 'login',
    component: LoginPage,
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
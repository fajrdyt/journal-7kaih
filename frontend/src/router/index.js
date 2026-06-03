import { createRouter, createWebHistory } from 'vue-router'

import LoginPage from '../pages/auth/LoginPage.vue'
import DashboardPage from '../pages/student/StudentDashboard.vue'

import { useAuthStore } from '../stores/authStore'

const routes = [
  {
    path: '/',
    name: 'login',
    component: LoginPage,
  },

  {
    path: '/login',
    name: 'login',
    component: LoginPage,
  },

  {
    path:'/dashboard',
    name:'dashboard',
    component: DashboardPage,
    meta: {
      requiresAuth: true,
    },
  },
  
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {

  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  if (to.path === '/login' && authStore.isAuthenticated) {
    return next('/dashboard')
  }
  next()
})
export default router
import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../pages/auth/LoginPage.vue'
import { useAuthStore } from '../stores/authStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
  },
  {
    path: '/student',
    component: DashboardLayout,
    meta: {
      requiresAuth: true,
      role: 'student',
    },
    children: [
      {
        path: '',
        redirect: '/student/dashboard',
      },
      {
        path: 'dashboard',
        name: 'student-dashboard',
        component: () => import('@/pages/student/StudentDashboardPage.vue'),
      },
      {
        path: 'checkin',
        name: 'student-checkin',
        component: () => import('@/pages/student/DailyCheckinPage.vue'),
      },
      {
        path: 'history',
        name: 'student-history',
        component: () => import('@/pages/student/CheckinHistoryPage.vue'),
      },
      {
        path: 'recap',
        name: 'student-recap',
        component: () => import('@/pages/student/PersonalRecapPage.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return '/login'
  }

  if (to.meta.role === 'student' && !authStore.isStudent) {
    return '/login'
  }

  if (to.path === '/login' && authStore.isAuthenticated) {
    return '/student/dashboard'
  }

  return true
})

export default router

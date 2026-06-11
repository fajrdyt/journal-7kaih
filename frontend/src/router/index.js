import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../pages/auth/LoginPage.vue'
import { useAuthStore } from '../stores/authStore'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const routes = [
  // 1. Arahkan halaman utama langsung ke halaman login
  {
    path: '/',
    redirect: '/login',
  },

  // 2. Rute Login tunggal dengan nama unik
  {
    path: '/login',
    name: 'login',
    component: LoginPage,
  },

  // 3. Grup Rute Khusus Student dengan Layout dan Pengaman Role
  {
    path: '/student',
    component: DashboardLayout,
    meta: {
      requiresAuth: true,
      role: 'student', // Menegaskan bahwa halaman ini hanya untuk student
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

// Pengaman Jalur Navigasi (Navigation Guard)
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()

  // Jika halaman butuh autentikasi dan user belum login, lempar ke login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next('/login')
  }

  // Jika halaman butuh role student tetapi user yang login bukan student
  if (to.meta.role === 'student' && authStore.user?.role !== 'student') {
    return next('/login')
  }

  // Jika user sudah login dan mencoba membuka halaman login lagi, lempar ke dashboard
  if (to.path === '/login' && authStore.isAuthenticated) {
    return next('/student/dashboard')
  }

  next()
})

export default router

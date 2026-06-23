
import { createRouter, createWebHistory } from 'vue-router'

import DashboardLayout from '@/layouts/DashboardLayout.vue'
import LoginPage from '@/pages/auth/LoginPage.vue'
import { useAuthStore } from '@/stores/authStore'

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

  // Student routes
  {
    path: '/student',
    component: DashboardLayout,
    meta: {
      requiresAuth: true,
      roles: ['siswa', 'student'],
    },
    children: [
      {
        path: '',
        redirect: '/student/dashboard',
      },
      {
        path: 'dashboard',
        name: 'student-dashboard',
        component: () =>
          import('@/pages/student/StudentDashboardPage.vue'),
        meta: {
          title: 'Dashboard Siswa',
        },
      },
      {
        path: 'checkin',
        name: 'student-checkin',
        component: () =>
          import('@/pages/student/DailyCheckinPage.vue'),
        meta: {
          title: 'Check-in Harian',
        },
      },
      {
        path: 'history',
        name: 'student-history',
        component: () =>
          import('@/pages/student/CheckinHistoryPage.vue'),
        meta: {
          title: 'Riwayat Check-in',
        },
      },
      {
        path: 'recap',
        name: 'student-recap',
        component: () =>
          import('@/pages/student/PersonalRecapPage.vue'),
        meta: {
          title: 'Rekap Pribadi',
        },
      },
      {
        path: 'settings',
        name: 'student-settings',
        component: () =>
          import('@/pages/student/StudentSettingsPage.vue'),
        meta: {
          title: 'Pengaturan Akun',
        },
      },
    ],
  },

  // Parent routes
  {
  path: '/parent',
  component: DashboardLayout,
  meta: {
    requiresAuth: true,
    roles: ['orang_tua', 'parent'],
  },
  children: [
    {
      path: '',
      redirect: '/parent/dashboard',
    },
    {
      path: 'dashboard',
      name: 'parent-dashboard',
      component: () =>
        import('@/pages/parent/ParentDashboardPage.vue'),
      meta: {
        title: 'Dashboard Orang Tua',
      },
    },
    {
      path: 'validation',
      name: 'parent-validation',
      component: () =>
        import('@/pages/parent/ParentValidationPage.vue'),
      meta: {
        title: 'Validasi Check-in Anak',
      },
    },
  ],
},

  // Fallback
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

function normalizeRole(role) {
  const value = String(role ?? '')
    .trim()
    .toLowerCase()

  const aliases = {
    student: 'siswa',
    siswa: 'siswa',
    teacher: 'guru',
    guru: 'guru',
    parent: 'orang_tua',
    orang_tua: 'orang_tua',
    admin: 'admin',
  }

  return aliases[value] ?? value
}

function dashboardByRole(role) {
  const normalizedRole = normalizeRole(role)

  const dashboards = {
    siswa: '/student/dashboard',
    guru: '/teacher/dashboard',
    orang_tua: '/parent/dashboard',
    admin: '/admin/dashboard',
  }

  return dashboards[normalizedRole] ?? '/login'
}

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  const requiresAuth = to.matched.some((record) => {
    return Boolean(record.meta?.requiresAuth)
  })

  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }

  if (requiresAuth && !authStore.isAuthenticated) {
    return {
      path: '/login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  if (to.path === '/login' && authStore.isAuthenticated) {
    return dashboardByRole(authStore.userRole)
  }

  const requiredRoles = to.matched.flatMap((record) => {
    const roles = record.meta?.roles

    return Array.isArray(roles) ? roles : []
  })

  if (requiredRoles.length && authStore.isAuthenticated) {
    const userRole = normalizeRole(authStore.userRole)

    const allowedRoles = requiredRoles.map((role) => {
      return normalizeRole(role)
    })

    if (!allowedRoles.includes(userRole)) {
      return dashboardByRole(userRole)
    }
  }

  return true
})

router.afterEach((to) => {
  const pageTitle = to.meta?.title

  document.title = pageTitle
    ? `${pageTitle} | Jurnal Kebiasaan`
    : 'Jurnal Kebiasaan'
})

export default router

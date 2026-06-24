import DashboardLayout from '@/layouts/DashboardLayout.vue'
import RoleDashboardLayout from '@/layouts/RoleDashboardLayout.vue'

import {
  authGuard,
  guestGuard,
  roleGuard,
} from './guards'

export const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/auth/LoginPage.vue'),
    beforeEnter: guestGuard,
    meta: {
      title: 'Login',
    },
  },
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () =>
      import('@/pages/auth/UnauthorizedPage.vue'),
    meta: {
      title: 'Akses Ditolak',
    },
  },
  {
    path: '/student',
    component: DashboardLayout,
    beforeEnter: roleGuard(['siswa', 'student']),
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
  {
  children: [
        {
  path: '/parent',
  component: DashboardLayout,
  beforeEnter: roleGuard(['orang_tua', 'parent']),
  children: [
    {
      path: '',
      redirect: {
        name: 'parent-dashboard',
      },
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
    {
      path: 'history',
      name: 'parent-history',
      component: () =>
        import('@/pages/parent/ParentHistoryPage.vue'),
      meta: {
        title: 'Riwayat Check-in Anak',
      },
    },
    {
      path: 'recap',
      name: 'parent-recap',
      component: () =>
        import('@/pages/parent/ParentRecapPage.vue'),
      meta: {
        title: 'Rekap Perkembangan Anak',
      },
    },
    {
      path: 'settings',
      name: 'parent-settings',
      component: () =>
        import('@/pages/shared/ProfilePage.vue'),
      meta: {
        title: 'Pengaturan Akun',
      },
    },
  ],
},
    ],
  },
  {
    path: '/teacher',
    component: RoleDashboardLayout,
    beforeEnter: roleGuard(['guru', 'teacher']),
    children: [
      {
        path: '',
        redirect: '/teacher/dashboard',
      },
      {
        path: 'dashboard',
        name: 'teacher-dashboard',
        component: () =>
          import('@/pages/teacher/TeacherDashboard.vue'),
        meta: {
          title: 'Dashboard Guru',
        },
      },
      {
        path: 'monitoring',
        name: 'teacher-monitoring',
        component: () =>
          import('@/pages/teacher/StudentMonitoringPage.vue'),
        meta: {
          title: 'Monitoring & Validasi',
        },
      },
      {
        path: 'recap',
        name: 'teacher-recap',
        component: () =>
          import('@/pages/teacher/ClassRecapPage.vue'),
        meta: {
          title: 'Rekap Kelas',
        },
      },
    ],
  },
  {
    path: '/admin',
    component: RoleDashboardLayout,
    beforeEnter: roleGuard(['admin']),
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: () =>
          import('@/pages/admin/AdminDashboardPage.vue'),
        meta: {
          title: 'Dashboard Admin',
        },
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () =>
          import('@/pages/admin/UserManagementPage.vue'),
        meta: {
          title: 'Manajemen Pengguna',
        },
      },
      {
        path: 'classes',
        name: 'admin-classes',
        component: () =>
          import('@/pages/admin/ClassManagementPage.vue'),
        meta: {
          title: 'Manajemen Kelas',
        },
      },
      {
        path: 'habits',
        name: 'admin-habits',
        component: () =>
          import('@/pages/admin/HabitManagementPage.vue'),
        meta: {
          title: 'Manajemen Kebiasaan',
        },
      },
      {
        path: 'relations',
        name: 'admin-relations',
        component: () =>
          import('@/pages/admin/RelationManagementPage.vue'),
        meta: {
          title: 'Relasi Siswa dan Orang Tua',
        },
      },
    ],
  },
  {
    path: '/settings',
    component: RoleDashboardLayout,
    beforeEnter: authGuard,
    children: [
      {
        path: '',
        name: 'settings',
        component: () =>
          import('@/pages/shared/ProfilePage.vue'),
        meta: {
          title: 'Pengaturan Akun',
        },
      },
    ],
  },
  {
    path: '/profile',
    redirect: '/settings',
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () =>
      import('@/pages/shared/NotFoundPage.vue'),
    meta: {
      title: 'Halaman Tidak Ditemukan',
    },
  },
]
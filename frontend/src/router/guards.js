import { useAuthStore } from '@/stores/authStore'

function normalizeRole(role) {
  const rawRole =
    typeof role === 'object' && role !== null
      ? role.name ?? role.code ?? null
      : role

  const value = String(rawRole ?? '')
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

  return aliases[value] || value || null
}

function dashboardByRole(role) {
  const dashboards = {
    siswa: '/student/dashboard',
    guru: '/teacher/dashboard',
    orang_tua: '/parent/dashboard',
    admin: '/admin/dashboard',
  }

  return dashboards[normalizeRole(role)] || '/login'
}

async function restoreUser(authStore) {
  if (authStore.token && !authStore.user) {
    await authStore.fetchUser()
  }
}

export async function authGuard(to) {
  const authStore = useAuthStore()

  await restoreUser(authStore)

  if (!authStore.isAuthenticated) {
    return {
      path: '/login',
      query: {
        redirect: to.fullPath,
      },
    }
  }

  return true
}

export async function guestGuard() {
  const authStore = useAuthStore()

  await restoreUser(authStore)

  if (authStore.isAuthenticated) {
    return dashboardByRole(authStore.userRole)
  }

  return true
}

export function roleGuard(allowedRoles = []) {
  return async (to) => {
    const authStore = useAuthStore()
    const authResult = await authGuard(to)

    if (authResult !== true) {
      return authResult
    }

    const userRole = normalizeRole(authStore.userRole)
    const normalizedAllowedRoles = allowedRoles.map(normalizeRole)

    if (!normalizedAllowedRoles.includes(userRole)) {
      return {
        path: '/unauthorized',
        query: {
          redirect: to.fullPath,
        },
      }
    }

    return true
  }
}

export function registerRouterGuards(router) {
  router.afterEach((to) => {
    const pageTitle = to.meta?.title

    document.title = pageTitle
      ? `${pageTitle} | Jurnal Kebiasaan`
      : 'Jurnal Kebiasaan'
  })
}
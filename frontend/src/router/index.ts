import { createRouter, createWebHistory } from 'vue-router'
import { giftsRoutes } from '@/apps/gifts/routes'
import { useAppsStore } from '@/stores/apps'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Only for signed-in users; others are sent to /login. */
    requiresAuth?: boolean
    /** Only for signed-out users; signed-in users are sent home. */
    guestOnly?: boolean
    /** Belongs to this app (key from /api/apps); users without it are sent home. */
    app?: string
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      // Landing page after sign-in: pick which app to use.
      name: 'home',
      component: () => import('@/views/AppPickerView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      // Target of the emailed sign-in link. GET has no side effects; the page's button POSTs.
      path: '/auth/verify',
      name: 'verify-link',
      component: () => import('@/views/VerifyLinkView.vue'),
    },
    {
      path: '/install',
      name: 'install',
      component: () => import('@/views/InstallView.vue'),
    },
    {
      path: '/devices',
      name: 'devices',
      component: () => import('@/views/DevicesView.vue'),
      meta: { requiresAuth: true },
    },
    ...giftsRoutes,
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  try {
    await auth.ensureLoaded()
  } catch {
    // Server unreachable: let public pages render; protected ones fall through to /login.
  }

  if (to.meta.requiresAuth && !auth.isSignedIn) {
    return { name: 'login', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  }
  if (to.meta.guestOnly && auth.isSignedIn) {
    return { name: 'home' }
  }
  if (to.meta.app) {
    const apps = useAppsStore()
    try {
      await apps.ensureLoaded()
    } catch {
      return // Offline: let the app render; its API calls are authorized server-side anyway.
    }
    if (!apps.find(to.meta.app)) return { name: 'home' }
  }
})

export default router

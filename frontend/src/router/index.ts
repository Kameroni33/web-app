import { createRouter, createWebHistory } from 'vue-router'
import { foodRoutes } from '@/apps/food/routes'
import { giftsRoutes } from '@/apps/gifts/routes'
import { moneyRoutes } from '@/apps/money/routes'
import { landedOnAnotherProfile, tagEntry } from '@/router/navigation'
import { useAppsStore } from '@/stores/apps'
import { useAuthStore } from '@/stores/auth'

declare module 'vue-router' {
  interface RouteMeta {
    /** Only for signed-in users; others are sent to /login. Also needs a chosen profile. */
    requiresAuth?: boolean
    /** Signed in, but no profile needed yet (the profile picker itself). */
    profileOptional?: boolean
    /** Only for signed-out users; signed-in users are sent home. */
    guestOnly?: boolean
    /** Admin accounts only (me.can.manage_groups). */
    requiresGroupAdmin?: boolean
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
      // "Who's using Kam Apps?", or naming the first profile on first sign-in.
      path: '/profiles/choose',
      name: 'choose-profile',
      component: () => import('@/views/ChooseProfileView.vue'),
      meta: { requiresAuth: true, profileOptional: true },
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/settings/SettingsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/settings/profiles',
      name: 'settings-profiles',
      component: () => import('@/views/settings/ProfilesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/settings/devices',
      name: 'settings-devices',
      component: () => import('@/views/settings/DevicesView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/settings/families',
      name: 'admin-families',
      component: () => import('@/views/settings/admin/FamiliesView.vue'),
      meta: { requiresAuth: true, requiresGroupAdmin: true },
    },
    {
      path: '/settings/families/:id(\\d+)',
      name: 'admin-family',
      component: () => import('@/views/settings/admin/FamilyView.vue'),
      meta: { requiresAuth: true, requiresGroupAdmin: true },
    },
    {
      // Public: invite links work signed in or out.
      path: '/invite/:token',
      name: 'invite',
      component: () => import('@/views/InviteView.vue'),
    },
    { path: '/devices', redirect: '/settings/devices' },
    ...giftsRoutes,
    ...foodRoutes,
    ...moneyRoutes,
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to, from) => {
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
  if (to.meta.requiresAuth && !auth.profile && !to.meta.profileOptional) {
    return { name: 'choose-profile', query: to.fullPath === '/' ? {} : { redirect: to.fullPath } }
  }
  // Back/forward onto a screen from before a profile switch: start over at home.
  if (landedOnAnotherProfile(from, auth.profile?.id ?? null)) {
    return { name: 'home', replace: true }
  }
  if (to.meta.requiresGroupAdmin && !auth.user?.can.manage_groups) {
    return { name: 'settings' }
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

router.afterEach((_to, _from, failure) => {
  tagEntry(useAuthStore().profile?.id ?? null, !failure)
})

export default router

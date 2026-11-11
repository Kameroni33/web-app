import type { RouteRecordRaw } from 'vue-router'

export const giftsRoutes: RouteRecordRaw[] = [
  {
    path: '/gifts',
    name: 'gifts',
    component: () => import('./GiftsHomeView.vue'),
    meta: { requiresAuth: true, app: 'gifts' },
  },
]

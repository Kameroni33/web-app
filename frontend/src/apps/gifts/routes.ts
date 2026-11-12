import type { RouteRecordRaw } from 'vue-router'

export const giftsRoutes: RouteRecordRaw[] = [
  {
    path: '/gifts',
    name: 'gifts',
    component: () => import('@/apps/ComingSoonView.vue'),
    meta: { requiresAuth: true, app: 'gifts' },
  },
]

import type { RouteRecordRaw } from 'vue-router'

export const foodRoutes: RouteRecordRaw[] = [
  {
    path: '/food',
    name: 'food',
    component: () => import('@/apps/ComingSoonView.vue'),
    meta: { requiresAuth: true, app: 'food' },
  },
]

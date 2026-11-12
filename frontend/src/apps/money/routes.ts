import type { RouteRecordRaw } from 'vue-router'

export const moneyRoutes: RouteRecordRaw[] = [
  {
    path: '/money',
    name: 'money',
    component: () => import('@/apps/ComingSoonView.vue'),
    meta: { requiresAuth: true, app: 'money' },
  },
]

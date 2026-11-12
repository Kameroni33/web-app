import type { RouteRecordRaw } from 'vue-router'

const meta = { requiresAuth: true, app: 'gifts' }

export const giftsRoutes: RouteRecordRaw[] = [
  { path: '/gifts', name: 'gifts', component: () => import('./views/MyListView.vue'), meta },
  {
    path: '/gifts/items/:id(\\d+)',
    name: 'gifts-item',
    component: () => import('./views/ItemView.vue'),
    props: true,
    meta,
  },
  {
    path: '/gifts/lists',
    name: 'gifts-wishlists',
    component: () => import('./views/WishlistsView.vue'),
    meta,
  },
  {
    path: '/gifts/people',
    name: 'gifts-people',
    component: () => import('./views/PeopleView.vue'),
    meta,
  },
  {
    path: '/gifts/people/:profileId(\\d+)',
    name: 'gifts-list',
    component: () => import('./views/TheirListView.vue'),
    props: true,
    meta,
  },
]

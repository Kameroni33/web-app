import type { RouteLocationRaw } from 'vue-router'
import giftsIcon from '@/assets/apps/gifts.svg'

/**
 * The frontend half of each app: where it lives and its icon. The backend
 * decides which apps a user may open (GET /api/apps); keys must match its
 * config/apps.php. To add an app: add it there, add its routes in
 * src/apps/<key>/routes.ts, and register it here.
 */
export interface AppEntry {
  icon: string
  home: RouteLocationRaw
}

export const appRegistry: Record<string, AppEntry> = {
  gifts: { icon: giftsIcon, home: { name: 'gifts' } },
}

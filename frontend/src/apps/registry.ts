import type { RouteLocationRaw } from 'vue-router'
import foodIcon from '@/assets/apps/food.svg'
import giftsIcon from '@/assets/apps/gifts.svg'
import moneyIcon from '@/assets/apps/money.svg'

/**
 * The frontend half of each app: where it lives, its icon and colours. The
 * backend decides which apps a user may open (GET /api/apps); keys must
 * match its config/apps.php. To add an app: add it there, add its routes in
 * src/apps/<key>/routes.ts (spread into the router), and register it here.
 */
export interface AppTheme {
  /** [light, dark] pairs; applied with CSS light-dark() while the app is open. */
  accent: [string, string]
  accentSoft: [string, string]
}

export interface AppEntry {
  icon: string
  home: RouteLocationRaw
  theme: AppTheme
}

export const appRegistry: Record<string, AppEntry> = {
  gifts: {
    icon: giftsIcon,
    home: { name: 'gifts' },
    theme: { accent: ['#be123c', '#fb7185'], accentSoft: ['#ffe4e6', '#4c0519'] },
  },
  food: {
    icon: foodIcon,
    home: { name: 'food' },
    theme: { accent: ['#c2410c', '#fb923c'], accentSoft: ['#ffedd5', '#431407'] },
  },
  money: {
    icon: moneyIcon,
    home: { name: 'money' },
    theme: { accent: ['#4338ca', '#a5b4fc'], accentSoft: ['#e0e7ff', '#1e1b4b'] },
  },
}

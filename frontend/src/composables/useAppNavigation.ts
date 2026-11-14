import { useRouter, type RouteLocationRaw } from 'vue-router'
import { goBack, resetHistory } from '@/router/navigation'
import { useAuthStore } from '@/stores/auth'

/** Native-app style navigation: see router/navigation.ts. */
export function useAppNavigation() {
  const router = useRouter()
  const auth = useAuthStore()
  return {
    /** Start over at `to` with nothing behind it (home, profile switch, sign in/out). */
    startAt: (to: RouteLocationRaw) => resetHistory(router, to, auth.profile?.id ?? null),
    /** Return to the parent page, popping history when that's where we came from. */
    back: (to: RouteLocationRaw) => goBack(router, to),
  }
}

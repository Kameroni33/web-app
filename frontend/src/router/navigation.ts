import { START_LOCATION, type RouteLocationRaw, type Router } from 'vue-router'

/**
 * App-like history. Browsers won't let a page delete history entries, but it
 * can jump back to the first entry of its own and overwrite it, and the next
 * navigation then drops everything after it. That's how going home, switching
 * profile and signing out "clear" the back stack.
 *
 * Each entry is tagged (in history.state, next to Vue Router's own fields)
 * with its depth from that first entry and the profile that was active on it.
 * If going back or forward ever lands on another profile's entry, the router
 * guard sends you home instead.
 */

interface Tags {
  kamDepth?: number
  kamProfile?: number | null
}

const state = (): (Tags & { replaced?: boolean }) | null => window.history.state

let lastDepth: number | null = null
let popping = false

// Registered before the router's own listener (this module loads first), so the
// flag is set before any guard runs for a back/forward navigation.
window.addEventListener('popstate', () => {
  popping = true
})

function tag(tags: Tags): void {
  window.history.replaceState({ ...state(), ...tags }, '')
}

/** Router guard check: the entry being shown was made by a different profile. */
export function landedOnAnotherProfile(from: unknown, profileId: number | null): boolean {
  if (!popping && from !== START_LOCATION) return false
  const owner = state()?.kamProfile
  if (typeof owner !== 'number' || profileId === null || owner === profileId) return false
  // Claim the entry now, so the redirect that follows passes this check.
  tag({ kamProfile: profileId })
  return true
}

/** After every navigation: record the entry's depth and profile (unless it failed). */
export function tagEntry(profileId: number | null, succeeded: boolean): void {
  const current = state()
  if (current && succeeded) {
    let depth = current.kamDepth
    if (typeof depth !== 'number') {
      // A fresh push is one deeper than where it came from; anything unknown is a new root.
      depth = !popping && current.replaced === false && lastDepth !== null ? lastDepth + 1 : 0
    }
    lastDepth = depth
    tag({ kamDepth: depth, kamProfile: profileId })
  }
  popping = false
}

/**
 * Go to `to` with nothing to go back to: jump to the first entry and replace it.
 * The router isn't told about the jump, so no page in between renders.
 */
export async function resetHistory(
  router: Router,
  to: RouteLocationRaw,
  profileId: number | null,
): Promise<void> {
  const depth = state()?.kamDepth ?? 0
  if (depth > 0) {
    await new Promise<void>((resolve) => {
      const done = () => {
        clearTimeout(timer)
        window.removeEventListener('popstate', done)
        resolve()
      }
      const timer = setTimeout(done, 1000)
      window.addEventListener('popstate', done)
      router.options.history.go(-depth, false)
    })
    popping = false
  }
  tag({ kamDepth: 0, kamProfile: profileId })
  lastDepth = 0
  await router.replace(to)
}

/**
 * A "‹ Back" link: pop the stack if the previous entry is where it points,
 * so back links and the back gesture agree; otherwise replace this entry.
 */
export async function goBack(router: Router, to: RouteLocationRaw): Promise<void> {
  const back = (state() as { back?: string | null } | null)?.back
  if (back && back === router.resolve(to).fullPath) router.back()
  else await router.replace(to)
}

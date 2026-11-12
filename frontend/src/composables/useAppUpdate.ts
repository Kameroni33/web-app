import { ref } from 'vue'
import { useRegisterSW } from 'virtual:pwa-register/vue'

/**
 * Service-worker updates, shared by the update banner and Settings.
 *
 * After a deploy the new version downloads in the background and waits until
 * the user taps Reload, so nobody loses a half-filled form. Installed PWAs can
 * stay open for days, so besides the browser's own checks we look hourly and
 * whenever the app returns to the foreground.
 */
const CHECK_INTERVAL_MS = 60 * 60 * 1000

let state: ReturnType<typeof create> | null = null

function create() {
  let registration: ServiceWorkerRegistration | undefined
  let swUrl = ''
  const checking = ref(false)

  const { needRefresh, updateServiceWorker } = useRegisterSW({
    onRegisteredSW(url, reg) {
      if (!reg) return
      registration = reg
      swUrl = url
      setInterval(() => void checkForUpdate(), CHECK_INTERVAL_MS)
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') void checkForUpdate()
      })
    },
  })

  /** Resolves true when an update is waiting to be applied. */
  async function checkForUpdate(): Promise<boolean> {
    if (!registration || registration.installing || !navigator.onLine) return needRefresh.value
    checking.value = true
    try {
      // Skip if the server is unreachable or sw.js is gone, rather than throwing.
      const response = await fetch(swUrl, { cache: 'no-store' }).catch(() => null)
      if (response?.status === 200) await registration.update()
      // A found update installs before needRefresh flips; give it a moment.
      await new Promise((resolve) => setTimeout(resolve, 1500))
    } finally {
      checking.value = false
    }
    return needRefresh.value
  }

  return {
    needRefresh,
    checking,
    checkForUpdate,
    applyUpdate: () => updateServiceWorker(true),
    /** False in dev, or where service workers are unavailable. */
    supported: () => registration !== undefined,
  }
}

export function useAppUpdate() {
  state ??= create()
  return state
}

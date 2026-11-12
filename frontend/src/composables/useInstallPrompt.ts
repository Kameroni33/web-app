import { computed, ref } from 'vue'

/** Chrome/Edge/Android's install event (not in the DOM typings). */
interface BeforeInstallPromptEvent extends Event {
  prompt(): Promise<void>
  userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null)
const installed = ref(false)

export function isStandalone(win: Window = window): boolean {
  return (
    win.matchMedia?.('(display-mode: standalone)').matches === true ||
    (win.navigator as Navigator & { standalone?: boolean }).standalone === true
  )
}

/** iPhone/iPad, including iPadOS which reports itself as a Mac with touch. */
export function isIos(nav: Navigator = navigator): boolean {
  return (
    /iPad|iPhone|iPod/.test(nav.userAgent) ||
    (nav.userAgent.includes('Macintosh') && nav.maxTouchPoints > 1)
  )
}

/**
 * Call once at startup, before the app mounts: browsers fire
 * beforeinstallprompt early and only once per page load.
 */
export function listenForInstallPrompt(): void {
  installed.value = isStandalone()
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault()
    deferredPrompt.value = event as BeforeInstallPromptEvent
  })
  window.addEventListener('appinstalled', () => {
    installed.value = true
    deferredPrompt.value = null
  })
}

export function useInstallPrompt() {
  /** The browser offers a one-tap install (Android, desktop Chrome/Edge). */
  const canPrompt = computed(() => !installed.value && deferredPrompt.value !== null)
  /** iOS has no install prompt; users need Share → Add to Home Screen. */
  const needsIosInstructions = computed(() => !installed.value && isIos())

  async function promptInstall(): Promise<boolean> {
    const event = deferredPrompt.value
    if (!event) return false
    await event.prompt()
    const { outcome } = await event.userChoice
    deferredPrompt.value = null
    return outcome === 'accepted'
  }

  return { installed, canPrompt, needsIosInstructions, promptInstall }
}

import { readonly, ref } from 'vue'

export type Theme = 'system' | 'light' | 'dark'

const STORAGE_KEY = 'theme'

function read(): Theme {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored === 'light' || stored === 'dark' ? stored : 'system'
  } catch {
    return 'system'
  }
}

const theme = ref<Theme>(read())

/** "system" follows the device; light/dark override it via <html data-theme>. */
export function applyTheme(): void {
  if (theme.value === 'system') delete document.documentElement.dataset.theme
  else document.documentElement.dataset.theme = theme.value
}

/** Theme is a per-device preference, kept in this browser only. */
export function useTheme() {
  function setTheme(next: Theme): void {
    theme.value = next
    applyTheme()
    try {
      if (next === 'system') localStorage.removeItem(STORAGE_KEY)
      else localStorage.setItem(STORAGE_KEY, next)
    } catch {
      // Private mode or blocked storage: applies until the app is closed.
    }
  }

  return { theme: readonly(theme), setTheme }
}

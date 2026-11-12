import { beforeEach, describe, expect, it, vi } from 'vitest'

describe('theme', () => {
  beforeEach(() => {
    vi.resetModules()
    localStorage.clear()
    delete document.documentElement.dataset.theme
  })

  it('follows the system until a theme is chosen, and remembers the choice', async () => {
    const { applyTheme, useTheme } = await import('@/composables/useTheme')
    applyTheme()
    expect(document.documentElement.dataset.theme).toBeUndefined()

    useTheme().setTheme('dark')
    expect(document.documentElement.dataset.theme).toBe('dark')
    expect(localStorage.getItem('theme')).toBe('dark')

    useTheme().setTheme('system')
    expect(document.documentElement.dataset.theme).toBeUndefined()
    expect(localStorage.getItem('theme')).toBeNull()
  })

  it('restores the saved theme on the next launch', async () => {
    localStorage.setItem('theme', 'light')
    const { applyTheme, useTheme } = await import('@/composables/useTheme')

    applyTheme()

    expect(useTheme().theme.value).toBe('light')
    expect(document.documentElement.dataset.theme).toBe('light')
  })
})
